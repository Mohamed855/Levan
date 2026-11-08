<div class="d-flex justify-content-center gap-2">
    @can('financial_transactions.restore')
    <button title="استرجاع الفاتورة" type="button"
        class="btn btn-icon btn-primary js-restore-btn" data-id="{{ $id }}">
        <i class="ti ti-refresh"></i>
    </button>
    @endcan

    @can('financial_transactions.force_delete')
    <button title="حذف نهائي" type="button"
        class="btn btn-icon btn-label-danger js-force-btn" data-id="{{ $id }}">
        <i class="ti ti-trash"></i>
    </button>
    @endcan
</div>
