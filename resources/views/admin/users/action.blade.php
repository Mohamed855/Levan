<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('admin.users.edit', $row->id)" title="تعديل المستخدم" permission="admin_users.update" />
    @if (!$row->isSuperAdmin() && auth('admin')->id() !== $row->id)
        <x-actions.delete :id="$row->id" :url="route('admin.users.destroy', $row->id)" title="حذف المستخدم" permission="admin_users.update" />
    @endif
</div>
