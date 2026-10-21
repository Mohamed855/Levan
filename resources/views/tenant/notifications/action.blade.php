<div class="d-flex justify-content-center gap-1">
    @if(!$row->read_at)
        <button type="button" class="btn btn-icon btn-sm btn-label-success" onclick="markAsRead('{{ $id }}')" title="تحديد كمقروء">
            <i class="ti ti-check"></i>
        </button>
    @endif
    <button type="button" class="btn btn-icon btn-sm btn-label-danger" onclick="deleteNotification('{{ $id }}')" title="حذف">
        <i class="ti ti-trash"></i>
    </button>
</div>
