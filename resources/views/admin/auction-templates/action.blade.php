<div class="d-flex justify-content-center gap-2">
    <a href="{{ route('admin.auction-templates.show', $row) }}" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض القالب">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.edit :url="route('admin.auction-templates.edit', $row)" title="تعديل القالب" permission="auction_templates.edit" />
    <x-actions.delete :id="$row->id" :url="route('admin.auction-templates.destroy', $row)" title="حذف القالب" permission="auction_templates.delete" />
</div>
