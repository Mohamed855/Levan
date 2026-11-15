<x-actions.row>
    @can('financial_transactions.restore')
        <x-actions.restore :id="$id" title="استرجاع الفاتورة" icon="ti ti-refresh" />
    @endcan

    @can('financial_transactions.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
