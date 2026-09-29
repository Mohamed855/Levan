<div class="d-flex justify-content-center gap-2">

    @can('version_testers.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('version_testers.delete')
        <button type="button" class="btn btn-icon btn-label-primary js-delete-btn" data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan

</div>
