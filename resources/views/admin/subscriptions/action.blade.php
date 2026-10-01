@can('subscriptions.delete')
    <div class="d-flex justify-content-center">
        <button type="button" class="btn btn-icon btn-label-danger js-delete-subscription-btn" title="حذف نهائي للاشتراك"
            data-id="{{ $row->id }}" data-number="{{ $row->subscription_number }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-subscription-form-{{ $row->id }}" action="{{ route('admin.subscriptions.destroy', $row->id) }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endcan
