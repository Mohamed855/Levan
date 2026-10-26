<x-bootstrap.modal id="subscriptionConfirmModal" title="تأكيد الإجراء" labelled-by="subscriptionConfirmModalTitle" centered>
    <div class="text-center py-4">
        <div class="mb-3">
            <div id="subscriptionConfirmModalIcon"
                class="avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-success">
                <i class="ti ti-check ti-lg" aria-hidden="true"></i>
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

    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-success" id="subscriptionConfirmModalSubmitBtn">تأكيد</button>
    </x-slot:footer>
</x-bootstrap.modal>
