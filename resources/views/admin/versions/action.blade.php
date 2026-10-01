<x-actions.row class="justify-content-center gap-2">
    <x-actions.edit :url="route($route . '.edit', $row->id)" title="تعديل الإصدار" permission="versions.edit" />
    <x-actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف الإصدار" permission="versions.delete" />
</x-actions.row>
