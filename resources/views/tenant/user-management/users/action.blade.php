<x-actions.row>
    <x-actions.show :url="tenant_route_name('users.show', $id)" title="عرض المستخدم" permission="users.view" />

    @can('users.edit')
        <x-actions.edit :url="tenant_route_name($route . '.edit', $id)" title="تعديل المستخدم" permission="users.edit" />
    @endcan

    @can('users.delete')
        @if ((!isset($row) || !$row->isAdmin()) && (int) $id !== 1 && (int) auth()->id() !== (int) $id)
            <x-actions.delete :id="$id" :url="tenant_route_name($route . '.destroy', $id)" title="حذف المستخدم" permission="users.delete" />
        @endif
    @endcan
</x-actions.row>
