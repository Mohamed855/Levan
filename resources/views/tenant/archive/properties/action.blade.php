<div class="d-flex justify-content-center gap-2">
    @can('properties_archive.restore')
    <button type="button" class="btn btn-icon btn-primary js-restore-btn"
        data-url="{{ tenant_route_name('archive.properties.restore', $row->id) }}"
        data-id="{{ $row->id }}"
        title="استعادة العقار">
        <i class="ti ti-rotate"></i>
    </button>
    @endcan

    @can('properties_archive.force_delete')
    <button type="button" class="btn btn-icon btn-label-danger js-force-delete-btn"
        data-url="{{ tenant_route_name('archive.properties.force-delete', $row->id) }}"
        data-id="{{ $row->id }}"
        title="حذف نهائي">
        <i class="ti ti-trash"></i>
    </button>
    @endcan
</div>
