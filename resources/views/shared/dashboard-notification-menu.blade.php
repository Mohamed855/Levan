@php
    $noticeRoute = fn($name, $parameters = []) => $scope === 'admin'
        ? route('admin.dashboard-notifications.' . $name, $parameters)
        : tenant_route_name('dashboard-notifications.' . $name, $parameters);
@endphp
<li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2 me-xl-2 js-dashboard-notifications"
    data-feed-url="{{ $noticeRoute('feed') }}" data-auth-url="{{ $noticeRoute('auth') }}"
    data-read-url="{{ $noticeRoute('read', ['notification' => '__ID__']) }}"
    data-delete-url="{{ $noticeRoute('destroy', ['notification' => '__ID__']) }}"
    data-read-all-url="{{ $noticeRoute('read-all') }}">
    <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow" href="#"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="الإشعارات"
        id="notificationDropdown">
        <span class="position-relative"><i class="ti ti-bell ti-md"></i>
            <span class="badge badge-dot badge-notifications d-none" data-notification-dot></span>
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width:min(350px, 95vw);">
        <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
                <h6 class="mb-0 me-auto fw-bold">الإشعارات</h6>
                <span class="badge bg-primary me-2" id="unreadCountBadge" data-notification-count aria-live="polite">0
                    جديد</span>
                <button type="button" class="btn btn-text-secondary rounded-pill btn-icon" data-notification-read-all
                    title="تحديد الكل كمقروء" aria-label="تحديد الكل كمقروء"><i class="ti ti-mail-opened"></i></button>
            </div>
        </li>
        <li class="dropdown-notifications-list scrollable-container overflow-auto" style="max-height:350px;">
            <ul class="list-group list-group-flush" id="notificationsList" data-notification-items>
                <li class="list-group-item text-center text-muted">جارٍ تحميل الإشعارات…</li>
            </ul>
        </li>
        <li class="border-top">
            <div class="d-grid p-3"><a class="btn btn-primary btn-sm" href="{{ $noticeRoute('index') }}">عرض كل
                    الإشعارات</a></div>
        </li>
    </ul>
</li>
