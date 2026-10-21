<div class="d-flex justify-content-center gap-2">
    <a href="{{ route('admin.activity-logs.show', $row->id) }}" class="btn btn-icon btn-label-primary" title="عرض تفاصيل النشاط">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.delete :id="$row->id" :url="route('admin.activity-logs.destroy', $row->id)" title="حذف السجل" permission="activity_log.view" />
</div>
