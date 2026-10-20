<x-actions.row>
    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل المستخدم" permission="users.edit" class="btn-icon btn-label-primary me-1" />

    @if ((int) $id !== 1)
        <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف المستخدم" permission="users.delete" />
    @endif
</x-actions.row>
