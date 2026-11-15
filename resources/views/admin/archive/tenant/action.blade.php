<x-actions.row>
    @can('companies.restore')
        <x-actions.restore :id="$id" title="استرجاع الشركة" icon="ti ti-refresh" />
    @endcan

    @can('companies.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
