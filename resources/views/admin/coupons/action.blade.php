<div class="d-flex justify-content-center gap-2">

    <a href="{{ route('admin.coupons.details', $row) }}" class="btn btn-icon btn-primary bg-primary" title="عرض تفاصيل الكوبون" aria-label="عرض تفاصيل الكوبون">
        <i class="ti ti-eye"></i>
    </a>

    @can('coupons.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل الكوبون" aria-label="تعديل الكوبون">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('coupons.delete')
        <button type="button" class="btn btn-icon btn-primary bg-primary js-delete-btn" data-id="{{ $row->id }}" title="حذف الكوبون" aria-label="حذف الكوبون">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan


</div>
