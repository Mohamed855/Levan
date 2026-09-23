<div class="dropdown">
    <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false"
        style="border: none; box-shadow: none;">
        <i class="ti ti-dots-vertical" style="font-size: 14px;"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-sm">

        @if($row->status ===
        \App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest::STATUS_PENDING)

        @can('اعتماد طلب تحديث')
        <li>
            <a class="dropdown-item d-flex align-items-center py-1 px-2 js-approve-request-btn" href="#"
                data-id="{{ $row->id }}">
                <i class="ti ti-check me-2" style="font-size: 12px;"></i>
                <span style="font-size: 12px;">موافقة</span>
            </a>
        </li>
        @endcan

        @can('رفض طلب تحديث')
        <li>
            <a class="dropdown-item d-flex align-items-center py-1 px-2 js-reject-request-btn" href="#"
                data-id="{{ $row->id }}">
                <i class="ti ti-x me-2" style="font-size: 12px;"></i>
                <span style="font-size: 12px;">رفض</span>
            </a>
        </li>
        @endcan

        @if(auth()->user()->can('اعتماد طلب تحديث') || auth()->user()->can('رفض طلب تحديث') || auth()->user()->can('طلبات تحديث البيانات'))
        <li>
            <hr class="dropdown-divider my-1">
        </li>
        @endif

        @endif

        @can('طلبات تحديث البيانات')
        <li>
            <a class="dropdown-item d-flex align-items-center py-1 px-2 js-view-request-btn" href="#"
                data-id="{{ $row->id }}">
                <i class="ti ti-eye me-2" style="font-size: 12px;"></i>
                <span style="font-size: 12px;">عرض</span>
            </a>
        </li>
        @endcan

    </ul>
</div>