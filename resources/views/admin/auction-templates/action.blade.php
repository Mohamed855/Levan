<x-actions.row class="justify-content-center gap-2">
    @can('auction_templates.view')
        <a href="{{ route($route . '.show', $row->id) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض تفاصيل القالب">
            <i class="ti ti-eye"></i>
        </a>
    @endcan

    <x-actions.edit :url="route($route . '.edit', $row->id)" title="تعديل القالب" permission="auction_templates.edit" />

    <x-actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف القالب" permission="auction_templates.delete" />
</x-actions.row>
