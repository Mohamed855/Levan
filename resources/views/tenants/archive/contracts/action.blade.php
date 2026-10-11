<div class="d-flex justify-content-center gap-2">
    @can('contracts_archive.restore')
        <button title="استرجاع العقد" type="button" onclick="confirmRestore({{ $id }})"
            class="btn btn-icon btn-label-primary">
            <i class="ti ti-refresh"></i>
        </button>
    @endcan

    @can('contracts_archive.force_delete')
        <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
            class="btn btn-icon btn-label-primary">
            <i class="ti ti-trash-x"></i>
        </button>
    @endcan
</div>
