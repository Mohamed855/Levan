<x-actions.row>
    <x-actions.edit :url="tenant_route_name('roles.edit', $role)" title="تعديل الدور" permission="roles.edit" />

    @if ($role->id != 1 && $role->name !== 'Admin')
        <x-actions.delete :id="$role->id" :url="tenant_route_name('roles.destroy', $role)" title="حذف الدور" permission="roles.delete" />
    @endif
</x-actions.row>
