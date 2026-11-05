@extends($layout)
@section('title', 'كل الإشعارات')

@php
    $noticeRoute = fn($name, $parameters = []) => ($scope ?? 'admin') === 'admin'
        ? route('admin.dashboard-notifications.' . $name, $parameters)
        : tenant_route_name('dashboard-notifications.' . $name, $parameters);

    $notificationTabs = [
        [
            'label' => 'الكل',
            'icon' => 'ti ti-bell',
            'url' => $noticeRoute('index', ['filter' => 'all']),
            'active' => ($currentFilter ?? 'all') === 'all',
            'count' => $totalCount ?? 0,
            'countAttributes' => ['data-filter-all-count' => true, 'aria-live' => 'polite'],
        ],
        [
            'label' => 'غير المقروءة',
            'icon' => 'ti ti-mail',
            'url' => $noticeRoute('index', ['filter' => 'unread']),
            'active' => ($currentFilter ?? 'all') === 'unread',
            'count' => $unreadCount ?? 0,
            'countAttributes' => ['data-filter-unread-count' => true, 'aria-live' => 'polite'],
        ],
        [
            'label' => 'المقروءة',
            'icon' => 'ti ti-mail-opened',
            'url' => $noticeRoute('index', ['filter' => 'read']),
            'active' => ($currentFilter ?? 'all') === 'read',
            'count' => $readCount ?? 0,
            'countAttributes' => ['data-filter-read-count' => true, 'aria-live' => 'polite'],
        ],
    ];
@endphp

@section('content')
    <div class="card" data-notification-page data-notification-filter="{{ $currentFilter }}"
        data-feed-url="{{ $noticeRoute('feed') }}" data-read-url="{{ $noticeRoute('read', ['notification' => '__ID__']) }}"
        data-delete-url="{{ $noticeRoute('destroy', ['notification' => '__ID__']) }}"
        data-read-all-url="{{ $noticeRoute('read-all') }}">
        <div class="card-header border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h5 class="mb-0 fw-bold">كل الإشعارات</h5>

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <x-shared.ui.tabs :items="$notificationTabs" aria-label="فلاتر الإشعارات" />
                    <button type="button" class="btn btn-label-primary d-flex align-items-center gap-1"
                        data-notification-read-all id="btn-mark-all-read" @if (($unreadCount ?? 0) === 0) disabled @endif>
                        <i class="ti ti-mail-opened"></i>
                        <span>تحديد الكل كمقروء</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $type = $data['type'] ?? 'info';
                    $icon = $data['icon'] ?? 'ti ti-bell';
                    if (!preg_match('/^ti ti-[a-z0-9-]+$/', (string) $icon)) {
                        $icon = 'ti ti-bell';
                    }
                    $typeColor = match ($type) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        'primary' => 'primary',
                        default => 'info',
                    };
                    $targetUrl = !empty($data['url']) && $data['url'] !== '#' ? $data['url'] : '';
                @endphp
                <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 p-3 {{ $notification->read_at ? 'marked-as-read' : 'bg-label-primary' }}"
                    data-notification-id="{{ $notification->id }}"
                    @if ($targetUrl) data-target-url="{{ $targetUrl }}" style="cursor: pointer;" @endif>
                    <span class="avatar-initial rounded-circle bg-label-{{ $typeColor }} p-2 flex-shrink-0">
                        <i class="{{ $icon }} fs-5"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <a href="{{ $targetUrl ?: '#' }}" data-dashboard-notification-read="{{ $notification->id }}"
                                class="text-body fw-semibold text-decoration-none">
                                {{ $data['title'] ?? 'إشعار' }}
                            </a>
                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        @if (!empty($data['message']))
                            <p class="mb-1 text-muted small">{{ $data['message'] }}</p>
                        @endif
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-text-secondary rounded-pill flex-shrink-0"
                        data-dashboard-notification-delete="{{ $notification->id }}" title="حذف الإشعار"
                        aria-label="حذف الإشعار">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="mb-3">
                        <span class="avatar-initial rounded-circle bg-label-primary p-3 d-inline-flex">
                            <i class="ti ti-bell-off fs-2"></i>
                        </span>
                    </div>
                    <h6 class="text-muted mb-0">لا توجد إشعارات حاليًا.</h6>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer border-top py-3">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
