<x-actions.row>
    @can('auctions_archive.restore')
        <x-actions.restore
            :url="tenant_route_name('archive.auctions.restore', $row->id)"
            :id="$row->id"
            title="استعادة المزاد"
            icon="ti ti-rotate"
        />
    @endcan

    @can('auctions_archive.force_delete')
        <x-actions.force-delete
            :url="tenant_route_name('archive.auctions.force-delete', $row->id)"
            :id="$row->id"
            title="حذف نهائي"
        />
    @endcan
</x-actions.row>
