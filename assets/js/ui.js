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

    static renderDestinationCard(dest, baseUrl) {
        const alt = `${dest.City || ''}, ${dest.Country || ''}`;
        const imgUrl = UIController.resolveImageUrl(dest.ImageURL, baseUrl);
        const imgTag = imgUrl
            ? `<img src="${UIController.esc(imgUrl)}" alt="${UIController.esc(alt)}" class="card-img" loading="lazy" onerror="this.classList.add('is-hidden');var n=this.nextElementSibling;if(n)n.classList.remove('is-hidden');">`
            : '';
        const placeholderClass = imgUrl ? 'card-img-placeholder is-hidden' : 'card-img-placeholder';
        const initials = UIController.esc((dest.City || '?').charAt(0).toUpperCase());

        return `<a href="${UIController.esc(baseUrl)}/traveller/destination.php?id=${encodeURIComponent(dest.DestinationID)}"
               class="card feature-card hover-lift">
            <div class="card-media">
                ${imgTag}
                <div class="${placeholderClass}" aria-hidden="${imgUrl ? 'true' : 'false'}">
                    <span class="card-img-placeholder-icon">${initials}</span>
                    <span class="card-img-placeholder-text">No photo</span>
                </div>
            </div>
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
                return `<div class="card"><div class="card-body">
                    <h3>${UIController.esc(acc.Name)}</h3>
                    <span class="badge">${UIController.esc(acc.Type)}</span>
                    <span class="stars">${stars}</span>
                    <p>${UIController.formatMoney(acc.PricePerNight)} / night</p>
                </div></div>`;
            },
            restaurants: (r) => {
                const rating = parseFloat(r.Rating);
                return `<div class="card"><div class="card-body">
                    <h3>${UIController.esc(r.Name)}</h3>
                    <span class="badge">${UIController.esc(r.CuisineType)}</span>
                    <p>Rating: ${Number.isNaN(rating) ? '—' : rating.toFixed(1)}/5</p>
                </div></div>`;
            },
            attractions: (attr) => `<div class="card"><div class="card-body">
                <h3>${UIController.esc(attr.Name)}</h3>
                <span class="badge">${UIController.esc(attr.Type)}</span>
                <p>${UIController.esc(UIController.truncate(attr.Description, 120))}</p>
            </div></div>`
        };

        root.querySelectorAll('[data-infinite-sentinel]').forEach(sentinel => {
            UIController.observeSentinel(sentinel, () => {
                const tabId = sentinel.dataset.tab;
                const panel = document.getElementById('tab-' + tabId);
                if (!panel || panel.style.display === 'none') return;
                UIController.browseLoadTab(tabId, false);
            });
        });

        UIController.browseLoadTab('destinations', true);
    }

    static observeSentinel(sentinel, callback) {
        if (!sentinel || !('IntersectionObserver' in window)) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) callback();
            });
        }, { root: null, rootMargin: '240px', threshold: 0 });
        observer.observe(sentinel);
        return observer;
    }

    static browseGetTabElements(tabId) {
        const panel = document.getElementById('tab-' + tabId);
        if (!panel) return null;
        return {
            content: panel.querySelector('[data-browse-content]'),
            status: panel.querySelector('[data-browse-status]')
        };
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
        }
    }
}

window.showTab = (id) => UIController.showTab(id);

document.addEventListener('DOMContentLoaded', () => {
    UIController.init();
});
