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
        <button type="button" class="btn btn-icon btn-label-primary bg-label-primary" 
                onclick="markAsRead('{{ $id }}')" 
                title="تحديد كمقروء">
            <i class="ti ti-check"></i>
        </button>
    @endif
    
    <button type="button" class="btn btn-icon btn-label-primary bg-label-primary" 
            onclick="deleteNotification('{{ $id }}')" 
            title="حذف">
        <i class="ti ti-trash"></i>
    </button>
</div>