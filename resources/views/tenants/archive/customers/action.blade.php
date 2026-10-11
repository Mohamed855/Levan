<div class="d-flex justify-content-center gap-2">
    @can('customers_archive.restore')
        <button title="استرجاع العميل" type="button" onclick="confirmRestore({{ $id }})"
            class="btn btn-icon btn-label-primary">
            <i class="ti ti-refresh"></i>
        </button>
    @endcan

    @can('customers_archive.force_delete')
        <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
            class="btn btn-icon btn-label-primary">
            <i class="ti ti-trash-x"></i>
        </button>
    @endcan
</div>
