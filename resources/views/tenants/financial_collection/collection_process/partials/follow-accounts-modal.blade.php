<!-- Modal for selecting related accounts -->
<div class="modal fade" id="followAccountsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">تطبيق المتابعة على الحسابات المرتبطة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="accountsLoadingState" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0 small">جاري تحميل الحسابات...</p>
                </div>

                <!-- Accounts List -->
                <div id="accountsListContainer" style="display: none;">
                    <!-- Alert Info -->
                    <div class="alert alert-info mb-3 py-2">
                        <small class="d-flex align-items-start mb-0">
                            <i class="ti ti-info-circle me-2 mt-1 flex-shrink-0" style="font-size: 14px;"></i>
                            <span style="font-size: 0.85rem;">الحساب الحالي محدد افتراضياً. يمكنك اختيار حسابات
                                إضافية</span>
                        </small>
                    </div>

                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllAccounts">
                            <label class="form-check-label fw-semibold" for="selectAllAccounts"
                                style="font-size: 0.9rem;">
                                تحديد الكل
                            </label>
                        </div>
                    </div>

                    <div class="border rounded" style="max-height: 350px; overflow-y: auto;">
                        <div id="relatedAccountsList"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">
                    إلغاء
                </button>
                <button type="button" id="confirmAccountsSelection" class="btn btn-primary btn-sm">
                    تأكيد
                    <span class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>
</div>