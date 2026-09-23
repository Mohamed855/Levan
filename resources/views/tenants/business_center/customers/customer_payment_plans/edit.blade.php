<div class="modal-header">
    <h5 class="modal-title">تعديل خطة السداد</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <form id="editPaymentPlanForm" data-plan-id="{{ $paymentPlan->id }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                <input type="date" name="due_date" class="form-control"
                    value="{{ $paymentPlan->due_date->format('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">مبلغ الاستحقاق <span class="text-danger">*</span></label>
                <input type="number" name="due_amount" class="form-control" value="{{ $paymentPlan->due_amount }}"
                    step="0.01" min="0.01" required>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" class="btn btn-primary" id="updatePaymentPlan">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ
    </button>
</div>
