<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-receipt me-2"></i>
                سجل السدادات
            </h5>
            <span class="badge bg-label-primary" id="payments-count">
                {{ $customer->collections()->where('status', 'approved')->count() }} سداد
            </span>
        </div>
    </div>

    <div class="card-body">
        <div id="payment-history-list">
            @php
            $payments = $customer->collections()->where('status', 'approved')->with([
            'paymentMethod:id,name',
            'addedBy:id,name',
            'approvedBy:id,name'
            ])->get();
            @endphp

            @include('tenants.business_center.customers.payment_history.list', [
            'payments' => $payments,
            'customer' => $customer
            ])
        </div>
    </div>
</div>

<script>
    window.customerId = {{ $customer->id }};
</script>