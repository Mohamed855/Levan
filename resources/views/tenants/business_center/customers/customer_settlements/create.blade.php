<div class="modal-header">
    <h5 class="modal-title">إضافة تسوية جديدة</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <form id="createSettlementForm" enctype="multipart/form-data">
        @csrf
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">التسويات</h6>
            <button type="button" class="btn btn-outline-primary btn-sm js-add-settlement-field-btn">
                إضافة تسوية
            </button>
        </div>

        <!-- حاوي الحقول الديناميكية -->
        <div id="settlements-container"></div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" class="btn btn-primary" id="saveSettlements">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ
    </button>
</div>

<script>
    window.settlementTypes = @json($settlementTypes);
</script>
