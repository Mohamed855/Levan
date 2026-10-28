<x-actions.row>
    <x-actions.show :url="tenant_route_name('users.show', $id)" title="عرض المستخدم" permission="users.view" />

    <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل المستخدم" permission="users.edit" />

    @if ((int) $id !== 1)
        <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف المستخدم" permission="users.delete" />
    @endif
</x-actions.row>
