<x-actions.row class="justify-content-center gap-2">
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل الجهة المسندة" permission="issuing_entities.edit" />
    @if (($row->assignments_count ?? 0) === 0)
        <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف الجهة المسندة" permission="issuing_entities.delete" />
    @endif
</x-actions.row>
