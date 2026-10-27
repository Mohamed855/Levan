<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="route($route . '.show', $row->id)" title="عرض تفاصيل القالب" permission="auction_templates.view" />

    <x-actions.edit :url="route($route . '.edit', $row->id)" title="تعديل القالب" permission="auction_templates.edit" />

    <x-actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف القالب" permission="auction_templates.delete" />
</x-actions.row>
