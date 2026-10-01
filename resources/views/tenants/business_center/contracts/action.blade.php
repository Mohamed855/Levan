<x-actions.row class="justify-content-center">
    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل العقد" permission="contracts.edit" class="btn-sm text-secondary" />
    <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف العقد" permission="contracts.delete" class="btn-sm text-secondary" />
</x-actions.row>
