<x-actions.row>
    <x-actions.show :url="route('admin.activity-logs.show', $row->id)" title="عرض التفاصيل" permission="activity_log.view" />
</x-actions.row>
