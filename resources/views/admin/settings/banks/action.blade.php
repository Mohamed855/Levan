<div class="d-flex justify-content-center gap-2">

    <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل البنك" aria-label="تعديل البنك">
        <i class="ti ti-edit"></i>
    </a>

    <button type="button" class="btn btn-icon btn-primary bg-primary" onclick="confirmDelete({{ $row->id }})" title="حذف البنك" aria-label="حذف البنك">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</div>
