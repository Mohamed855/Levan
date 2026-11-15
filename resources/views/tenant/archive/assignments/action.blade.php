<x-actions.row>
    @can('assignments_archive.restore')
        <x-actions.restore
            :url="tenant_route_name('archive.assignments.restore', $row->id)"
            :id="$row->id"
            title="استعادة الإسناد"
            icon="ti ti-rotate"
        />
    @endcan

    @can('assignments_archive.force_delete')
        <x-actions.force-delete
            :url="tenant_route_name('archive.assignments.force-delete', $row->id)"
            :id="$row->id"
            title="حذف نهائي"
        />
    @endcan
</x-actions.row>
