<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('tenant.companies.edit', $row->id)" title="تعديل الشركة" permission="companies.edit" />
    <x-actions.delete :id="$row->id" :url="route('tenant.companies.destroy', $row->id)" title="أرشفة الشركة" permission="companies.delete" />
</div>
