<div class="d-flex justify-content-center gap-2">
    @can('tenants.view')
        <a href="{{ route('admin.tenants.show', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض تفاصيل العميل">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('tenants.delete')
        <button type="button" class="btn btn-icon btn-label-primary bg-label-primary js-delete-btn" title="حذف العميل"
            data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route('admin.tenants.destroy', $row->id) }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
