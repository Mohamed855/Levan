<div class="d-flex justify-content-center gap-2">
    @can('subscriptions.view')
        <a href="{{ route('admin.subscriptions.show', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض تفاصيل الاشتراك">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('subscriptions.delete')
        <button type="button" class="btn btn-icon btn-label-primary bg-label-primary js-delete-btn" title="حذف الاشتراك"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route('admin.subscriptions.destroy', $row->id) }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan

    @can('subscriptions.update')
        @if($row->status === 'pending')
            <form method="POST" action="{{ route('admin.subscriptions.accept', $row) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-icon btn-label-success" title="قبول الاشتراك"><i class="ti ti-check"></i></button>
            </form>
            <form method="POST" action="{{ route('admin.subscriptions.reject', $row) }}" class="d-inline" onsubmit="return confirm('هل تريد رفض الاشتراك؟')">
                @csrf
                <button type="submit" class="btn btn-icon btn-label-danger" title="رفض الاشتراك"><i class="ti ti-x"></i></button>
            </form>
        @endif
    @endcan
</div>
