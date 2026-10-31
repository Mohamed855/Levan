import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.js-dashboard-notifications') || document.querySelector('[data-feed-url]');
    if (!root && !document.querySelector('[data-notification-id]')) return;

    const list = root?.querySelector('[data-notification-items]');
    const page = document.querySelector('[data-notification-page]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
    let echo;
    let loading = false;
    let refreshPending = false;
    let connected = false;
    let initialized = false;
    let stopped = false;
    let unreadCount = null;
    const seen = new Set();
    const color = (type) => ['success', 'warning', 'danger', 'primary', 'secondary', 'info'].includes(type) ? type : 'info';

    const safeUrl = (url) => {
        if (!url || typeof url !== 'string') return '';
        const trimmed = url.trim();
        if (!trimmed || trimmed === '#' || trimmed.startsWith('javascript:')) return '';
        try {
            const target = new URL(trimmed, window.location.origin);
            if (target.protocol !== 'http:' && target.protocol !== 'https:') {
                return '';
            }
            if (trimmed.startsWith('/')) {
                return target.href;
            }
            const currentHost = window.location.hostname;
            const targetHost = target.hostname;
            if (
                targetHost === currentHost ||
                targetHost.endsWith('.' + currentHost) ||
                currentHost.endsWith('.' + targetHost) ||
                (targetHost.includes('localhost') && currentHost.includes('localhost')) ||
                (targetHost === '127.0.0.1' && currentHost === '127.0.0.1')
            ) {
                return target.href;
            }
            if (target.origin === window.location.origin) {
                return target.href;
            }
            return target.href;
        } catch {
            return '';
        }
    };

    const getEndpoint = (attr, id = null) => {
        const datasetKey = attr;
        const el = root?.dataset?.[datasetKey] ? root : document.querySelector(`[data-${attr.replace(/([A-Z])/g, '-$1').toLowerCase()}]`);
        const template = el?.dataset?.[datasetKey];
        if (!template) return '';
        return id !== null ? template.replace('__ID__', encodeURIComponent(id)) : template;
    };

    const node = (tag, classes, text) => {
        const element = document.createElement(tag);
        element.className = classes;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    const render = (items, count) => {
        unreadCount = count;
        const countBadge = document.querySelector('[data-notification-count]');
        if (countBadge) countBadge.textContent = `${count} جديد`;
        const dot = document.querySelector('[data-notification-dot]');
        if (dot) dot.classList.toggle('d-none', count === 0);
        document.querySelectorAll('[data-notification-read-all]').forEach((button) => {
            if (button.getAttribute('aria-busy') !== 'true') button.disabled = count === 0;
        });

        if (!list) return;
        list.replaceChildren();
        if (!items.length) {
            list.append(node('li', 'list-group-item text-center text-muted', 'لا توجد إشعارات حاليًا'));
            return;
        }

        items.forEach((notice) => {
            const data = notice.data || {};
            const targetUrl = safeUrl(data.url);
            const row = node('li', `list-group-item list-group-item-action dropdown-notifications-item ${notice.read_at ? 'marked-as-read' : ''}`);
            row.dataset.notificationId = notice.id;
            if (targetUrl) {
                row.dataset.targetUrl = targetUrl;
                row.style.cursor = 'pointer';
            }
            const flex = node('div', 'd-flex gap-2 align-items-start');
            const icon = node('span', `avatar-initial rounded-circle bg-label-${color(data.type)} p-2`);
            const glyph = node('i', /^ti ti-[a-z0-9-]+$/.test(data.icon || '') ? data.icon : 'ti ti-bell');
            icon.append(glyph);
            const content = node('div', 'flex-grow-1');
            const link = node('a', 'small text-body fw-semibold d-block', data.title || 'إشعار');
            link.href = targetUrl || '#';
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

    const updateSidebarCounters = (counts) => {
        if (!counts || typeof counts !== 'object') return;
        Object.entries(counts).forEach(([key, val]) => {
            const num = Number(val) || 0;
            const badges = document.querySelectorAll(`[data-sidebar-counter="${key}"]`);
            badges.forEach((badge) => {
                const prev = Number(badge.textContent.trim()) || 0;
                badge.textContent = num;
                if (num > 0) {
                    const wasHidden = badge.classList.contains('d-none');
                    badge.classList.remove('d-none');
                    if (wasHidden || num !== prev) {
                        badge.classList.remove('counter-pulse');
                        void badge.offsetWidth;
                        badge.classList.add('counter-pulse');
                        setTimeout(() => badge.classList.remove('counter-pulse'), 700);
                    }
                } else {
                    badge.classList.add('d-none');
                }
            });
        });
    };

    const subscribe = (config) => {
        if (echo || !config?.key) return;
        echo = new Echo({
            broadcaster: 'pusher', client: new Pusher(config.key, {
                cluster: 'mt1', wsHost: config.host, wsPort: config.port, wssPort: config.port,
                forceTLS: config.scheme === 'https', enabledTransports: ['ws', 'wss'], disableStats: true,
                authEndpoint: root?.dataset?.authUrl, auth: { headers },
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
        if (stopped || !root?.dataset?.feedUrl) return;
        if (loading) { refreshPending = true; return; }
        loading = true;
        try {
            const response = await fetch(root.dataset.feedUrl, { headers, credentials: 'same-origin', cache: 'no-store' });
            if ([401, 403, 419].includes(response.status)) { stopped = true; echo?.disconnect(); return; }
            if (!response.ok) return;
            const data = await response.json();
            if (data.counts) {
                Object.entries(data.counts).forEach(([filter, count]) => {
                    if (!['all', 'unread', 'read'].includes(filter)) return;
                    document.querySelectorAll(`[data-filter-${filter}-count]`).forEach((badge) => {
                        badge.textContent = String(count);
                    });
                });
            }
            render(data.items || [], Number(data.count || 0));
            if (data.sidebar_counts) {
                updateSidebarCounters(data.sidebar_counts);
            }
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
        if (root?.dataset?.feedUrl) {
            await refresh();
        }
    }

    const refreshFilteredPage = () => {
        if (page && page.dataset.notificationFilter !== 'all') window.location.reload();
    };

    document.addEventListener('click', async (event) => {
        const remove = event.target.closest('[data-dashboard-notification-delete]');
        const all = event.target.closest('[data-notification-read-all]');
        const item = event.target.closest('[data-notification-id]');
        const readLink = event.target.closest('[data-dashboard-notification-read]');

        // 1. Delete single notification
        if (remove) {
            event.preventDefault();
            event.stopPropagation();
            const noticeId = remove.dataset.dashboardNotificationDelete;
            const deleteUrl = getEndpoint('deleteUrl', noticeId);
            if (deleteUrl) {
                try {
                    await mutate(deleteUrl, 'DELETE');
                    remove.closest('[data-notification-id]')?.remove();
                    refreshFilteredPage();
                } catch {
                    window.toastr?.error('تعذر حذف الإشعار. يرجى المحاولة مرة أخرى.');
                }
            }
            return;
        }

        // 2. Mark all notifications as read
        if (all) {
            event.preventDefault();
            if (all.getAttribute('aria-busy') === 'true') return;
            const readAllUrl = getEndpoint('readAllUrl');
            if (readAllUrl) {
                all.disabled = true;
                all.setAttribute('aria-busy', 'true');
                try {
                    await mutate(readAllUrl);
                    document.querySelectorAll('[data-notification-id]').forEach((row) => {
                        row.classList.add('marked-as-read');
                        row.classList.remove('bg-label-primary');
                    });
                    window.toastr?.success('تم تحديد جميع الإشعارات كمقروءة');
                    refreshFilteredPage();
                } catch {
                    window.toastr?.error('تعذر تحديث الإشعارات. يرجى المحاولة مرة أخرى.');
                } finally {
                    all.removeAttribute('aria-busy');
                    all.disabled = unreadCount === 0;
                }
            }
            return;
        }

        // 3. Notification item clicked (full card, title, icon, timestamp, or message)
        if (item || readLink) {
            const row = item || readLink.closest('[data-notification-id]');
            const noticeId = readLink?.dataset.dashboardNotificationRead || row?.dataset.notificationId;
            const rawUrl = row?.dataset.targetUrl || readLink?.getAttribute('href') || row?.querySelector('a[data-dashboard-notification-read]')?.getAttribute('href') || row?.querySelector('a')?.getAttribute('href');
            const target = safeUrl(rawUrl);

            const readUrl = getEndpoint('readUrl', noticeId);

            // If a valid URL is linked, navigate immediately
            if (target) {
                const opensNewTab = event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1;
                if (readUrl) {
                    fetch(readUrl, {
                        method: 'POST', headers, credentials: 'same-origin', keepalive: true,
                    }).then(async (response) => {
                        if (!response.ok) return;
                        row?.classList.add('marked-as-read');
                        row?.classList.remove('bg-label-primary');
                        await refresh();
                        if (opensNewTab) refreshFilteredPage();
                    }).catch(() => {});
                }
                if (opensNewTab) {
                    if (event.target.tagName.toLowerCase() !== 'a') {
                        event.preventDefault();
                        window.open(target, '_blank');
                    }
                    return;
                }
                event.preventDefault();
                window.location.assign(target);
                return;
            }

            event.preventDefault();
            if (readUrl) {
                try {
                    await mutate(readUrl);
                    row?.classList.add('marked-as-read');
                    row?.classList.remove('bg-label-primary');
                    refreshFilteredPage();
                } catch {
                    window.toastr?.error('تعذر تحديث الإشعار. يرجى المحاولة مرة أخرى.');
                }
            }
        }
    });

    window.updateNotificationCount = refresh;
    window.markAllAsRead = () => {
        const url = getEndpoint('readAllUrl');
        return url ? mutate(url) : Promise.resolve();
    };
    window.deleteNotification = (id) => {
        const url = getEndpoint('deleteUrl', id);
        return url ? mutate(url, 'DELETE') : Promise.resolve();
    };

    if (root) {
        root.addEventListener('show.bs.dropdown', refresh);
    }
    window.addEventListener('online', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    const timer = setInterval(() => { if (!document.hidden && !connected) refresh(); }, 5000);
    const sync = setInterval(() => { if (!document.hidden) refresh(); }, 60000);
    window.addEventListener('pagehide', () => { connected = false; echo?.disconnect(); });
    window.addEventListener('pageshow', (event) => { if (event.persisted) { echo?.connector.pusher.connect(); refresh(); } });

    if (root?.dataset?.feedUrl) {
        refresh();
    }
});
