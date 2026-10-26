import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.js-dashboard-notifications');
    if (!root) return;
    const list = root.querySelector('[data-notification-items]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
    let echo;
    let loading = false;
    let refreshPending = false;
    let connected = false;
    let initialized = false;
    let stopped = false;
    const seen = new Set();
    const color = (type) => ['success', 'warning', 'danger', 'primary', 'secondary', 'info'].includes(type) ? type : 'info';
    const safeUrl = (url) => {
        try {
            const target = new URL(url || '#', window.location.origin);
            return target.origin === window.location.origin ? target.href : '#';
        } catch { return '#'; }
    };
    const node = (tag, classes, text) => {
        const element = document.createElement(tag);
        element.className = classes;
        if (text !== undefined) element.textContent = text;
        return element;
    };
    const render = (items, count) => {
        root.querySelector('[data-notification-count]').textContent = `${count} جديد`;
        root.querySelector('[data-notification-dot]').classList.toggle('d-none', count === 0);
        list.replaceChildren();
        if (!items.length) list.append(node('li', 'list-group-item text-center text-muted', 'لا توجد إشعارات حاليًا'));
        items.forEach((notice) => {
            const data = notice.data || {};
            const row = node('li', `list-group-item list-group-item-action dropdown-notifications-item ${notice.read_at ? 'marked-as-read' : ''}`);
            row.dataset.notificationId = notice.id;
            const flex = node('div', 'd-flex gap-2 align-items-start');
            const icon = node('span', `avatar-initial rounded-circle bg-label-${color(data.type)} p-2`);
            const glyph = node('i', /^ti ti-[a-z0-9-]+$/.test(data.icon || '') ? data.icon : 'ti ti-bell');
            icon.append(glyph);
            const content = node('div', 'flex-grow-1');
            const link = node('a', 'small text-body fw-semibold d-block', data.title || 'إشعار');
            link.href = safeUrl(data.url);
            link.dataset.dashboardNotificationRead = notice.id;
            content.append(link, node('p', 'mb-1 small text-muted', data.message || ''), node('small', 'text-muted', notice.created_at));
            const remove = node('button', 'btn btn-sm btn-text-secondary btn-icon', '×');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'حذف الإشعار');
            remove.dataset.dashboardNotificationDelete = notice.id;
            flex.append(icon, content, remove);
            row.append(flex);
            list.append(row);
            if (initialized && !seen.has(notice.id) && !notice.read_at && window.toastr) {
                window.toastr.info(data.title || 'إشعار جديد', undefined, { escapeHtml: true });
            }
            seen.add(notice.id);
        });
        initialized = true;
    };
    const subscribe = (config) => {
        if (echo || !config?.key) return;
        echo = new Echo({
            broadcaster: 'pusher', client: new Pusher(config.key, {
                cluster: 'mt1', wsHost: config.host, wsPort: config.port, wssPort: config.port,
                forceTLS: config.scheme === 'https', enabledTransports: ['ws', 'wss'], disableStats: true,
                authEndpoint: root.dataset.authUrl, auth: { headers },
            }),
        });
        echo.private(config.channel).listen('.dashboard.notifications.changed', refresh).subscribed(refresh);
        const connection = echo.connector.pusher.connection;
        connection.bind('connected', () => { connected = true; refresh(); });
        connection.bind('disconnected', () => { connected = false; });
        connection.bind('unavailable', () => { connected = false; });
        connection.bind('error', () => { connected = false; });
    };
    async function refresh() {
        if (stopped) return;
        if (loading) { refreshPending = true; return; }
        loading = true;
        try {
            const response = await fetch(root.dataset.feedUrl, { headers, credentials: 'same-origin', cache: 'no-store' });
            if ([401, 403, 419].includes(response.status)) { stopped = true; echo?.disconnect(); return; }
            if (!response.ok) return;
            const data = await response.json();
            render(data.items || [], Number(data.count || 0));
            subscribe(data.broadcast);
        } catch { /* The stored feed is retried after reconnect or at the fallback interval. */ }
        finally {
            loading = false;
            if (refreshPending && !stopped) { refreshPending = false; queueMicrotask(refresh); }
        }
    }
    async function mutate(url, method = 'POST') {
        const response = await fetch(url, { method, headers, credentials: 'same-origin' });
        if (!response.ok) throw new Error('تعذر تحديث الإشعار');
        await refresh();
    }
    document.addEventListener('click', async (event) => {
        const remove = event.target.closest('[data-dashboard-notification-delete]');
        const read = event.target.closest('[data-dashboard-notification-read]');
        const all = event.target.closest('[data-notification-read-all]');
        if (!remove && !read && !all) return;
        event.preventDefault();
        try {
            if (remove) {
                await mutate(root.dataset.deleteUrl.replace('__ID__', encodeURIComponent(remove.dataset.dashboardNotificationDelete)), 'DELETE');
                remove.closest('[data-notification-id]')?.remove();
            } else if (all) {
                await mutate(root.dataset.readAllUrl);
                document.querySelectorAll('[data-notification-id]').forEach((row) => { row.classList.add('marked-as-read'); row.classList.remove('bg-label-primary'); });
            } else {
                await mutate(root.dataset.readUrl.replace('__ID__', encodeURIComponent(read.dataset.dashboardNotificationRead)));
                const target = safeUrl(read.href);
                if (target !== new URL('#', location.href).href) location.assign(target);
            }
        } catch { window.toastr?.error('تعذر تحديث الإشعار. يرجى المحاولة مرة أخرى.'); }
    });
    window.updateNotificationCount = refresh;
    window.markAllAsRead = () => mutate(root.dataset.readAllUrl);
    window.deleteNotification = (id) => mutate(root.dataset.deleteUrl.replace('__ID__', encodeURIComponent(id)), 'DELETE');
    root.addEventListener('show.bs.dropdown', refresh);
    window.addEventListener('online', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    const timer = setInterval(() => { if (!document.hidden && !connected) refresh(); }, 5000);
    const sync = setInterval(() => { if (!document.hidden) refresh(); }, 60000);
    window.addEventListener('pagehide', () => { connected = false; echo?.disconnect(); });
    window.addEventListener('pageshow', (event) => { if (event.persisted) { echo?.connector.pusher.connect(); refresh(); } });
    refresh();
});
