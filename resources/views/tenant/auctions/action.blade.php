<div class="d-flex justify-content-center align-items-center gap-2">
    <x-actions.show :url="tenant_route_name($route . '.show', $row->id)" title="عرض التفاصيل" />
    <x-actions.edit :url="tenant_route_name($route . '.edit', $row->id)" title="تعديل المزاد" permission="auctions.edit" />
    <x-actions.delete :id="$row->id" :url="tenant_route_name($route . '.destroy', $row->id)" title="حذف المزاد" permission="auctions.delete" />
</div>
