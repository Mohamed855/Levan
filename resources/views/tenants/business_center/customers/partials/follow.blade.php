<!-- follow.blade.php -->
<div class="card">
    <div class="container-fluid">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">
                <h5 class="m-0 fw-bold">سجل المتابعات</h5>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ tenant_route_name('customers.follows.next-customer', $customer->id) }}"
                    class="btn btn-primary btn-sm" title="الانتقال إلى العميل التالي">
                    التالي
                    <i class="ti ti-arrow-left me-1"></i>
                </a>
                @can('إضافة متابعة')
                <button type="button" class="btn btn-primary btn-sm js-create-follow-btn">
                    <i class="ti ti-plus me-1"></i>
                    إضافة متابعة
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body" data-customer-id="{{ $customer->id }}">
            <div id="saved-follows">
                @php
                $relations = ['contactMethod', 'customerStatus', 'createdBy', 'collection', 'contactResult'];
                $optional = [
                    'fullPaymentDetails' => 'full_payment_details',
                    'partialPaymentDetails' => 'partial_payment_details',
                    'promisePaymentDetails' => 'promise_payment_details',
                    'callbackDetails' => 'callback_details',
                    'documentRequestDetails' => 'document_request_details',
                    'objectionDetails' => 'objection_details',
                    'recoveryDetails' => 'recovery_details',
                ];
                foreach ($optional as $rel => $table) {
                    if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                        $relations[] = $rel;
                    }
                }

                $customerFollows = \App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow::where(
                    'customer_id',
                    $customer->id,
                )
                ->with($relations)
                ->latest()
                ->get();

                // التحقق من وجود أي متابعة لها أزرار متاحة
                $hasAnyActions = false;
                foreach ($customerFollows as $follow) {
                $canEdit = true;
                if ($follow->collection_id && $follow->collection) {
                $canEdit = !in_array($follow->collection->status, ['approved', 'rejected']);
                }

                $hasEditButton = $canEdit && auth()->user()->can('تعديل متابعة');
                $hasDeleteButton = auth()->user()->can('حذف متابعة');

                if ($hasEditButton || $hasDeleteButton) {
                $hasAnyActions = true;
                break;
                }
                }
                @endphp

                @include('tenants.business_center.customers.customer_follows.list', [
                'customerFollows' => $customerFollows,
                'customer' => $customer,
                'hasAnyActions' => $hasAnyActions,
                ])
            </div>
        </div>
    </div>
</div>

<div id="customer-follow-modals-container"></div>

@include('tenants.business_center.customers.customer_follows.accounts-modal')

<script>
    window.customerId = {{ $customer->id }};

    // تفعيل tooltips للملاحظات
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>