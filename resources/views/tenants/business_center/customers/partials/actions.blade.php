<div class="d-flex justify-content-center gap-2">

    @if (auth()->user()->can('customers.edit_all') ||
            auth()->user()->can('customers.edit') ||
            (auth()->user()->can('customers.update_directly') &&
                $customer->relationship_manager_id === auth()->id()))
        <a title="تعديل العميل" href="{{ tenant_route_name('customers.edit', $customer->id) }}" class="btn btn-icon btn-label-primary">
            <i class="ti ti-edit"></i>
        </a>
    @endif

    @if (auth()->user()->can('customers.delete_all') ||
            (auth()->user()->can('customers.delete') && $customer->relationship_manager_id === auth()->id()))
        <button title="حذف العميل" type="button" class="btn btn-icon btn-label-primary js-delete-btn" data-id="{{ $customer->id }}">
            <i class="ti ti-trash"></i>
        </button>
        <form id="delete-form-{{ $customer->id }}" action="{{ tenant_route_name('customers.destroy', $customer->id) }}"
            method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>
