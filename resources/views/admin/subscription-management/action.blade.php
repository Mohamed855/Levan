{{-- Actions for subscription management --}}
@can('subscriptions.delete')
    <div class="d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-btn" title="حذف نهائي للاشتراك"
            data-id="{{ $row->id }}" data-delete-url="{{ route('admin.subscriptions.destroy', $row->id) }}"
            data-delete-title="حذف نهائي للاشتراك؟"
            data-delete-message="سيتم حذف الاشتراك رقم ({{ $row->subscription_number }}) نهائياً مع كافة فتراته ومقاعده."
            data-delete-confirm-text="نعم، حذف نهائي">
            <i class="ti ti-trash"></i>
        </button>
    </div>
@endcan
