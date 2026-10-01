<div class="d-flex justify-content-center gap-2">
    @can('plans.view')
        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-label-primary" title="عرض تفاصيل الباقة">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    @can('plans.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-label-primary" title="تعديل الباقة">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('plans.delete')
        @if ($row->hasSubscriptions())
            <button type="button" class="btn btn-icon btn-label-secondary" title="لا يمكن حذف باقة مرتبطة باشتراكات، استخدم زر التعطيل بدلاً من ذلك" disabled>
                <i class="ti ti-trash-off"></i>
            </button>
        @else
            <button type="button" class="btn btn-icon btn-label-danger js-delete-btn" data-id="{{ $row->id }}" title="حذف الباقة">
                <i class="ti ti-trash"></i>  
            </button>

            <form id="delete-form-{{ $row->id }}" action="{{ route($route . '.destroy', $row->id) }}" method="POST"
                style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endcan
</div>
