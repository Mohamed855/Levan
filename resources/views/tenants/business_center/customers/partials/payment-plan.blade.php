<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">خطة السداد</h5>
            <button type="button" class="btn btn-primary btn-sm js-create-payment-plan-btn">
                إضافة خطة سداد
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- عرض خطط السداد المحفوظة -->
        <div id="saved-payment-plans">
            @php
            $customerPaymentPlans = collect();
            try {
            if (method_exists($customer, 'paymentPlans')) {
            $customerPaymentPlans = $customer->paymentPlans()->latest()->get();
            }
            } catch (\Exception $e) {
            $customerPaymentPlans = collect();
            }
            @endphp

            @include('tenants.business_center.customers.customer_payment_plans.list', ['customerPaymentPlans' =>
            $customerPaymentPlans])
        </div>
    </div>
</div>

<!-- Container للـ Modals -->
<div id="customer-payment-plan-modals-container"></div>

<script>
    window.customerId = {{ $customer->id }};
</script>