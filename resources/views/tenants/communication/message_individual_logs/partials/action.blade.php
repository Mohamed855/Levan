<div class="d-flex justify-content-center gap-2">
    {{-- <a href="{{ tenant_route_name('communication.message-individual-logs.show', $id) }}"
        class="btn btn-icon btn-label-primary waves-effect waves-light" title="عرض">
        <i class="ti ti-eye"></i>
    </a> --}}

    <a href="javascript:void(0);" onclick="confirmDelete('{{ $id }}')"
        class="btn btn-icon btn-label-primary waves-effect waves-light" title="حذف السجل">
        <i class="ti ti-trash"></i>
    </a>

    <form id="delete-form-{{ $id }}"
        action="{{ tenant_route_name('communication.message-individual-logs.destroy', $id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
