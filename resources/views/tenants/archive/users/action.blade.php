<div class="d-flex justify-content-center">
    @can('إسترجاع السجلات')
    <button title="استرجاع المستخدم" type="button" onclick="confirmRestore({{ $id }})"
        class="btn btn-icon btn-label-primary me-1">
        <i class="ti ti-refresh"></i>
    </button>
    @endcan

    @can('حذف نهائي للسجلات')
    <button title="حذف نهائي" type="button" onclick="confirmForceDelete({{ $id }})"
        class="btn btn-icon btn-label-primary">
        <i class="ti ti-trash-x"></i>
    </button>
    @endcan
</div>