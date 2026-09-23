<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">طلبات إضافة أرقام الهواتف</h5>
        <span class="badge bg-primary rounded-pill">
            {{ $customer->phoneRequests->count() }} طلب
        </span>
    </div>
    <div class="card-body">
        <div id="phone-requests-list">
            @include('tenants.business_center.customers.phone-requests.list', ['customer' => $customer])
        </div>
    </div>
</div>