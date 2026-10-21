<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('tenant.roles.edit', $role->id)" title="تعديل الدور" permission="roles.update" />
    @if (! in_array($role->name, ['tenant_owner', 'tenant_manager', 'tenant_user'], true))
        <x-actions.delete :id="$role->id" :url="route('tenant.roles.destroy', $role->id)" title="حذف الدور" permission="roles.delete" />
    @endif
</div>
