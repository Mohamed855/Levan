@php
    // التأكد من وجود المتغيرات المطلوبة
    if (!isset($id)) {
        $id = '';
    }
    
    if (!isset($notification)) {
        $notification = null;
    }
    
    // إذا كان هناك كائن إشعار، استخدم معرفه
    if ($notification && isset($notification->id)) {
        $id = $notification->id;
    }
@endphp

<div class="btn-group gap-1">
    @if (!$notification || !$notification->read_at)
        <button type="button" class="btn btn-icon btn-primary bg-primary js-mark-read-btn" 
                data-id="{{ $id }}" 
                title="تحديد كمقروء"
                aria-label="تحديد كمقروء">
            <i class="ti ti-check"></i>
        </button>
    @endif
    
    <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-notification-btn" 
            data-id="{{ $id }}" 
            title="حذف"
            aria-label="حذف">
        <i class="ti ti-trash"></i>
    </button>
</div>