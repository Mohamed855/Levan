<div class="d-flex justify-content-center gap-2">
    <x-actions.edit :url="route('admin.faqs.edit', $row->id)" title="تعديل السؤال" permission="faq.update" />
    <x-actions.delete :id="$row->id" :url="route('admin.faqs.destroy', $row->id)" title="حذف السؤال" permission="faq.update" />
</div>
