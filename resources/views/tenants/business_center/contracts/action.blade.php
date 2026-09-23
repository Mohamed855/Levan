<div class="d-flex justify-content-center">
    @can('تعديل عقد')
        <a href="{{ tenant_route_name($route . '.edit', $id) }}" class="btn btn-sm text-secondary" title="تعديل العقد">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('حذف عقد')
        <button type="button" class="btn btn-sm text-secondary js-delete-btn" data-id="{{ $id }}" title="حذف العقد">
            <i class="ti ti-trash"></i>
        </button>
        <form id="delete-form-{{ $id }}" action="{{ tenant_route_name($route . '.destroy', $id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</div>
