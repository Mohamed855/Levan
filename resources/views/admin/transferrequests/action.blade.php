<div class="d-flex justify-content-center gap-2">

    <a href="{{ route('admin.transfer-requests.details', $row) }}" class="btn btn-sm text-secondary" title="تعديل">
        <i class="ti ti-eye"></i>
    </a>

    {{-- <button type="button" class="btn btn-sm text-danger js-delete-btn" data-id="{{ $row->id }}" title="حذف">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-{{ $row->id }}" action="{  { route($route . '.destroy', $row->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form> --}}

</div>
