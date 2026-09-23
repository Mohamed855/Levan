<div class="card">
    <div class="container-fluid">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title mb-0">
                <h5 class="m-0 fw-bold">سجل تفاصيل السداد</h5>
            </div>
            <button type="button" class="btn btn-primary btn-sm js-create-payment-btn">
                <i class="ti ti-plus me-1"></i>
                إضافة تحصيل جديد
            </button>
        </div>
        <div class="card-body" data-customer-id="{{ $customer->id }}">
            @php
            $customerPayments = \App\Models\Tenants\FinancialCollection\Collection\Collection::where('customer_id', $customer->id)
            ->with(['paymentMethod', 'addedBy', 'approvedBy'])
            ->latest()
            ->get();
            @endphp

            @if ($customerPayments && $customerPayments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="payments-table"
                    data-customer-id="{{ $customer->id }}">
                    <thead class="table-light">
                        <tr>
                            <th>التحصيل</th>
                            <th>المبلغ</th>
                            <th>تفاصيل السداد</th>
                            <th>الحالة والمستخدم</th>
                            <td>المرفقات</td>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customerPayments as $payment)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ $payment->receipt_number }}</span>
                                    <small class="text-muted">{{ $payment->formatted_collection_date }}</small>
                                    @if ($payment->closing_code)
                                    <small class="text-muted" style="font-size: 0.75rem;">كود: {{ $payment->closing_code
                                        }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ number_format($payment->collected_amount, 2)
                                    }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div>
                                        <span
                                            class="badge bg-label-{{ $payment->collection_type === 'cash' ? 'info' : 'warning' }}">
                                            {{ $payment->collection_type_label }}
                                        </span>
                                        <span
                                            class="badge bg-label-{{ $payment->payment_type === 'settlement' ? 'primary' : 'secondary' }}">
                                            {{ $payment->payment_type_label }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div>
                                        <span class="badge {{ $payment->status_class }}">
                                            {{ $payment->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                
                                    @if($payment->attachment)
                                    <a href="{{ asset('storage/' . $payment->attachment) }}" target="_blank"
                                        class="btn btn-sm btn-icon btn-text-secondary" title="عرض المرفق">
                                        <i class="ti ti-paperclip"></i>
                                    </a>
                                    @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center">
                                    @if($payment->status === 'pending')
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary js-edit-payment-btn"
                                        data-id="{{ $payment->id }}" title="تعديل">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary js-delete-payment-btn"
                                        data-id="{{ $payment->id }}" title="حذف">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @else
                                    <span class="text-muted">لا يمكن الإجراء</span>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="ti ti-credit-card-off" style="font-size: 48px; color: #8592a3;"></i>
                <h5 class="text-muted mt-3">لا توجد تحصيلات لهذا العميل</h5>
            </div>
            @endif
        </div>
    </div>
</div>
<div id="customer-payment-modals-container"></div>

<script>
    window.customerId = {{ $customer->id }};

    window.refreshPaymentsTable = function() {
        location.reload();
    };
</script>