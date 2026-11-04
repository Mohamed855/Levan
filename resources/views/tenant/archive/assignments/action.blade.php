<div class="d-flex justify-content-center gap-2">
    @can('assignments_archive.restore')
    <button type="button" class="btn btn-icon btn-primary js-restore-btn"
        data-url="{{ tenant_route_name('archive.assignments.restore', $row->id) }}"
        data-id="{{ $row->id }}"
        title="استعادة الإسناد">
        <i class="ti ti-rotate"></i>
    </button>
    @endcan

    @can('assignments_archive.force_delete')
    <button type="button" class="btn btn-icon btn-label-danger js-force-delete-btn"
        data-url="{{ tenant_route_name('archive.assignments.force-delete', $row->id) }}"
        data-id="{{ $row->id }}"
        title="حذف نهائي">
        <i class="ti ti-trash"></i>
    </button>
    @endcan
</div>
