<x-actions.row class="justify-content-center">
    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل الشركة" permission="companies.edit" class="btn-sm text-secondary" />
    <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف الشركة" permission="companies.delete" class="btn-sm text-secondary" />
</x-actions.row>
