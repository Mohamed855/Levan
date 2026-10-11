<x-actions.row class="justify-content-center gap-2">
    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل المحفظة" permission="portfolios.edit" />
    <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف المحفظة" permission="portfolios.delete" />
</x-actions.row>
