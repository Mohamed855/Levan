<div class="d-flex justify-content-center gap-2">

    @can('plans.view')
        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-label-primary" title="عرض تفاصيل الباقة">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('plans.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('plans.delete')
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
