<div class="d-flex justify-content-center">
    @can('customers_archive.restore')
    <button title="استرجاع العميل" type="button" onclick="confirmRestore({{ $id }})"
        class="btn btn-sm btn-icon btn-text-success me-1">
        <i class="ti ti-refresh"></i>
    </button>
    @endcan

    @can('customers_archive.force_delete')
    <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
        class="btn btn-sm btn-icon btn-text-danger">
        <i class="ti ti-trash-x"></i>
    </button>
    @endcan
</div>
