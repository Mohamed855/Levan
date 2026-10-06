<div class="d-flex justify-content-center align-items-center gap-1">
    <a href="{{ tenant_route_name($route . '.show', $row->id) }}" class="btn btn-sm btn-icon text-secondary" title="عرض التفاصيل">
        <i class="ti ti-eye"></i>
    </a>
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل المزاد" permission="auctions.edit" class="btn-sm text-secondary" />
    <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف المزاد" permission="auctions.delete" class="btn-sm text-secondary" />
</div>
