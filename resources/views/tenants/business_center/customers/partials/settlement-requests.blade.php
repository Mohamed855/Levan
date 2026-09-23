<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">طلبات التسوية</h5>
            <button type="button" class="btn btn-primary btn-sm js-create-settlement-btn">
                إضافة تسوية جديدة
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="saved-settlements">
            @php
                $customerSettlements = collect();
                try {
                    if (method_exists($customer, 'settlements')) {
                        $customerSettlements = $customer->settlements()->with('createdBy:id,name')->latest()->get();
                    }
                } catch (\Exception $e) {
                    $customerSettlements = collect();
                }
            @endphp

            @include('tenants.business_center.customers.customer_settlements.list', ['customerSettlements' => $customerSettlements])
        </div>
    </div>
</div>

<div id="customer-settlement-modals-container"></div>

<script>
    window.customerId = {{ $customer->id }};
</script>