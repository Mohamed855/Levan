<div class="d-flex justify-content-center gap-2">
    @can('coupons.view')
        <a href="{{ route('admin.coupons.show', $row) }}" class="btn btn-icon btn-label-info bg-label-info" title="تفاصيل الاستخدام">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('coupons.edit')
        <a href="{{ route('admin.coupons.edit', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="تعديل الكوبون">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('coupons.delete')
        <button type="button" class="btn btn-icon btn-label-primary bg-label-primary js-delete-btn" title="حذف الكوبون"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route('admin.coupons.destroy', $row->id) }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
