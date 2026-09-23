<div class="d-flex justify-content-center">
    @can('تعديل شركة')
        <a href="{{ tenant_route_name($route . '.edit', $id) }}" class="btn btn-sm text-secondary" title="تعديل الشركة">
            <i class="ti ti-edit"></i>
        </a>
    @endcan
    @can('حذف شركة')
        <button type="button" class="btn btn-sm text-secondary js-delete-btn" data-id="{{ $id }}">
            <i class="ti ti-trash"></i>
        </button>
        <form id="delete-form-{{ $id }}" action="{{ tenant_route_name($route . '.destroy', $id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
