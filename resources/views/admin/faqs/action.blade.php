<div class="d-flex justify-content-center gap-2">
    @can('faqs.edit')
        <a href="{{ route($route . '.edit', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="تعديل السؤال" aria-label="تعديل السؤال">
            <i class="ti ti-edit"></i>
        </a>
    @endcan

    @can('faqs.delete')
        <x-shared.actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف السؤال"
            class="btn-icon btn-primary bg-primary" />
    @endcan
</div>
