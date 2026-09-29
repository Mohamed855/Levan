<div class="modal-header">
    <h5 class="modal-title">تعديل التحصيل - {{ $customer->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="editCustomerPaymentForm" data-customer-id="{{ $customer->id }}" data-payment-id="{{ $collection->id }}"
        novalidate enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="edit_collected_amount">المبلغ المحصل <span
                        class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="collected_amount" id="edit_collected_amount" class="form-control"
                        placeholder="0.00" step="0.01" min="0" value="{{ $collection->collected_amount }}" required>
                    <span class="input-group-text">ريال</span>
                </div>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="edit_collection_type">نوع التحصيل <span
                        class="text-danger">*</span></label>
                <select name="collection_type" id="edit_collection_type" class="form-control select2-modal"
                    data-placeholder="اختر نوع التحصيل" required>
                    <option value=""></option>
                    <option value="cash" {{ $collection->collection_type == 'cash' ? 'selected' : '' }}>نقدي</option>
                    <option value="scheduled" {{ $collection->collection_type == 'scheduled' ? 'selected' : '' }}>مجدول
                    </option>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="edit_payment_method_id">طريقة التحصيل <span
                        class="text-danger">*</span></label>
                <select name="payment_method_id" id="edit_payment_method_id" class="form-control select2-modal"
                    data-placeholder="اختر طريقة التحصيل" required>
                    <option value=""></option>
                    @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}" {{ $collection->payment_method_id == $method->id ? 'selected' : ''
                        }}>
                        {{ $method->name }}
                    </option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="edit_payment_type">نوع السداد <span class="text-danger">*</span></label>
                <select name="payment_type" id="edit_payment_type" class="form-control select2-modal"
                    data-placeholder="اختر نوع السداد" required>
                    <option value=""></option>
                    <option value="settlement" {{ $collection->payment_type == 'settlement' ? 'selected' : '' }}>سداد
                        بتسوية</option>
                    <option value="without_settlement" {{ $collection->payment_type == 'without_settlement' ? 'selected'
                        : '' }}>سداد بدون تسوية</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="edit_collection_date">تاريخ التحصيل <span
                        class="text-danger">*</span></label>
                <input type="date" name="collection_date" id="edit_collection_date" class="form-control"
                    value="{{ $collection->collection_date?->format('Y-m-d') }}" required>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="edit_receipt_number">رقم السند <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" name="receipt_number" id="edit_receipt_number" class="form-control"
                        placeholder="رقم السند" value="{{ $collection->receipt_number }}" required>
                    <button type="button" class="btn btn-outline-secondary" id="edit-generate-receipt"
                        title="توليد رقم جديد">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-12">
                <label class="form-label" for="edit_closing_code">رمز الإقفال</label>
                <input type="text" name="closing_code" id="edit_closing_code" class="form-control"
                    placeholder="رمز الإقفال (اختياري)" value="{{ $collection->closing_code }}" maxlength="50">
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-12">
                <label class="form-label" for="edit_attachment">المرفق</label>

                @if($collection->attachment)
                <div class="mb-2 p-2 border rounded bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-paperclip me-2 text-primary"></i>
                            <div>
                                <small class="text-muted d-block">المرفق الحالي:</small>
                                <a href="{{ asset('storage/' . $collection->attachment) }}" target="_blank"
                                    class="text-primary fw-medium">
                                    {{ basename($collection->attachment) }}
                                </a>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_attachment"
                                id="remove_attachment">
                            <label class="form-check-label text-danger" for="remove_attachment">
                                <small>حذف المرفق</small>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                <input type="file" name="attachment" id="edit_attachment" class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                <div class="form-text">
                    أنواع الملفات المدعومة: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (الحد الأقصى: 10 ميجابايت)
                    @if($collection->attachment)
                    <br><small class="text-info">اختر ملف جديد لاستبدال المرفق الحالي، أو اتركه فارغاً للاحتفاظ بالمرفق
                        الحالي</small>
                    @endif
                </div>
                <div class="invalid-feedback"></div>
            </div>

            @if($collection->status !== 'pending')
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-2"></i>
                    <strong>تنبيه:</strong> هذا التحصيل تمت مراجعته بالفعل وحالته:
                    <span class="badge {{ $collection->status_class }}">{{ $collection->status_label }}</span>
                    @if($collection->status === 'rejected' && $collection->rejection_reason)
                    <br><small>سبب الرفض: {{ $collection->rejection_reason }}</small>
                    @endif
                    @if($collection->approvedBy)
                    <br><small>تمت المراجعة بواسطة: {{ $collection->approvedBy->name }} في {{
                        $collection->approved_at?->format('Y-m-d H:i') }}</small>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
    @if($collection->status === 'pending')
    <button type="button" id="updateCustomerPayment" class="btn btn-primary">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        تحديث التحصيل
    </button>
    @else
    <button type="button" class="btn btn-secondary" disabled>
        لا يمكن التعديل بعد المراجعة
    </button>
    @endif
</div>

<script>
    // توليد رقم سند جديد في التعديل
$('#edit-generate-receipt').on('click', function() {
    $.ajax({
        url: `/customers/${window.customerId}/payments/generate-receipt`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#edit_receipt_number').val(response.receipt_number);
                // إزالة رسائل الخطأ إن وجدت
                $('#edit_receipt_number').removeClass('is-invalid');
                $('#edit_receipt_number').siblings('.invalid-feedback').remove();
            }
        },
        error: function() {
            toastr.error('حدث خطأ أثناء توليد رقم السند');
        }
    });
});

// التحكم في عرض/إخفاء خيار حذف المرفق
$('#remove_attachment').on('change', function() {
    const isChecked = $(this).is(':checked');
    const attachmentInput = $('#edit_attachment');
    
    if (isChecked) {
        attachmentInput.prop('disabled', true);
        attachmentInput.closest('.col-12').find('.form-text').addClass('text-muted');
    } else {
        attachmentInput.prop('disabled', false);
        attachmentInput.closest('.col-12').find('.form-text').removeClass('text-muted');
    }
});

// التحقق من تغيير الملف الجديد
$('#edit_attachment').on('change', function() {
    if ($(this).val()) {
        $('#remove_attachment').prop('checked', false);
    }
});
</script>