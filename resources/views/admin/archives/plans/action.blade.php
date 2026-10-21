<div class="d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-label-success js-restore-btn" data-url="{{ route('admin.archives.plans.restore', $id) }}" title="استرجاع الباقة">
        <i class="ti ti-rotate-clockwise"></i>
    </button>
    <button type="button" class="btn btn-icon btn-label-danger js-force-delete-btn" data-url="{{ route('admin.archives.plans.force-delete', $id) }}" title="حذف نهائي">
        <i class="ti ti-trash-x"></i>
    </button>
</div>
