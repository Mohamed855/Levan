@foreach($customerPaymentPlans as $plan)
    <div class="border rounded p-3 mb-3" id="plan-{{ $plan->id }}">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label">تاريخ الاستحقاق</label>
                <div class="fw-medium">{{ $plan->due_date_formatted }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">مبلغ الاستحقاق</label>
                <div class="fw-medium">{{ $plan->due_amount_formatted }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">تاريخ الإضافة</label>
                <div class="text-muted small">
                    {{ $plan->created_at->locale('ar')->translatedFormat('d F Y - H:i') }}
                    • {{ $plan->createdBy->name ?? 'غير محدد' }}
                </div>
            </div>
            <div class="col-md-2 text-end">
                <button type="button"
                        class="btn btn-link btn-sm text-secondary me-1 js-edit-payment-plan-btn"
                        data-id="{{ $plan->id }}"
                        title="تعديل">
                    <i class="ti ti-edit" style="font-size: 1.2rem;"></i>
                </button>
                <button type="button"
                        class="btn btn-link btn-sm text-secondary js-delete-payment-plan-btn"
                        data-id="{{ $plan->id }}"
                        title="حذف">
                    <i class="ti ti-trash" style="font-size: 1.2rem;"></i>
                </button>
            </div>
        </div>
    </div>
@endforeach

@if($customerPaymentPlans->count() == 0)
    <div class="text-center py-5" id="no-plans-message">
        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
        <p class="text-muted mb-0">لا توجد خطط سداد</p>
        <small class="text-muted">ابدأ بإضافة خطة سداد لهذا العميل</small>
    </div>
@endif