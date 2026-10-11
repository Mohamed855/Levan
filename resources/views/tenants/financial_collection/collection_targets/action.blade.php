{{-- أزرار الإجراءات للجدول الخارجي - عرض وحذف فقط --}}

<div class="d-flex justify-content-center gap-2">
    {{-- زر عرض التفاصيل --}}
    {{-- @can('أهداف التحصيل') --}}
    <a href="{{ tenant_route_name($route . '.user-details', $userId) }}" class="btn btn-icon btn-label-primary" title="عرض التفاصيل">
        <i class="ti ti-eye"></i>
    </a>
    {{-- @endcan --}}

    {{-- زر الحذف --}}
    {{-- @can('حذف هدف') --}}
    {{-- <button type="button" 
                class="btn btn-icon btn-label-primary delete-btn"
                data-id="{{ $id }}" 
                data-route="{{ tenant_route_name($route . '.destroy', $id) }}" 
                title="حذف"
                onclick="event.stopPropagation();">
            <i class="ti ti-trash"></i>
        </button> --}}
    {{-- @endcan --}}
</div>
