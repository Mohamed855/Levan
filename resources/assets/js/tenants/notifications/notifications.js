// resources/js/notifications.js

// console.log('🔄 Loading notifications.js...');

document.addEventListener('DOMContentLoaded', function() {
    // console.log('🔄 DOM Loaded - Initializing notifications system...');
    
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    
    // console.log('🔍 Checking requirements:', {
    //     'user-id meta found': !!userIdMeta,
    //     'user-id value': userIdMeta?.content,
    //     'csrf meta found': !!csrfMeta,
    //     'Echo available': !!window.Echo,
    //     'Pusher available': !!window.Pusher,
    //     'Routes available': !!window.routes
    // });
    
    // if (!window.Echo) {
    //     console.error('❌ Laravel Echo is not initialized!');
    //     return;
    // }
    
    if (!userIdMeta || !userIdMeta.content) {
        // console.error('❌ User ID meta tag not found or empty!');
        return;
    }
    
    // 👇 تحقق من وجود Routes
    if (!window.routes) {
        // console.error('❌ Routes not configured! Add window.routes in layout.');
        return;
    }
    
    const userId = userIdMeta.content;
    const channelName = `notifications.${userId}`;
    
    // console.log('✅ All checks passed!');
    // console.log('🔔 Subscribing to channel:', channelName);
    
    updateNotificationCount();
    
    try {
        window.Echo.private(channelName)
            .notification((notification) => {
                // console.log('📩 New notification received:', notification);
                updateNotificationCount();
                addNotificationToList(notification);
            })
            .error((error) => {
                // console.error('❌ Channel subscription error:', error);
            });
        
        // console.log('✅ Successfully subscribed to notifications channel');
        
    } catch (error) {
        // console.error('❌ Error setting up Echo:', error);
    }
    
    if (window.Echo.connector?.pusher?.connection) {
        window.Echo.connector.pusher.connection.bind('state_change', function(states) {
            // console.log('📡 Pusher state changed:', states.current);
        });
    }
});

// تحديث عدد الإشعارات
function updateNotificationCount() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        // console.error('❌ CSRF token not found');
        return;
    }
    
    // 👇 استخدم الـ route من window.routes
    const url = window.routes?.notificationsUnreadCount || '/notifications/unread-count';
    
    // console.log('🔍 Fetching from:', url);
    
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        // console.log('📡 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // console.log('✅ Notification count:', data.count);
        
        const badge = document.querySelector('.badge-notifications');
        const countBadge = document.getElementById('unreadCountBadge');

        if (data.count > 0) {
            if (badge) badge.style.display = 'block';
            if (countBadge) countBadge.textContent = `${data.count} جديد`;
        } else {
            if (badge) badge.style.display = 'none';
            if (countBadge) countBadge.textContent = '0 جديد';
        }
    })
    .catch(error => {
        // console.error('❌ Error updating notification count:', error);
    });
}

// إضافة إشعار للقائمة
function addNotificationToList(notification) {
    const list = document.getElementById('notificationsList');
    if (!list) {
        // console.warn('⚠️ Notifications list element not found');
        return;
    }

    const emptyMessage = list.querySelector('.text-center.py-4');
    if (emptyMessage) {
        emptyMessage.parentElement.remove();
    }

    const iconColors = {
        'success': 'bg-label-success',
        'warning': 'bg-label-warning',
        'info': 'bg-label-info',
        'danger': 'bg-label-danger',
        'primary': 'bg-label-primary',
        'secondary': 'bg-label-secondary'
    };

    const colorClass = iconColors[notification.type] || 'bg-label-info';
    const icon = notification.icon || 'ti ti-bell';
    const title = notification.title || 'إشعار';
    const message = notification.message || '';
    const time = notification.created_at || 'الآن';

    const notificationHTML = `
        <li class="list-group-item list-group-item-action dropdown-notifications-item"
            data-notification-id="${notification.id}">
            <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded-circle ${colorClass}">
                            <i class="${icon}"></i>
                        </span>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h6 class="small mb-1 fw-semibold">${title}</h6>
                    <p class="mb-1 small text-muted">${message}</p>
                    <small class="text-muted">${time}</small>
                </div>
                <div class="flex-shrink-0 dropdown-notifications-actions">
                    <span class="badge badge-dot bg-primary"></span>
                    <a href="javascript:void(0)" onclick="deleteNotification('${notification.id}')"
                       class="dropdown-notifications-archive ms-2">
                        <span class="ti ti-x"></span>
                    </a>
                </div>
            </div>
        </li>
    `;

    list.insertAdjacentHTML('afterbegin', notificationHTML);

    const items = list.querySelectorAll('li');
    if (items.length > 10) {
        items[items.length - 1].remove();
    }
}

// تحديد الكل كمقروء
window.markAllAsRead = function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    // 👇 استخدم الـ route من window.routes
    const url = window.routes?.notificationsMarkAllRead || '/notifications/mark-all-read';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.querySelectorAll('.dropdown-notifications-item').forEach(item => {
            item.classList.add('marked-as-read');
            const dot = item.querySelector('.badge-dot');
            if (dot) dot.remove();
        });
        updateNotificationCount();
    })
    // .catch(error => console.error('❌ Error marking all as read:', error));
};

// حذف إشعار
window.deleteNotification = function(id) {
    if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    // 👇 استخدم الـ base route من window.routes
    const baseUrl = window.routes?.notificationsDelete || '/notifications';
    const url = `${baseUrl}/${id}`;

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const item = document.querySelector(`[data-notification-id="${id}"]`);
        if (item) {
            item.style.transition = 'opacity 0.3s';
            item.style.opacity = '0';
            setTimeout(() => item.remove(), 300);
        }

        updateNotificationCount();

        const list = document.getElementById('notificationsList');
        if (list && list.children.length === 0) {
            list.innerHTML = `
                <li class="list-group-item text-center py-4">
                    <i class="ti ti-bell-off ti-lg text-muted mb-2"></i>
                    <p class="text-muted mb-0">لا توجد إشعارات</p>
                </li>
            `;
        }
    })
    // .catch(error => console.error('❌ Error deleting notification:', error));
};

// window.showToast = function(title, message) {
//     console.log(`🔔 ${title}: ${message}`);
// };