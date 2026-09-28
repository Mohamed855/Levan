<div class="d-flex justify-content-center gap-1">
  <a title="تعديل المستخدم" href="{{ tenant_route_name($route . '.edit', $id) }}" class="btn btn-icon btn-label-primary me-1">
    <i class="ti ti-edit"></i>
  </a>

  @if((int) $id !== 1)
    <button title="حذف المستخدم" type="button" onclick="confirmDelete({{ $id }})" class="btn btn-icon btn-label-primary">
      <i class="ti ti-trash"></i>
    </button>
    <form id="delete-form-{{ $id }}" action="{{ tenant_route_name($route . '.destroy', $id) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  @endif
</div>
