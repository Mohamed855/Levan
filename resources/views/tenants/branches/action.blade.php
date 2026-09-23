<div class="d-flex justify-content-center">

    {{-- @can('تعديل فرع') --}}
        <a href="{{ tenant_route_name($route . '.edit', $row->id) }}" class="btn btn-sm text-secondary">
            <i class="ti ti-edit"></i>
        </a>
    {{-- @endcan --}}

    {{-- @can('حذف فرع') --}}
        <button type="button" class="btn btn-sm text-secondary" onclick="confirmDelete({{ $row->id }})">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ tenant_route_name($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    {{-- @endcan --}}

</div>