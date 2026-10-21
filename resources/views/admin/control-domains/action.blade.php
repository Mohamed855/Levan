<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('admin.control-domains.edit', $row->id)" title="تعديل النطاق" permission="control_domains.update" />
    <x-actions.delete :id="$row->id" :url="route('admin.control-domains.destroy', $row->id)" title="حذف النطاق" permission="control_domains.update" />
</div>
