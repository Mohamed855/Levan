<div class="modal-header">
    <h5 class="modal-title">إضافة خطة سداد جديدة</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <form id="createPaymentPlanForm">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">خطط السداد</h6>
            <button type="button" class="btn btn-outline-primary btn-sm js-add-payment-plan-field-btn">
                إضافة خطة
            </button>
        </div>

        <!-- حاوي الحقول الديناميكية -->
        <div id="payment-plans-container"></div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" class="btn btn-primary" id="savePaymentPlans">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ
    </button>
</div>
