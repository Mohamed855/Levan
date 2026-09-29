<!-- مودال إضافة رقم هاتف جديد -->
<div class="modal fade" id="addPhoneModal" tabindex="-1" aria-labelledby="addPhoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPhoneModalLabel">إضافة رقم هاتف جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPhoneForm">
                    @csrf
                    <input type="hidden" id="phone_customer_id" name="customer_id">
                    
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="phone_number" 
                               name="phone" 
                               required
                               maxlength="10"
                               pattern="[0-9]{10}"
                               title="يجب أن يتكون رقم الهاتف من 10 أرقام فقط"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" id="savePhoneBtn" class="btn btn-primary btn-sm">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    إضافة
                </button>
            </div>
        </div>
    </div>
</div>