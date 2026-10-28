<x-actions.row>
    <x-actions.edit :url="route('admin.roles.edit', $role)" title="تعديل الدور" permission="roles.edit" />

    @if ($role->name !== 'SuperAdmin')
        <x-actions.delete :id="$role->id" :url="route('admin.roles.destroy', $role)" title="حذف الدور" permission="roles.delete" />
    @endif
</x-actions.row>
