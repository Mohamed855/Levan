<div class="d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-label-success js-restore-btn" data-url="{{ route('admin.archives.users.restore', $id) }}" title="استرجاع المستخدم">
        <i class="ti ti-rotate-clockwise"></i>
    </button>
    <button type="button" class="btn btn-icon btn-label-danger js-force-delete-btn" data-url="{{ route('admin.archives.users.force-delete', $id) }}" title="حذف نهائي">
        <i class="ti ti-trash-x"></i>
    </button>
</div>
