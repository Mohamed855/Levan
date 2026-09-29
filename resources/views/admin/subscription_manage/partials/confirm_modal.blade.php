<div class="modal fade" id="subscriptionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subscriptionConfirmModalTitle">تأكيد الإجراء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <div id="subscriptionConfirmModalIcon" class="avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-label-success">
                        <i class="ti ti-check ti-lg"></i>
                    </div>
                </div>
                <h5 id="subscriptionConfirmModalHeading" class="mb-2">تأكيد العملية</h5>
                <p id="subscriptionConfirmModalMessage" class="text-muted mb-0">هل تريد المتابعة في هذا الإجراء؟</p>

                <div id="subscriptionRejectReasonContainer" class="mt-4 text-start d-none">
                    <label for="subscription_reject_reason" class="form-label fw-medium">سبب الرفض <span class="text-muted small">(اختياري)</span></label>
                    <textarea class="form-control" id="subscription_reject_reason" rows="3" placeholder="أدخل سبب الرفض هنا..."></textarea>
                    <div id="subscription_reject_reason_error" class="text-danger small mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center border-top-0 pt-0 pb-4">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="subscriptionConfirmModalSubmitBtn">تأكيد</button>
            </div>
        </div>
    </div>
</div>
