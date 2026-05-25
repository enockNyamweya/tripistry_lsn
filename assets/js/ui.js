class UIController {

    static esc(text) {
        const el = document.createElement('span');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    static formatDateTime(value) {
        if (!value) return '';
        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return UIController.esc(value);
        return d.toLocaleString('en-ZA', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    static formatMoney(amount) {
        const n = parseFloat(amount);
        if (Number.isNaN(n)) return 'R0.00';
        return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    static truncate(text, len) {
        const s = text || '';
        return s.length <= len ? s : s.slice(0, len) + '...';
    }

    static resolveImageUrl(url, baseUrl) {
        if (!url || !String(url).trim()) return '';
        const u = String(url).trim();
        if (/^https?:\/\//i.test(u)) return u;
        if (u.startsWith('/')) return baseUrl + u;
        if (u.startsWith('..')) return baseUrl + '/' + u.replace(/^\.\.\//, '');
        return u;
    }

    static photoUrl(category, id) {
        return `https://loremflickr.com/400/300/${category}?lock=${id || 1}`;
    }

    static mediaBlock(imgUrl, alt, label, sublabel, color) {
        if (imgUrl && imgUrl.trim()) {
            return `<div class="card-media">
                <img src="${UIController.esc(imgUrl)}" alt="${UIController.esc(alt)}" class="card-img" loading="lazy" onerror="this.style.display='none';var p=this.parentElement.querySelector('.card-media-fallback');if(p)p.style.display='flex';">
                <div class="card-media-fallback" style="display:none;background:linear-gradient(160deg, ${color||'#4f46e5'} 0%, ${color||'#4f46e5'}cc 40%, ${color||'#4f46e5'} 100%);min-height:160px;align-items:center;justify-content:center;flex-direction:column;padding:1.5rem;text-align:center;">
                    <div style="font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.7);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;">${UIController.esc(sublabel)}</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#fff;line-height:1.3;max-width:90%;">${UIController.esc(label)}</div>
                </div>
            </div>`;
        }
        return `<div class="card-media" style="background:linear-gradient(160deg, ${color||'#4f46e5'} 0%, ${color||'#4f46e5'}cc 40%, ${color||'#4f46e5'} 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:160px;padding:1.5rem;text-align:center;">
            <div style="font-size:0.8rem;font-weight:700;color:rgba(255,255,255,0.7);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.5rem;">${UIController.esc(sublabel)}</div>
            <div style="font-size:1.1rem;font-weight:700;color:#fff;line-height:1.3;max-width:90%;">${UIController.esc(label)}</div>
        </div>`;
    }

    static renderDestinationCard(dest, baseUrl) {
        const alt = `${dest.City || ''}, ${dest.Country || ''}`;
        const imgUrl = UIController.resolveImageUrl(dest.ImageURL, baseUrl) || UIController.photoUrl('city,skyline', dest.DestinationID);
        return `<a href="${UIController.esc(baseUrl)}/traveller/destination.php?id=${encodeURIComponent(dest.DestinationID)}"
               class="card feature-card hover-lift">
            ${UIController.mediaBlock(imgUrl, alt, `${dest.City}, ${dest.Country}`, 'Destination', '#2563eb')}
            <div class="card-body">
                <h3>${UIController.esc(dest.City)}, ${UIController.esc(dest.Country)}</h3>
                <p>${UIController.esc(UIController.truncate(dest.Description, 120))}</p>
            </div>
        </a>`;
    }

    static init() {
        this.initTabs();
        this.initBrowseInfinite();
    }

    static initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const match = btn.getAttribute('onclick')?.match(/'([^']+)'/);
                if (match) this.showTab(match[1]);
            });
        });
    }

    static showTab(tabId) {
        document.querySelectorAll('.tab-content')
            .forEach(el => el.style.display = 'none');

        document.querySelectorAll('.tab-btn')
            .forEach(el => el.classList.remove('active'));

        const target = document.getElementById('tab-' + tabId);
        if (target) target.style.display = 'block';

        document.querySelector(`[onclick*="${tabId}"]`)
            ?.classList.add('active');

        UIController.browseEnsureTab(tabId);
    }

    static initBrowseInfinite() {
        const root = document.getElementById('browse-lazy');
        if (!root) return;

        UIController.browseRoot = root;
        UIController.browseApiBase = root.dataset.apiBase;
        UIController.browseBaseUrl = root.dataset.baseUrl;
        UIController.browsePageSize = parseInt(root.dataset.pageSize, 10) || 12;
        UIController.browseTabState = {};

        UIController.browseEndpoints = {
            destinations: { path: 'destinations', type: 'grid' },
            flights: { path: 'flights', type: 'table' },
            accommodations: { path: 'accommodations', type: 'grid' },
            restaurants: { path: 'restaurants', type: 'grid' },
            attractions: { path: 'attractions', type: 'grid' }
        };

        UIController.browseRenderers = {
            destinations: (dest) => UIController.renderDestinationCard(dest, UIController.browseBaseUrl),
            flights: (f) => `<tr>
                <td>${UIController.esc(f.Airline)}</td>
                <td>${UIController.esc(f.FlightNumber)}</td>
                <td>${UIController.esc(f.DepartureCity)}</td>
                <td>${UIController.esc(f.ArrivalCity)}</td>
                <td>${UIController.formatDateTime(f.DepartureTime)}</td>
                <td>${UIController.formatDateTime(f.ArrivalTime)}</td>
                <td>${UIController.formatMoney(f.Price)}</td>
            </tr>`,
            accommodations: (acc) => {
                const stars = '★'.repeat(Math.max(0, parseInt(acc.StarRating, 10) || 0));
                const imgUrl = UIController.resolveImageUrl(acc.ImageURL, UIController.browseBaseUrl) || UIController.photoUrl('hotel,resort,room', acc.AccommodationID);
                const city = acc.City ? `${acc.City}, ${acc.Country || ''}` : '';
                return `<a href="${UIController.browseBaseUrl}/traveller/accommodation_detail.php?id=${encodeURIComponent(acc.AccommodationID)}" class="card hover-lift" style="text-decoration:none;color:inherit;">
                    ${UIController.mediaBlock(imgUrl, acc.Name, acc.Name, acc.Type || 'Hotel', '#059669')}
                    <div class="card-body">
                        <h3>${UIController.esc(acc.Name)}</h3>
                        <span class="badge">${UIController.esc(acc.Type || 'Hotel')}</span>
                        <span class="stars" style="color:#f59e0b;margin-left:0.5rem;">${stars}</span>
                        ${city ? `<p style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">📍 ${UIController.esc(city)}</p>` : ''}
                        <p style="font-weight:600;color:#059669;margin-top:0.5rem;">${UIController.formatMoney(acc.PricePerNight)} <span style="font-weight:400;color:#64748b;">/ night</span></p>
                    </div>
                </a>`;
            },
            restaurants: (r) => {
                const rating = parseFloat(r.Rating);
                const imgUrl = UIController.resolveImageUrl(r.ImageURL, UIController.browseBaseUrl) || UIController.photoUrl('food,restaurant,dining', r.RestaurantID);
                const city = r.City ? `${r.City}, ${r.Country || ''}` : '';
                const cuisine = r.CuisineType || 'Various';
                return `<a href="${UIController.browseBaseUrl}/traveller/restaurant_detail.php?id=${encodeURIComponent(r.RestaurantID)}" class="card hover-lift" style="text-decoration:none;color:inherit;">
                    ${UIController.mediaBlock(imgUrl, r.Name, r.Name, cuisine, '#d97706')}
                    <div class="card-body">
                        <h3>${UIController.esc(r.Name)}</h3>
                        <span class="badge">${UIController.esc(cuisine)}</span>
                        <span style="color:#f59e0b;margin-left:0.5rem;">${Number.isNaN(rating) ? '—' : '★'.repeat(Math.round(rating)) + ' ' + rating.toFixed(1)}</span>
                        ${r.PriceRange ? `<span class="badge" style="margin-left:0.5rem;">${UIController.esc(r.PriceRange)}</span>` : ''}
                        ${city ? `<p style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">📍 ${UIController.esc(city)}</p>` : ''}
                    </div>
                </a>`;
            },
            attractions: (attr) => {
                const imgUrl = UIController.resolveImageUrl(attr.ImageURL, UIController.browseBaseUrl) || UIController.photoUrl('landmark,tourism,travel', attr.AttractionID);
                const city = attr.City ? `${attr.City}, ${attr.Country || ''}` : '';
                const fee = parseFloat(attr.EntryFee) > 0 ? UIController.formatMoney(attr.EntryFee) : 'Free';
                const type = attr.Type || 'Attraction';
                return `<a href="${UIController.browseBaseUrl}/traveller/attraction_detail.php?id=${encodeURIComponent(attr.AttractionID)}" class="card hover-lift" style="text-decoration:none;color:inherit;">
                    ${UIController.mediaBlock(imgUrl, attr.Name, attr.Name, type, '#7c3aed')}
                    <div class="card-body">
                        <h3>${UIController.esc(attr.Name)}</h3>
                        <span class="badge">${UIController.esc(type)}</span>
                        <span class="badge" style="margin-left:0.5rem;">${fee}</span>
                        ${city ? `<p style="font-size:0.8rem;color:#64748b;margin-top:0.25rem;">📍 ${UIController.esc(city)}</p>` : ''}
                        <p>${UIController.esc(UIController.truncate(attr.Description, 100))}</p>
                    </div>
                </a>`;
            }
        };

        root.querySelectorAll('[data-browse-load-more]').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                if (tabId) UIController.browseLoadTab(tabId, false);
            });
        });

        UIController.browseLoadTab('destinations', true);
    }

    static browseGetTabElements(tabId) {
        const panel = document.getElementById('tab-' + tabId);
        if (!panel) return null;
        return {
            content: panel.querySelector('[data-browse-content]'),
            status: panel.querySelector('[data-browse-status]'),
            loadMore: panel.querySelector('[data-browse-load-more]')
        };
    }

    static browseUpdateLoadMore(tabId) {
        const els = UIController.browseGetTabElements(tabId);
        const state = UIController.browseTabState[tabId];
        if (!els?.loadMore || !state) return;
        if (state.nextPage) {
            els.loadMore.style.display = '';
            els.loadMore.disabled = state.loading;
            els.loadMore.textContent = state.loading ? 'Loading…' : 'Load more';
        } else {
            els.loadMore.style.display = 'none';
            els.loadMore.disabled = true;
        }
    }

    static browseSetStatus(el, message, isError) {
        if (!el) return;
        el.textContent = message || '';
        el.className = 'lazy-status' + (isError ? ' lazy-status-error' : '');
        el.style.display = message ? 'block' : 'none';
    }

    static browseEnsureTab(tabId) {
        const state = UIController.browseTabState[tabId];
        if (!state || !state.loaded) {
            UIController.browseLoadTab(tabId, true);
        }
    }

    static async browseLoadTab(tabId, reset) {
        const cfg = UIController.browseEndpoints?.[tabId];
        if (!cfg || !UIController.browseApiBase) return;

        const els = UIController.browseGetTabElements(tabId);
        if (!els?.content) return;

        if (!UIController.browseTabState[tabId]) {
            UIController.browseTabState[tabId] = { loading: false, loaded: false, nextPage: null };
        }
        const state = UIController.browseTabState[tabId];
        if (state.loading) return;
        if (!reset && !state.nextPage) return;

        const page = reset ? 1 : state.nextPage;
        if (!page) return;

        if (reset) {
            els.content.innerHTML = '';
            state.nextPage = null;
            state.loaded = false;
        }

        state.loading = true;
        UIController.browseUpdateLoadMore(tabId);
        UIController.browseSetStatus(els.status, 'Loading...', false);

        try {
            const url = `${UIController.browseApiBase}/${cfg.path}?page=${page}&limit=${UIController.browsePageSize}`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Failed to load ' + tabId);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Request failed');

            const render = UIController.browseRenderers[tabId];
            const html = json.data.map(render).join('');

            if (cfg.type === 'table') {
                if (reset || !els.content.querySelector('table')) {
                    els.content.innerHTML =
                        '<table class="data-table"><thead><tr>' +
                        '<th>Airline</th><th>Flight #</th><th>From</th><th>To</th>' +
                        '<th>Departure</th><th>Arrival</th><th>Price</th>' +
                        '</tr></thead><tbody></tbody></table>';
                }
                els.content.querySelector('tbody').insertAdjacentHTML('beforeend', html);
            } else {
                els.content.insertAdjacentHTML('beforeend', html);
            }

            if (json.data.length === 0 && page === 1) {
                UIController.browseSetStatus(els.status, 'No results found.', false);
            } else {
                UIController.browseSetStatus(els.status, '', false);
            }

            state.nextPage = json.pagination.next_page;
            state.loaded = true;
        } catch (err) {
            UIController.browseSetStatus(els.status, err.message || 'Could not load data.', true);
        } finally {
            state.loading = false;
            UIController.browseUpdateLoadMore(tabId);
        }
    }
}

window.showTab = (id) => UIController.showTab(id);

document.addEventListener('DOMContentLoaded', () => {
    UIController.init();
});
