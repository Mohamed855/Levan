<div class="d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-label-info js-sub-periods-btn"
        data-url="{{ route('admin.subscriptions.periods', $row->id) }}"
        data-number="{{ $row->subscription_number }}"
        title="عرض فترات وسجل الاشتراك">
        <i class="ti ti-timeline"></i>
    </button>

    @can('subscriptions.delete')
        <button type="button" class="btn btn-icon btn-label-primary js-delete-btn" title="حذف نهائي للاشتراك"
            data-id="{{ $row->id }}" data-delete-url="{{ route('admin.subscriptions.destroy', $row->id) }}"
            data-delete-title="حذف نهائي للاشتراك؟"
            data-delete-message="سيتم حذف الاشتراك رقم ({{ $row->subscription_number }}) نهائياً مع كافة فتراته ومقاعده."
            data-delete-confirm-text="نعم، حذف نهائي">
            <i class="ti ti-trash"></i>
        </button>
    @endcan
</div>
