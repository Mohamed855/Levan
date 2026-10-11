<div class="dropdown d-flex justify-content-center gap-2">
    <button class="btn btn-icon btn-label-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false"
        title="الإجراءات" aria-label="الإجراءات">
        <i class="ti ti-dots-vertical" aria-hidden="true"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-sm">

        @if($row->status ===
        \App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest::STATUS_PENDING)

        @can('customer_update_requests.approve')
        <li>
            <a class="dropdown-item d-flex align-items-center py-1 px-2 js-approve-request-btn" href="#"
                data-id="{{ $row->id }}">
                <i class="ti ti-check me-2" style="font-size: 12px;"></i>
                <span style="font-size: 12px;">موافقة</span>
            </a>
        </li>
        @endcan

        @can('customer_update_requests.reject')
        <li>
            <a class="dropdown-item d-flex align-items-center py-1 px-2 js-reject-request-btn" href="#"
                data-id="{{ $row->id }}">
                <i class="ti ti-x me-2" style="font-size: 12px;"></i>
                <span style="font-size: 12px;">رفض</span>
            </a>
        </li>
        @endcan

        @if(auth()->user()->can('customer_update_requests.approve') || auth()->user()->can('customer_update_requests.reject') || auth()->user()->can('customer_update_requests.view'))
        <li>
            <hr class="dropdown-divider my-1">
        </li>
        @endif

        @endif

        @can('customer_update_requests.view')
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
