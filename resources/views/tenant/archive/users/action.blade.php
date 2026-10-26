<div class="d-flex justify-content-center gap-2">
    @can('user_archive.restore')
    <button title="استرجاع المستخدم" type="button" onclick="confirmRestore({{ $id }})"
        class="btn btn-icon btn-primary bg-primary">
        <i class="ti ti-refresh"></i>
    </button>
    @endcan

    @can('user_archive.force_delete')
    <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
        class="btn btn-icon btn-primary bg-primary">
        <i class="ti ti-trash-x"></i>
    </button>
    @endcan
</div>
