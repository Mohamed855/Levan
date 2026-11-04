<div class="d-flex justify-content-center gap-2">
    @can('financial_transactions.restore')
    <button title="استرجاع الفاتورة" type="button" onclick="confirmRestore({{ $id }})"
        class="btn btn-icon btn-primary">
        <i class="ti ti-refresh"></i>
    </button>
    @endcan

    @can('financial_transactions.force_delete')
    <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
        class="btn btn-icon btn-label-danger">
        <i class="ti ti-trash"></i>
    </button>
    @endcan
</div>
