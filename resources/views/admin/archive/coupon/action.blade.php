<x-actions.row>
    @can('coupons.restore')
        <x-actions.restore :id="$id" title="استرجاع الكوبون" icon="ti ti-refresh" />
    @endcan

    @can('coupons.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
