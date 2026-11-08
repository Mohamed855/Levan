<div class="d-flex justify-content-center gap-2">

    @can('update', $row)
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل النطاق" aria-label="تعديل النطاق">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('delete', $row)
        <x-shared.actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف النطاق"
            class="btn-icon btn-primary bg-primary" />
    @endcan

</div>
