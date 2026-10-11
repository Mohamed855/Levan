<div class="d-flex justify-content-center gap-2">
    <form action="{{ tenant_route_name('archive.assignments.restore', $row->id) }}" method="POST" class="d-inline">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-icon btn-label-primary" title="استعادة الإسناد">
            <i class="ti ti-rotate"></i>
        </button>
    </form>
    <button type="button" class="btn btn-icon btn-label-primary js-force-delete"
        data-url="{{ tenant_route_name('archive.assignments.force-delete', $row->id) }}"
        title="حذف نهائي">
        <i class="ti ti-trash"></i>
    </button>
</div>
