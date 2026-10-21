<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('admin.banks.edit', $row->id)" title="تعديل الحساب البنكي" permission="banks.update" />
    <x-actions.delete :id="$row->id" :url="route('admin.banks.destroy', $row->id)" title="حذف الحساب البنكي" permission="banks.update" />
</div>
