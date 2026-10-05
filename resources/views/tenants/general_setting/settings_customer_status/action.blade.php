<div class="d-flex justify-content-center gap-2">
    <button type="button" class="btn btn-icon btn-edit" data-id="{{ $id }}" data-name="{{ $name ?? '' }}"
        data-status="{{ $status ?? 'active' }}" title="تعديل">
        <i class="ti ti-edit"></i>
    </button>
    <a href="javascript:void(0);" class="btn btn-icon btn-delete" onclick="confirmDelete('{{ $id }}')"
        title="حذف الشركة">
        <i class="ti ti-trash"></i>
    </a>
    <form id="delete-form-{{ $id }}" action="{{ tenant_route_name($route . '.destroy', $id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
