<div class="d-flex justify-content-center gap-1">
    <form action="{{ tenant_route_name('archive.assignments.restore', $row->id) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-icon btn-label-success" title="استعادة الإسناد">
            <i class="ti ti-rotate"></i>
        </button>
    </form>
    <button type="button" class="btn btn-sm btn-icon btn-label-danger js-force-delete"
        data-url="{{ tenant_route_name('archive.assignments.force-delete', $row->id) }}"
        title="حذف نهائي">
        <i class="ti ti-trash"></i>
    </button>
</div>
