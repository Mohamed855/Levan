<div class="d-flex justify-content-center">

   <a href="{{ route('admin.coupons.details', $row) }}" class="btn btn-sm text-secondary">
            <i class="ti ti-eye"></i>
        </a>

    @can('تعديل كوبون')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-sm text-secondary">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('حذف كوبون')
        <button type="button" class="btn btn-sm text-secondary js-delete-btn" data-id="{{ $row->id }}">
            <i class="ti ti-trash"></i>
        </button>

        <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endcan


</div>
