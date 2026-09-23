<div class="d-flex justify-content-center gap-2">
    {{-- @can('عرض سجلات الرسائل') --}}
    {{-- <a href="{{ tenant_route_name('communication.message-individual-logs.show', $id) }}"
        class="btn btn-icon btn-text-secondary waves-effect waves-light rounded-pill" title="عرض">
        <i class="ti ti-eye"></i>
    </a> --}}
    {{-- @endcan --}}

    {{-- @can('حذف سجلات الرسائل') --}}
    <a href="javascript:void(0);" onclick="confirmDelete('{{ $id }}')"
        class="btn btn-icon btn-text-secondary waves-effect waves-light rounded-pill" title="حذف السجل">
        <i class="ti ti-trash"></i>
    </a>

    <form id="delete-form-{{ $id }}"
        action="{{ tenant_route_name('communication.message-individual-logs.destroy', $id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    {{-- @endcan --}}
</div>
