<x-actions.row class="justify-content-center gap-2">
    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل العقد" permission="contracts.edit" />
    <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف العقد" permission="contracts.delete" />
</x-actions.row>
