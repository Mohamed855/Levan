<div class="d-flex justify-content-center gap-2">

    <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary">
        <i class="ti ti-edit"></i>
    </a>

    <button type="button" class="btn btn-icon btn-label-primary bg-label-primary" onclick="confirmDelete({{ $row->id }})">
        <i class="ti ti-trash"></i>
    </button>

    <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>

</div>
