<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('tenant.users.edit', $row->id)" title="تعديل المستخدم" permission="users.update" />
    @if ($row->id !== auth('tenant')->id())
        <x-actions.delete :id="$row->id" :url="route('tenant.users.destroy', $row->id)" title="أرشفة المستخدم" permission="users.delete" />
    @endif
</div>
