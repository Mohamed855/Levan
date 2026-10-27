<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name($route . '.show', $row->id)" title="عرض التفاصيل" />
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل العقار" permission="properties.edit" />
    <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف العقار" permission="properties.delete" />
</x-actions.row>
