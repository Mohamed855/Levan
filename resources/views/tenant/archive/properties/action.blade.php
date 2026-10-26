<div class="d-flex justify-content-center gap-2">
    <form action="{{ tenant_route_name('archive.properties.restore', $row->id) }}" method="POST" class="d-inline">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-icon btn-primary bg-primary" title="استعادة العقار">
            <i class="ti ti-rotate"></i>
        </button>
    </form>
    <button type="button" class="btn btn-icon btn-primary bg-primary js-force-delete"
        data-url="{{ tenant_route_name('archive.properties.force-delete', $row->id) }}"
        title="حذف نهائي">
        <i class="ti ti-trash"></i>
    </button>
</div>
