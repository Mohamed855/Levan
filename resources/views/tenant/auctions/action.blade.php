<div class="d-flex justify-content-center align-items-center gap-2">
    <a href="{{ tenant_route_name($route . '.show', $row->id) }}" class="btn btn-icon btn-primary bg-primary" title="عرض التفاصيل">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل المزاد" permission="auctions.edit" />
    <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف المزاد" permission="auctions.delete" />
</div>
