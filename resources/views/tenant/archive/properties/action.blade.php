<x-actions.row>
    @can('properties_archive.restore')
        <x-actions.restore
            :url="tenant_route_name('archive.properties.restore', $row->id)"
            :id="$row->id"
            title="استعادة العقار"
            icon="ti ti-rotate"
        />
    @endcan

    @can('properties_archive.force_delete')
        <x-actions.force-delete
            :url="tenant_route_name('archive.properties.force-delete', $row->id)"
            :id="$row->id"
            title="حذف نهائي"
        />
    @endcan
</x-actions.row>
