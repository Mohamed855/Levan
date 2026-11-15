<x-actions.row>
    @can('plans.restore')
        <x-actions.restore :id="$id" title="استرجاع الباقة" icon="ti ti-refresh" />
    @endcan

    @can('plans.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
