<div class="modal-header">
    <h5 class="modal-title">إضافة تحصيل جديد - {{ $customer->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="createCustomerPaymentForm" data-customer-id="{{ $customer->id }}" novalidate enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="collected_amount">المبلغ المحصل <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="collected_amount" id="collected_amount" class="form-control" 
                           placeholder="0.00" step="0.01" min="0" required>
                    <span class="input-group-text">ريال</span>
                </div>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="collection_type">نوع التحصيل <span class="text-danger">*</span></label>
                <select name="collection_type" id="collection_type" class="form-control select2-modal" 
                        data-placeholder="اختر نوع التحصيل" required>
                    <option value=""></option>
                    <option value="cash">نقدي</option>
                    <option value="scheduled">مجدول</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="payment_method_id">طريقة التحصيل <span class="text-danger">*</span></label>
                <select name="payment_method_id" id="payment_method_id" class="form-control select2-modal" 
                        data-placeholder="اختر طريقة التحصيل" required>
                    <option value=""></option>
                    @foreach ($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="payment_type">نوع السداد <span class="text-danger">*</span></label>
                <select name="payment_type" id="payment_type" class="form-control select2-modal" 
                        data-placeholder="اختر نوع السداد" required>
                    <option value=""></option>
                    <option value="settlement">سداد بتسوية</option>
                    <option value="without_settlement">سداد بدون تسوية</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="collection_date">تاريخ التحصيل <span class="text-danger">*</span></label>
                <input type="date" name="collection_date" id="collection_date" class="form-control" 
                       value="{{ date('Y-m-d') }}" required>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="receipt_number">رقم السند <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" name="receipt_number" id="receipt_number" class="form-control" 
                           placeholder="رقم السند" value="{{ $receiptNumber }}" required>
                    <button type="button" class="btn btn-outline-secondary" id="generate-receipt" title="توليد رقم جديد">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-12">
                <label class="form-label" for="closing_code">رمز الإقفال</label>
                <input type="text" name="closing_code" id="closing_code" class="form-control" 
                       placeholder="رمز الإقفال (اختياري)" maxlength="50">
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-12">
                <label class="form-label" for="attachment">المرفق</label>
                <input type="file" name="attachment" id="attachment" class="form-control" 
                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                <div class="form-text">
                    أنواع الملفات المدعومة: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (الحد الأقصى: 10 ميجابايت)
                </div>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" id="saveCustomerPayment" class="btn btn-primary">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ التحصيل
    </button>
</div>

<script>
// توليد رقم سند جديد
$('#generate-receipt').on('click', function() {
    $.ajax({
        url: `/customers/${window.customerId}/payments/generate-receipt`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#receipt_number').val(response.receipt_number);
            }
        },
        error: function() {
            toastr.error('حدث خطأ أثناء توليد رقم السند');
        }
    });
});
</script>