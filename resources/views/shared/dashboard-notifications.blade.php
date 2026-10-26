@extends($layout)
@section('title', 'كل الإشعارات')
@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">كل الإشعارات</h5></div>
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <div class="list-group-item d-flex gap-3 {{ $notification->read_at ? 'marked-as-read' : 'bg-label-primary' }}" data-notification-id="{{ $notification->id }}">
                <div class="flex-grow-1">
                    <a href="{{ $notification->data['url'] ?? '#' }}" data-dashboard-notification-read="{{ $notification->id }}" class="text-body fw-semibold">{{ $notification->data['title'] ?? 'إشعار' }}</a>
                    <p class="mb-1">{{ $notification->data['message'] ?? '' }}</p>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                </div>
                <button type="button" class="btn btn-icon btn-text-secondary" data-dashboard-notification-delete="{{ $notification->id }}" aria-label="حذف الإشعار"><i class="ti ti-x"></i></button>
            </div>
        @empty
            <p class="p-4 mb-0 text-muted">لا توجد إشعارات حاليًا.</p>
        @endforelse
    </div>
    <div class="card-footer">{{ $notifications->links() }}</div>
</div>
@endsection
