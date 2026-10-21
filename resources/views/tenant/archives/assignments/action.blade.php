<div class="d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-sm btn-label-success js-restore-btn" data-url="{{ route('tenant.archives.assignments.restore', $id) }}" title="استرجاع الإسناد">
        <i class="ti ti-rotate-clockwise"></i>
    </button>
    <button type="button" class="btn btn-icon btn-sm btn-label-danger js-force-delete-btn" data-url="{{ route('tenant.archives.assignments.force-delete', $id) }}" title="حذف نهائي">
        <i class="ti ti-trash-x"></i>
    </button>
</div>
