import Alpine from 'alpinejs';
import './echo.js';

/*
 * SweetAlert2 (~40KB gzip) is only needed when a confirm dialog, toast, or flash
 * message actually fires — which never happens on a plain page view. Load it on
 * first use and memoise the module so subsequent calls are instant.
 */
let swalModule;
const loadSwal = () => (swalModule ??= import('sweetalert2').then((module) => module.default));

window.Alpine = Alpine;

/*
 * Row "kebab" action menu. The menu is rendered position:fixed so it escapes the
 * table's overflow-x-auto clip, and is anchored to its trigger on open — flipping
 * upward when a bottom row has no room below.
 */
Alpine.data('kebabMenu', () => ({
    open: false,
    coords: 'top:-9999px;left:-9999px',

    init() {
        this.$watch('open', (isOpen) => {
            if (isOpen) {
                this.$nextTick(() => this.reposition());
            }
        });
    },

    reposition() {
        const trigger = this.$el.querySelector('button');
        const menu = this.$el.querySelector('[data-kebab-menu]');

        if (!trigger || !menu) {
            return;
        }

        const btn = trigger.getBoundingClientRect();
        const width = menu.offsetWidth || 176;
        const height = menu.offsetHeight || 200;
        const gap = 6;

        let left = Math.max(8, btn.right - width);
        let top = btn.bottom + gap;

        // Flip above the trigger when there isn't room below.
        if (top + height > window.innerHeight - 8) {
            top = Math.max(8, btn.top - height - gap);
        }

        this.coords = `top:${top}px;left:${left}px`;
    },
}));

/*
 * Topbar notification bell. Polls a lightweight JSON feed so the unread badge and
 * the dropdown list stay current without a full page reload, and marks items read
 * in place. Endpoints and the initial count are provided via the root element's
 * data-* attributes so this stays decoupled from Blade.
 */
Alpine.data('notificationBell', (initialUnread = 0) => ({
    open: false,
    unread: initialUnread,
    items: [],
    loaded: false,
    pollMs: 20000,
    timer: null,

    init() {
        this.feedUrl = this.$el.dataset.feedUrl;
        this.markAllUrl = this.$el.dataset.markAllUrl;
        this.csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        this.refresh();
        this.startPolling();
        this.subscribeRealtime();

        // Don't poll a hidden tab; refresh immediately when it becomes visible again.
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.refresh();
                this.startPolling();
            }
        });
    },

    startPolling() {
        if (!this.timer) {
            this.timer = setInterval(() => this.refresh(), this.pollMs);
        }
    },

    /*
     * Real-time ready: when a broadcast driver (e.g. Reverb) is wired up and Laravel Echo
     * is present, subscribe to the private per-admin channel and refresh the feed the
     * instant a notification lands. With no Echo on the page this is a silent no-op and
     * the polling loop above remains the delivery mechanism — so flipping on websockets
     * later needs no change here.
     */
    subscribeRealtime() {
        const adminId = this.$el.dataset.adminId;

        if (!window.Echo || !adminId) {
            return;
        }

        try {
            window.Echo.private(`admin.${adminId}`).listen('.notification.created', () => this.refresh());
        } catch (e) {
            // Echo present but misconfigured — polling still covers us.
        }
    },

    stopPolling() {
        clearInterval(this.timer);
        this.timer = null;
    },

    async refresh() {
        try {
            const res = await fetch(this.feedUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) {
                return;
            }
            const data = await res.json();
            this.unread = data.unread_count;
            this.items = data.notifications;
            this.loaded = true;
        } catch (e) {
            // Network blip — keep the last known state and try again next tick.
        }
    },

    get badge() {
        return this.unread > 9 ? '9+' : String(this.unread);
    },

    // Linked items navigate on click; fire-and-forget the read call with keepalive
    // so it survives the navigation. Non-linked items just update in place.
    markRead(item) {
        if (item.is_read) {
            return;
        }
        item.is_read = true;
        this.unread = Math.max(0, this.unread - 1);

        fetch(item.mark_url, {
            method: 'PATCH',
            keepalive: true,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
        }).catch(() => {});
    },

    async markAllRead() {
        if (this.unread === 0) {
            return;
        }
        this.unread = 0;
        this.items = this.items.map((item) => ({ ...item, is_read: true }));

        try {
            await fetch(this.markAllUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
            });
        } catch (e) {
            // Optimistic update already applied; the next poll reconciles if it failed.
        }
    },
}));

/*
 * Reusable export dialog (rendered by <x-admin.export-button>). Reads the current URL's
 * filters so an export matches the listing, lets the admin pick format / scope / columns,
 * then either downloads immediately (small, synchronous exports) or shows a live progress
 * bar while polling a queued export until its file is ready.
 */
Alpine.data('exportDialog', (config) => ({
    open: false,
    loading: false,
    title: '',
    columns: {},
    selectedColumns: [],
    counts: { filtered: 0, all: 0 },
    format: 'csv',
    scope: 'filtered',
    phase: 'idle', // idle | working | done | error
    progress: 0,
    progressLabel: 'Preparing your export…',
    downloadUrl: null,
    error: '',
    ids: [],
    pollTimer: null,

    get csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    get formatOptions() {
        return [
            { value: 'csv', label: 'CSV' },
            { value: 'xlsx', label: 'Excel' },
            { value: 'pdf', label: 'PDF' },
            { value: 'print', label: 'Print' },
        ];
    },

    get scopeOptions() {
        const opts = [];
        if (config.selectable && this.ids.length) {
            opts.push({ value: 'selected', label: 'Selected rows', count: this.ids.length });
        }
        opts.push({ value: 'page', label: 'Current page', count: '' });
        opts.push({ value: 'filtered', label: 'All matching filters', count: this.counts.filtered });
        opts.push({ value: 'all', label: 'Everything', count: this.counts.all });
        return opts;
    },

    get allColumnsSelected() {
        const total = Object.keys(this.columns).length;
        return total > 0 && this.selectedColumns.length === total;
    },

    filters() {
        return window.location.search.replace(/^\?/, '');
    },

    collectIds() {
        return Array.from(document.querySelectorAll('[data-export-id]:checked')).map((el) => el.value);
    },

    async openDialog() {
        this.phase = 'idle';
        this.downloadUrl = null;
        this.error = '';
        this.ids = this.collectIds();
        this.loading = true;
        this.open = true;
        document.body.style.overflow = 'hidden';

        try {
            const url = this.filters() ? `${config.optionsUrl}?${this.filters()}` : config.optionsUrl;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.title = data.title;
            this.columns = data.columns;
            this.selectedColumns = [...data.default_columns];
            this.counts = data.counts;
            this.scope = config.selectable && this.ids.length ? 'selected' : 'filtered';
        } catch (e) {
            this.error = 'Could not load export options.';
            this.phase = 'error';
        } finally {
            this.loading = false;
        }
    },

    toggleAllColumns() {
        this.selectedColumns = this.allColumnsSelected ? [] : Object.keys(this.columns);
    },

    close() {
        this.open = false;
        document.body.style.overflow = '';
        this.stopPolling();
    },

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    },

    buildQuery() {
        const params = new URLSearchParams(this.filters());
        params.set('scope', this.scope);
        this.selectedColumns.forEach((c) => params.append('columns[]', c));
        this.ids.forEach((id) => params.append('ids[]', id));
        return params.toString();
    },

    submit() {
        if (this.selectedColumns.length === 0) {
            return;
        }

        if (this.format === 'print') {
            window.open(`${config.printUrl}?${this.buildQuery()}`, '_blank');
            this.close();
            return;
        }

        this.startExport();
    },

    async startExport() {
        this.phase = 'working';
        this.progress = 5;
        this.progressLabel = 'Preparing your export…';

        try {
            const url = this.filters() ? `${config.storeUrl}?${this.filters()}` : config.storeUrl;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ format: this.format, scope: this.scope, columns: this.selectedColumns, ids: this.ids }),
            });
            if (!res.ok) {
                throw new Error('Export request failed.');
            }
            this.handleStatus(await res.json());
        } catch (e) {
            this.phase = 'error';
            this.error = e.message || 'Export failed.';
        }
    },

    handleStatus(data) {
        this.progress = data.progress ?? this.progress;

        if (data.status === 'completed') {
            this.finish(data);
        } else if (data.status === 'failed') {
            this.phase = 'error';
            this.error = data.error || 'Export failed.';
            this.stopPolling();
        } else {
            this.progressLabel = data.total_rows
                ? `Processing ${data.processed_rows} of ${data.total_rows} rows…`
                : 'Processing…';
            this.startPolling(data.poll_url);
        }
    },

    startPolling(url) {
        if (this.pollTimer || !url) {
            return;
        }
        this.pollTimer = setInterval(async () => {
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                this.handleStatus(await res.json());
            } catch (e) {
                // Transient failure — the next tick retries.
            }
        }, 1500);
    },

    finish(data) {
        this.stopPolling();
        this.progress = 100;
        this.phase = 'done';
        this.downloadUrl = data.download_url;
        if (data.download_url) {
            window.location.assign(data.download_url);
        }
    },
}));

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    // Load the icon library (lucide) only on pages that render icons — keeps it
    // off the critical path and out of admin, which uses its own SVG icons.
    if (document.querySelector('[data-lucide]')) {
        import('./lucide.js');
    }

    // Load the hero slider chunk (Swiper + GSAP) only on pages that have a hero.
    if (document.getElementById('hero-swiper')) {
        import('./hero.js').then((module) => module.initHeroSlider());
    }

    // Load the scroll-reveal chunk (GSAP + ScrollTrigger) only on pages with reveal sections.
    if (document.querySelector('[data-reveal]')) {
        import('./home-animations.js').then((module) => module.initHomeAnimations());
    }

    // Load the SortableJS chunk only on admin screens that use drag ordering.
    if (document.querySelector('[data-sortable]')) {
        import('./sortables.js').then((module) => module.initSortables());
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('.relative')?.querySelector('[data-password-input]');

            if (!field) {
                return;
            }

            const isVisible = field.type === 'text';
            field.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            button.innerHTML = `<i class="h-4 w-4" data-lucide="${isVisible ? 'eye' : 'eye-off'}"></i>`;
            window.lucide?.createIcons();
        });
    });

    document.querySelectorAll('[data-strength-input]').forEach((field) => {
        const form = field.closest('form');
        const segments = form?.querySelectorAll('[data-strength-meter] span');
        const label = form?.querySelector('[data-strength-text]');

        field.addEventListener('input', () => {
            const value = field.value;
            const score = [value.length >= 4, /[a-z]/i.test(value), /\d/.test(value), /[^a-z\d]/i.test(value)].filter(Boolean).length;
            const messages = ['Use at least 4 characters.', 'Keep going', 'Fair password', 'Strong password', 'Excellent password'];

            segments?.forEach((segment, index) => {
                segment.className = `h-1 rounded-full transition-colors ${index < score ? 'bg-emerald-500' : 'bg-slate-200'}`;
            });

            if (label) {
                label.textContent = messages[score];
                label.className = `text-xs ${score >= 3 ? 'text-emerald-600' : 'text-slate-400'}`;
            }
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin alerts (SweetAlert2)
|--------------------------------------------------------------------------
| Confirmations use `data-confirm` on a form or link; flash messages and
| validation errors are handed over from the Blade layout via window.adminAlert.
*/

const swalTheme = () => {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#181a38' : '#ffffff',
        color: dark ? '#e2e8f0' : '#1e293b',
        confirmButtonColor: '#5352ed',
        cancelButtonColor: dark ? '#33386b' : '#94a3b8',
        customClass: { popup: 'font-admin rounded-2xl' },
    };
};

window.adminConfirm = (options = {}) =>
    loadSwal().then((Swal) =>
        Swal.fire({
            title: options.title ?? 'Are you sure?',
            text: options.text ?? 'This action cannot be undone.',
            icon: options.icon ?? 'warning',
            showCancelButton: true,
            confirmButtonText: options.confirmText ?? 'Yes, continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            ...swalTheme(),
        }),
    );

window.adminToast = (type, message) =>
    loadSwal().then((Swal) =>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            ...swalTheme(),
        }),
    );

/** Destructive-sounding wording gets the danger treatment. */
const isDestructive = (message) => /delete|remove|purge|permanent|trash|block|reject|cancel/i.test(message);

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');

    if (!trigger || trigger.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const message = trigger.dataset.confirm;
    const destructive = isDestructive(message);

    window.adminConfirm({
        title: destructive ? 'Are you sure?' : 'Please confirm',
        text: message,
        icon: destructive ? 'warning' : 'question',
        confirmText: destructive ? 'Yes, proceed' : 'Confirm',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        trigger.dataset.confirmed = 'true';

        if (trigger.tagName === 'FORM') {
            trigger.submit();
        } else if (trigger.tagName === 'A') {
            window.location.href = trigger.href;
        } else {
            trigger.click();
        }
    });
});

/** Submit buttons inside a form that carries data-confirm. */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!form.matches('[data-confirm]') || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    const message = form.dataset.confirm;

    window.adminConfirm({
        title: isDestructive(message) ? 'Are you sure?' : 'Please confirm',
        text: message,
        icon: isDestructive(message) ? 'warning' : 'question',
        confirmText: isDestructive(message) ? 'Yes, proceed' : 'Confirm',
    }).then((result) => {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const alert = window.adminAlert;

    if (!alert) {
        return;
    }

    if (alert.flash) {
        window.adminToast(alert.flash.type, alert.flash.message);
    }

    if (alert.errors?.length) {
        loadSwal().then((Swal) =>
            Swal.fire({
                icon: 'error',
                title: alert.errors.length === 1 ? 'Please fix this' : `Please fix ${alert.errors.length} issues`,
                html: `<ul style="text-align:left;margin:0;padding-left:1.1rem;line-height:1.7">${alert.errors
                    .map((message) => `<li>${message}</li>`)
                    .join('')}</ul>`,
                confirmButtonText: 'Got it',
                ...swalTheme(),
            }),
        );
    }
});

/*
|--------------------------------------------------------------------------
| Public site alerts (SweetAlert2)
|--------------------------------------------------------------------------
| Session flash messages and validation errors are handed over from the
| website master layout via window.siteFlash and shown as toasts styled to
| match the site (Inter font, brand indigo, rounded corners).
*/
const SITE_TOAST_HEADINGS = {
    success: 'Success',
    error: 'Error!',
    warning: 'Warning!',
    info: 'Information',
};

window.siteToast = (type, message) =>
    loadSwal().then((Swal) =>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: SITE_TOAST_HEADINGS[type] ?? '',
            text: message,
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            showCloseButton: true,
            background: '#ffffff',
            color: '#0f172a',
            customClass: {
                popup: `site-swal site-swal--${type}`,
                title: 'site-swal__title',
                htmlContainer: 'site-swal__text',
                timerProgressBar: 'site-swal__bar',
                closeButton: 'site-swal__close',
            },
        }),
    );

document.addEventListener('DOMContentLoaded', () => {
    const flash = window.siteFlash;

    if (!flash) {
        return;
    }

    if (flash.flash) {
        window.siteToast(flash.flash.type, flash.flash.message);
    }

    if (flash.errors?.length) {
        window.siteToast(
            'error',
            flash.errors.length === 1 ? flash.errors[0] : `Please fix ${flash.errors.length} highlighted fields.`,
        );
    }
});

/*
|--------------------------------------------------------------------------
| Admin row-action (kebab) dropdown
|--------------------------------------------------------------------------
| Opens downward, but flips upward when there isn't enough room below the
| trigger — so menus on the last table rows aren't clipped by the table's
| overflow container.
*/
window.kebabMenu = () => ({
    open: false,
    up: false,
    init() {
        this.$watch('open', (isOpen) => {
            if (!isOpen) {
                return;
            }
            this.$nextTick(() => {
                const rect = this.$el.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                // Flip up when the menu (~240px tall) wouldn't fit below the trigger.
                this.up = spaceBelow < 260;
            });
        });
    },
});

/*
|--------------------------------------------------------------------------
| Live search (whole website)
|--------------------------------------------------------------------------
| Any form with data-live-search="<selector>[,<selector>...]" refreshes those
| regions in place instead of reloading the page: text inputs are debounced,
| selects/checkboxes apply immediately, and the URL stays shareable via
| history.replaceState. The server may answer with the full page (regions are
| extracted via DOMParser) or, for single-region forms, with just the region's
| inner fragment. Selects with data-live-param="name" update one query param
| from the current URL (e.g. the properties sort control). Falls back to a
| normal navigation on any fetch failure, and to classic GET submits when JS
| is disabled.
*/
(() => {
    let debounceTimer;
    let controller;

    const regionsFor = (element) =>
        (element.closest('[data-live-search]')?.dataset.liveSearch ?? '')
            .split(',')
            .map((selector) => selector.trim())
            .filter(Boolean);

    const setLoading = (selectors, form, isLoading) => {
        form?.querySelector('[data-search-spinner]')?.classList.toggle('hidden', !isLoading);
        selectors.forEach((selector) => {
            document.querySelector(selector)?.classList.toggle('opacity-40', isLoading);
            document.querySelector(selector)?.classList.toggle('pointer-events-none', isLoading);
        });
    };

    const swap = (url, selectors, form) => {
        controller?.abort();
        controller = new AbortController();
        setLoading(selectors, form, true);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then((response) => response.text())
            .then((html) => {
                const incoming = new DOMParser().parseFromString(html, 'text/html');

                selectors.forEach((selector) => {
                    const current = document.querySelector(selector);
                    const fresh = incoming.querySelector(selector);

                    if (current && fresh) {
                        current.innerHTML = fresh.innerHTML;
                    } else if (current && selectors.length === 1) {
                        // Fragment response (no layout): the body IS the region.
                        current.innerHTML = incoming.body.innerHTML;
                    }
                });

                window.lucide?.createIcons();
                window.history.replaceState({}, '', url);
                setLoading(selectors, form, false);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    window.location.assign(url);
                }
            });
    };

    const submitLive = (form) => {
        const url = new URL(form.action || window.location.href);
        new FormData(form).forEach((value, key) => {
            if (String(value).trim() !== '') {
                url.searchParams.set(key, value);
            }
        });

        swap(url, regionsFor(form), form);
    };

    document.addEventListener('input', (event) => {
        const field = event.target;
        if (!field.matches('form[data-live-search] input:not([type="checkbox"]):not([type="radio"])')) {
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => submitLive(field.form), 350);
    });

    document.addEventListener('change', (event) => {
        const field = event.target;

        if (field.matches('select[data-live-param]')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            field.value === '' ? url.searchParams.delete(field.dataset.liveParam) : url.searchParams.set(field.dataset.liveParam, field.value);
            swap(url, regionsFor(field), field.form);
            return;
        }

        if (field.matches('form[data-live-search] select, form[data-live-search] input[type="checkbox"], form[data-live-search] input[type="radio"]')) {
            clearTimeout(debounceTimer);
            submitLive(field.form);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.matches('form[data-live-search]')) {
            return;
        }

        event.preventDefault();
        clearTimeout(debounceTimer);
        submitLive(form);
    });
})();
