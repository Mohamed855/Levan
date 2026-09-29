{{-- ملف: resources/views/business_center/customers/customer_sms/create.blade.php --}}
<div class="modal-header">
    <h5 class="modal-title">إرسال رسالة نصية</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <form id="createSmsForm">
        @csrf

        <div class="row g-3">
            <!-- اختيار رقم الهاتف -->
            <div class="col-12">
                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                <select name="phone_number" id="phone_number" class="form-control select2-phone" required>
                    <option value="">اختر رقم الهاتف</option>
                    @foreach($phoneNumbers as $phone)
                        <option value="{{ $phone['phone'] }}" data-type="{{ $phone['type'] }}">
                            {{ $phone['phone'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="phone_type" id="phone_type">
            </div>

            <!-- اختيار القالب -->
            <div class="col-12">
                <label class="form-label">قالب الرسالة <span class="text-danger">*</span></label>
                <select name="template_id" id="template_id" class="form-control select2-template" required>
                    <option value="">اختر قالب الرسالة</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- معاينة القالب -->
            <div class="col-12" id="template-preview-container" style="display: none;">
                <label class="form-label">معاينة الرسالة</label>
                <div id="template-preview" class="border rounded p-3 bg-light">
                    <!-- سيتم عرض معاينة القالب هنا -->
                </div>
                <div class="form-text">
                    <i class="ti ti-info-circle me-1"></i>
                    هذه معاينة للرسالة مع بيانات العميل الحقيقية
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" class="btn btn-primary" id="sendSmsButton" disabled>
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        إرسال الرسالة
    </button>
</div>

<script>
    // بيانات أرقام الهواتف
    window.phoneNumbers = @json($phoneNumbers);
    window.customerId = {{ $customer->id }};
</script>
