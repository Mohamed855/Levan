<x-actions.row>
    <x-actions.show :url="tenant_route_name('activity-logs.show', $row->id)" title="عرض التفاصيل" permission="activity_log.view" />
</x-actions.row>
