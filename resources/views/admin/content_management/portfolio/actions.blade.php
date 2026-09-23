<div class="d-flex justify-content-center gap-2">
    {{-- <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-sm text-secondary" title="عرض">
        <i class="ti ti-eye"></i>
    </a> --}}

    <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-sm text-secondary" title="تعديل">
        <i class="ti ti-edit"></i>
    </a>

    <button type="button" class="btn btn-sm text-secondary js-delete-btn" data-id="{{ $row->id }}" title="حذف">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
