<div class="d-flex justify-content-center gap-2">
    <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض الدور">
        <i class="ti ti-eye"></i>
    </a>

    @if (!in_array($role->name, ['SuperAdmin', 'manager'], true))
        <x-actions.edit :url="route('admin.roles.edit', $role)" title="تعديل الدور" permission="roles.update" />
        <x-actions.delete :id="$role->id" :url="route('admin.roles.destroy', $role)" title="حذف الدور" permission="roles.delete" />
    @endif
</div>
