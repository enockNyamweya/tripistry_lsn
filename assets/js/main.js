/** Backup if viewport script did not run (e.g. cached head). */
function initForceDesktopViewOnMobile() {
    if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return;
    }
    document.documentElement.classList.add('force-desktop-view');
    var meta = document.querySelector('meta[name="viewport"]');
    if (meta) {
        meta.setAttribute('content', 'width=1024');
    }
}

function initFlashMessages() {
    document.querySelectorAll('.alert-success, .alert-error').forEach(function (msg) {
        setTimeout(function () {
            msg.style.opacity = '0';
            msg.style.transition = 'opacity 0.5s';
            setTimeout(function () { msg.remove(); }, 500);
        }, 4000);
    });
}

function escHtml(text) {
    const el = document.createElement('span');
    el.textContent = text == null ? '' : String(text);
    return el.innerHTML;
}

function formatMoney(amount) {
    const n = parseFloat(amount);
    if (Number.isNaN(n)) return 'R0.00';
    return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatPackageDate(value) {
    if (!value) return '';
    const d = new Date(String(value).slice(0, 10));
    if (Number.isNaN(d.getTime())) return escHtml(value);
    return d.toLocaleDateString('en-ZA', { month: 'short', day: 'numeric', year: 'numeric' });
}

function setLoadMoreButton(btn, nextPage, loading) {
    if (!btn) return;
    if (nextPage == null) {
        btn.style.display = 'none';
        btn.disabled = true;
        return;
    }
    btn.style.display = '';
    btn.disabled = !!loading;
    btn.textContent = loading ? 'Loading…' : 'Load more';
}

function resolveImageUrl(url, baseUrl) {
    if (!url || !String(url).trim()) return '';
    const u = String(url).trim();
    if (/^https?:\/\//i.test(u)) return u;
    if (u.startsWith('/')) return baseUrl + u;
    if (u.startsWith('..')) return baseUrl + '/' + u.replace(/^\.\.\//, '');
    return u;
}

function initPackagesInfinite() {
    const root = document.getElementById('packages-lazy');
    if (!root) return;

    const apiBase = root.dataset.apiBase;
    const baseUrl = root.dataset.baseUrl || '';
    const pageSize = parseInt(root.dataset.pageSize, 10) || 12;
    const compareIds = (root.dataset.compareIds || '')
        .split(',')
        .map(function (id) { return parseInt(id, 10); })
        .filter(function (id) { return !Number.isNaN(id) && id > 0; });

    const listEl = root.querySelector('[data-packages-list]');
    const statusEl = root.querySelector('[data-packages-status]');
    const loadMoreBtn = root.querySelector('[data-packages-load-more]');

    let nextPage = 1;
    let loading = false;
    let hasMore = true;

    function setStatus(message, isError) {
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.className = 'lazy-status' + (isError ? ' lazy-status-error' : '');
        statusEl.style.display = message ? 'block' : 'none';
    }

    function parseFilterPrice(field) {
        if (!field || field.value === '' || field.value === null) return null;
        const n = parseFloat(String(field.value).replace(',', '.').trim());
        if (Number.isNaN(n) || n <= 0) return null;
        return n;
    }

    function validateFilterPrices(form) {
        if (!form) return { ok: true, min: null, max: null };
        const min = parseFilterPrice(form.elements['min_price']);
        const max = parseFilterPrice(form.elements['max_price']);
        if (min !== null && max !== null && max < min) {
            return {
                ok: false,
                message: 'Max price must be higher than min price, or leave max empty for no upper limit.'
            };
        }
        return { ok: true, min: min, max: max };
    }

    function buildFilterParams(page) {
        const form = document.getElementById('packages-filter-form');
        const params = new URLSearchParams();
        params.set('page', String(page));
        params.set('limit', String(pageSize));
        if (!form) return params;

        const prices = validateFilterPrices(form);
        if (!prices.ok) return params;

        ['search', 'destination', 'sort'].forEach(function (name) {
            const field = form.elements[name];
            if (field && field.value !== '') params.set(name, field.value);
        });
        if (prices.min !== null) params.set('min_price', String(prices.min));
        if (prices.max !== null) params.set('max_price', String(prices.max));

        ['min_duration', 'max_duration'].forEach(function (name) {
            const field = form.elements[name];
            if (field && field.value !== '') params.set(name, field.value);
        });

        const minRating = form.elements['min_rating'];
        if (minRating && minRating.value !== '') params.set('min_rating', minRating.value);
        return params;
    }

    function renderPackage(pkg) {
        const rating = parseFloat(pkg.AvgRating);
        const stars = rating > 0 ? '★'.repeat(Math.round(rating)) : '';
        const ratingHtml = rating > 0
            ? stars + ' ' + rating.toFixed(1) + ' (' + (pkg.ReviewCount || 0) + ' reviews)'
            : 'No reviews yet';
        const imgUrl = resolveImageUrl(pkg.ImageURL, baseUrl);
        const fallbackImg = 'https://loremflickr.com/400/300/travel,landscape?lock=' + pkg.PackageID;
        const placeholderClass = imgUrl ? 'card-img-placeholder is-hidden' : 'card-img-placeholder';
        const mediaBlock = '<div class="card-media">' +
            (imgUrl ? '<img src="' + escHtml(imgUrl) + '" alt="' + escHtml(pkg.Title) + '" class="card-img" loading="lazy" onerror="this.onerror=null;this.src=\'' + fallbackImg + '\';">' : '') +
            '<div class="' + placeholderClass + '"' + (imgUrl ? ' hidden' : '') + '><span class="card-img-placeholder-text">No photo</span></div></div>';
        const ids = compareIds.slice();
        if (ids.indexOf(pkg.PackageID) === -1) ids.push(pkg.PackageID);
        const compareUrl = 'packages.php?compare=' + encodeURIComponent(ids.slice(0, 3).join(','));

        const dest = escHtml((pkg.DestinationCity || 'N/A') + ', ' + (pkg.DestinationCountry || ''));

        return '<article class="package-card">' +
            '<header class="package-card-header">' +
            '<h2 class="package-card-title">' + escHtml(pkg.Title) + '</h2>' +
            '<span class="agency-badge">' + escHtml(pkg.AgencyName) + '</span></header>' +
            '<div class="package-card-body">' + mediaBlock +
            '<div class="package-info">' +
            '<ul class="package-meta">' +
            '<li><strong>Destination</strong><span>' + dest + '</span></li>' +
            '<li><strong>Duration</strong><span>' + escHtml(pkg.DurationDays) + ' days</span></li>' +
            '<li><strong>Dates</strong><span>' + formatPackageDate(pkg.StartDate) + ' to ' + formatPackageDate(pkg.EndDate) + '</span></li>' +
            '<li><strong>Max travellers</strong><span>' + escHtml(pkg.MaxTravellers) + '</span></li>' +
            '</ul>' +
            '<p class="package-rating">' + ratingHtml + '</p>' +
            '<p class="package-price">' + formatMoney(pkg.Price) + '</p></div></div>' +
            '<footer class="package-card-footer">' +
            '<a href="package_detail.php?id=' + encodeURIComponent(pkg.PackageID) + '" class="btn btn-primary">View Details</a>' +
            '<a href="' + escHtml(compareUrl) + '" class="btn btn-secondary">Compare</a></footer></article>';
    }

    async function loadPage(page, reset) {
        if (loading) return;
        if (!hasMore && !reset) return;

        loading = true;
        if (!reset && loadMoreBtn && nextPage) {
            setLoadMoreButton(loadMoreBtn, nextPage, true);
        }
        setStatus(page === 1 ? 'Loading packages...' : '', false);

        try {
            const form = document.getElementById('packages-filter-form');
            const priceCheck = validateFilterPrices(form);
            if (!priceCheck.ok) {
                if (reset) listEl.innerHTML = '';
                listEl.innerHTML = '<p class="empty-state packages-filter-hint">' + escHtml(priceCheck.message) + '</p>';
                nextPage = null;
                hasMore = false;
                setLoadMoreButton(loadMoreBtn, null, false);
                setStatus('', false);
                loading = false;
                return;
            }

            const params = buildFilterParams(page);
            const res = await fetch(apiBase + '/packages?' + params.toString());
            if (!res.ok) throw new Error('Failed to load packages');
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Request failed');

            if (reset) listEl.innerHTML = '';

            if (json.data.length === 0 && page === 1) {
                listEl.innerHTML = '<p class="empty-state">No packages found matching your criteria.</p>';
            } else {
                if (page === 1 && listEl.querySelector('.empty-state')) listEl.innerHTML = '';
                listEl.insertAdjacentHTML('beforeend', json.data.map(renderPackage).join(''));
            }

            nextPage = json.pagination.next_page;
            hasMore = nextPage !== null;
            setStatus('', false);
        } catch (err) {
            setStatus(err.message || 'Could not load packages.', true);
        } finally {
            loading = false;
            setLoadMoreButton(loadMoreBtn, nextPage, false);
        }
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            if (nextPage && !loading) loadPage(nextPage, false);
        });
    }

    const filterForm = document.getElementById('packages-filter-form');
    if (filterForm) {
        filterForm.querySelectorAll('.filter-price-input').forEach(function (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault();
            });
            input.addEventListener('input', function () {
                const raw = String(input.value).trim();
                if (raw === '' || raw === '0' || raw === '00') {
                    input.value = '';
                    return;
                }
                const n = parseFloat(raw.replace(',', '.'));
                if (Number.isNaN(n) || n <= 0) input.value = '';
            });
            input.addEventListener('change', function () {
                const n = parseFloat(String(input.value).replace(',', '.'));
                if (input.value !== '' && (Number.isNaN(n) || n <= 0)) input.value = '';
            });
        });

        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hasMore = true;
            nextPage = 1;
            loadPage(1, true);
        });
    }

    loadPage(1, true);
}

function formatShortDate(value) {
    if (!value) return '';
    const d = new Date(String(value).slice(0, 10));
    if (Number.isNaN(d.getTime())) return escHtml(value);
    return d.toLocaleDateString('en-ZA', { month: 'short', day: 'numeric', year: 'numeric' });
}

function agencyApiUrl(apiBase, resource, availableType, packageId) {
    let url = apiBase + '/agency/' + resource;
    if (resource === 'available' && availableType) {
        url += '/' + availableType;
    }
    return url;
}

function initAgencyInfinite() {
    document.querySelectorAll('[data-agency-infinite]').forEach(function (root) {
        const apiBase = root.dataset.apiBase;
        const resource = root.dataset.resource;
        const pageSize = parseInt(root.dataset.pageSize, 10) || 15;
        const packageId = root.dataset.packageId || '';
        const availableType = root.dataset.availableType || '';
        const listEl = root.querySelector('[data-agency-list]');
        const statusEl = root.querySelector('[data-agency-status]');
        const loadMoreBtn = root.querySelector('[data-agency-load-more]');
        const listMode = root.dataset.listMode || 'table';

        if (!apiBase || !resource || !listEl) return;

        let nextPage = 1;
        let loading = false;
        let hasMore = true;

        function setStatus(message, isError) {
            if (!statusEl) return;
            statusEl.textContent = message || '';
            statusEl.className = 'lazy-status' + (isError ? ' lazy-status-error' : '');
            statusEl.style.display = message ? 'block' : 'none';
        }

        function buildParams(page) {
            const params = new URLSearchParams();
            params.set('page', String(page));
            params.set('limit', String(pageSize));
            if (packageId) params.set('package_id', packageId);
            return params;
        }

        function renderRow(item) {
            if (resource === 'packages') {
                const rating = item.AvgRating ? parseFloat(item.AvgRating).toFixed(1) + ' ★' : 'N/A';
                const statusClass = (item.Status || '').toLowerCase();
                return '<tr>' +
                    '<td>' + escHtml(item.Title) + '</td>' +
                    '<td>' + escHtml(item.DestinationCity || 'N/A') + '</td>' +
                    '<td>' + formatMoney(item.Price) + '</td>' +
                    '<td>' + escHtml(item.DurationDays) + ' days</td>' +
                    '<td>' + rating + '</td>' +
                    '<td>' + escHtml(item.BookingCount) + '</td>' +
                    '<td><span class="status-badge status-' + escHtml(statusClass) + '">' + escHtml(item.Status) + '</span></td>' +
                    '<td class="actions">' +
                    '<a href="edit_package.php?id=' + encodeURIComponent(item.PackageID) + '" class="btn btn-secondary btn-sm">Edit</a> ' +
                    '<a href="manage_items.php?id=' + encodeURIComponent(item.PackageID) + '" class="btn btn-secondary btn-sm">Items</a> ' +
                    '<a href="?delete=' + encodeURIComponent(item.PackageID) + '" class="btn btn-danger btn-sm" onclick="return confirm(\'Delete this package?\')">Delete</a>' +
                    '</td></tr>';
            }
            if (resource === 'bookings' && root.dataset.variant === 'dashboard') {
                const statusClass = (item.Status || '').toLowerCase();
                return '<tr>' +
                    '<td>' + escHtml(item.PackageTitle) + '</td>' +
                    '<td>' + escHtml(item.FirstName + ' ' + item.LastName) + '</td>' +
                    '<td>' + formatShortDate(item.BookingDate) + '</td>' +
                    '<td>' + formatMoney(item.TotalCost) + '</td>' +
                    '<td><span class="status-badge status-' + escHtml(statusClass) + '">' + escHtml(item.Status) + '</span></td>' +
                    '</tr>';
            }
            if (resource === 'bookings') {
                let actions = '';
                if (item.Status === 'Pending') {
                    actions = '<form method="POST" style="display:inline;">' +
                        '<input type="hidden" name="booking_id" value="' + escHtml(item.BookingID) + '">' +
                        '<button type="submit" name="action" value="confirm" class="btn btn-primary btn-sm">Confirm</button> ' +
                        '<button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button></form>';
                } else if (item.Status === 'Confirmed') {
                    actions = '<form method="POST" style="display:inline;">' +
                        '<input type="hidden" name="booking_id" value="' + escHtml(item.BookingID) + '">' +
                        '<button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button></form>';
                }
                const statusClass = (item.Status || '').toLowerCase();
                return '<tr>' +
                    '<td>#' + escHtml(item.BookingID) + '</td>' +
                    '<td>' + escHtml(item.PackageTitle) + '</td>' +
                    '<td>' + escHtml(item.FirstName + ' ' + item.LastName) + '</td>' +
                    '<td>' + escHtml(item.PassportNum || 'N/A') + '</td>' +
                    '<td>' + escHtml(item.PhoneNumber || 'N/A') + '</td>' +
                    '<td>' + formatShortDate(item.BookingDate) + '</td>' +
                    '<td>' + escHtml(item.NumTravellers) + '</td>' +
                    '<td>' + formatMoney(item.TotalCost) + '</td>' +
                    '<td><span class="status-badge status-' + escHtml(statusClass) + '">' + escHtml(item.Status) + '</span></td>' +
                    '<td class="actions">' + actions + '</td></tr>';
            }
            if (resource === 'group-trips') {
                const statusClass = (item.Status || '').toLowerCase();
                let actions = '<a href="edit_package.php?id=' + encodeURIComponent(item.PID) + '" class="btn btn-secondary btn-sm">Edit Package</a> ';
                if (item.Status === 'Open') {
                    actions += '<a href="?gid=' + encodeURIComponent(item.GroupTripID) + '&status=Closed" class="btn btn-secondary btn-sm">Close</a> ';
                } else if (item.Status === 'Closed') {
                    actions += '<a href="?gid=' + encodeURIComponent(item.GroupTripID) + '&status=Open" class="btn btn-secondary btn-sm">Reopen</a> ';
                }
                if (item.Status !== 'Cancelled') {
                    actions += '<a href="?gid=' + encodeURIComponent(item.GroupTripID) + '&status=Cancelled" class="btn btn-danger btn-sm" onclick="return confirm(\'Cancel this group trip?\')">Cancel</a>';
                }
                const dep = formatShortDate(item.DepartureDate);
                const ret = new Date(String(item.ReturnDate).slice(0, 10));
                const retStr = Number.isNaN(ret.getTime()) ? '' : ret.toLocaleDateString('en-ZA', { month: 'short', day: 'numeric' });
                return '<tr>' +
                    '<td>' + escHtml(item.GroupName) + '</td>' +
                    '<td>' + escHtml(item.PackageTitle) + '</td>' +
                    '<td>' + escHtml(item.DestinationCity || 'N/A') + '</td>' +
                    '<td>' + dep + ' to ' + retStr + '</td>' +
                    '<td>' + escHtml(item.MinParticipants) + '-' + escHtml(item.MaxParticipants) + '</td>' +
                    '<td>' + escHtml(item.EnrolmentCount) + '</td>' +
                    '<td><span class="status-badge status-' + escHtml(statusClass) + '">' + escHtml(item.Status) + '</span></td>' +
                    '<td class="actions">' + actions + '</td></tr>';
            }
            if (resource === 'available') {
                const idField = item.DestinationID != null ? 'DestinationID'
                    : item.FlightID != null ? 'FlightID'
                    : item.AccommodationID != null ? 'AccommodationID'
                    : item.RestaurantID != null ? 'RestaurantID'
                    : 'AttractionID';
                const addType = root.dataset.addType || availableType.replace(/s$/, '');
                const label = (item.NameCol || item.City || item.Name || '') +
                    (item.SubCol ? ' (' + item.SubCol + ')' : '');
                return '<div class="available-picker-row">' +
                    '<span>' + escHtml(label) + '</span>' +
                    '<form method="POST" action="">' +
                    '<input type="hidden" name="add_type" value="' + escHtml(addType) + '">' +
                    '<input type="hidden" name="add_id" value="' + escHtml(item[idField]) + '">' +
                    '<button type="submit" class="btn btn-primary btn-sm">Add</button></form></div>';
            }
            return '';
        }

        async function loadPage(page, reset) {
            if (loading) return;
            if (!hasMore && !reset) return;

            loading = true;
            if (!reset && loadMoreBtn && nextPage) {
                setLoadMoreButton(loadMoreBtn, nextPage, true);
            }
            setStatus(page === 1 ? 'Loading...' : '', false);

            try {
                const params = buildParams(page);
                const url = agencyApiUrl(apiBase, resource, availableType, packageId) + '?' + params.toString();
                const res = await fetch(url, { credentials: 'same-origin' });
                if (res.status === 401) throw new Error('Please log in as an agency user.');
                if (!res.ok) throw new Error('Failed to load data');
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Request failed');

                if (reset) listEl.innerHTML = '';

                if (json.data.length === 0 && page === 1) {
                    const emptyMsg = root.dataset.emptyMessage || 'No items found.';
                    if (listMode === 'picker') {
                        listEl.innerHTML = '<p class="text-muted">' + escHtml(emptyMsg) + '</p>';
                    } else {
                        listEl.innerHTML = '<tr><td colspan="20">' + escHtml(emptyMsg) + '</td></tr>';
                    }
                } else {
                    if (page === 1 && listEl.querySelector('.empty-state, .text-muted')) listEl.innerHTML = '';
                    listEl.insertAdjacentHTML('beforeend', json.data.map(renderRow).join(''));
                }

                nextPage = json.pagination.next_page;
                hasMore = nextPage !== null;
                setStatus('', false);
            } catch (err) {
                setStatus(err.message || 'Could not load data.', true);
            } finally {
                loading = false;
                setLoadMoreButton(loadMoreBtn, nextPage, false);
            }
        }

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                if (nextPage && !loading) loadPage(nextPage, false);
            });
        }

        loadPage(1, true);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initForceDesktopViewOnMobile();
    initFlashMessages();
    initPackagesInfinite();
    initAgencyInfinite();
});

document.addEventListener('click', function (e) {
    const stat = e.target.closest('.stat-card h3');
    if (!stat) return;
    stat.classList.toggle('expanded');
});
