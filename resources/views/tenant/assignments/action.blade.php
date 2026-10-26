<x-actions.row class="justify-content-center gap-2">
    <a href="{{ tenant_route_name($route . '.show', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="عرض التفاصيل">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل الإسناد" permission="assignments.edit" />
    <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف الإسناد" permission="assignments.delete" />
</x-actions.row>
