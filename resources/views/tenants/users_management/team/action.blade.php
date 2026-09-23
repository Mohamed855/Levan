<div class="d-flex align-items-center gap-2">
    <a href="{{ tenant_route_name($route . '.edit', $id) }}" 
       class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect waves-light" 
       data-bs-toggle="tooltip" 
       title="تعديل">
        <i class="ti ti-edit ti-md"></i>
    </a>
    
    <form id="delete-form-{{ $id }}" 
          action="{{ tenant_route_name($route . '.destroy', $id) }}" 
          method="POST" 
          class="d-inline-block m-0">
        @csrf
        @method('DELETE')
        <button type="button" 
                class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect waves-light" 
                data-bs-toggle="tooltip" 
                title="حذف" 
                onclick="confirmDelete({{ $id }})">
            <i class="ti ti-trash ti-md"></i>
        </button>
    </form>
</div>