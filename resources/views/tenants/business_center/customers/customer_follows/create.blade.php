<div class="modal-header">
    <h5 class="modal-title fw-bold">إضافة متابعة جديدة - {{ $customer->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="createCustomerFollowForm" data-customer-id="{{ $customer->id }}" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">وسيلة الاتصال <span class="text-danger">*</span></label>
                <select name="contact_method_id" id="contact_method_id" class="form-control select2-modal"
                    data-placeholder="اختر وسيلة الاتصال" required>
                    <option value=""></option>
                    @if (isset($contact_methods))
                        @foreach ($contact_methods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">مكان الزيارة</label>
                <input type="text" name="contact_location" id="contact_location" class="form-control" maxlength="255"
                    placeholder="أدخل مكان الزيارة إن وجد" />
                <div class="invalid-feedback"></div>
            </div>

            <!-- الصف الثاني: الشخص المتصل به -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">الشخص المتصل به </label>
                <select name="contacted_person" id="contacted_person" class="form-control select2-modal"
                    data-placeholder="اختر الشخص المتصل به">
                    <option value=""></option>
                    <x-form.controls.enum-options :enum="\App\Enums\Tenants\Customers\ContactedPerson::class"
                        :selected="old('contacted_person')" />
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6" id="contacted_person_name_field" style="display: none;">
                <label class="form-label fw-semibold">اسم الشخص المتصل به </label>
                <input type="text" name="contacted_person_name" id="contacted_person_name" class="form-control"
                    maxlength="255" placeholder="أدخل اسم الشخص المتصل به" />
                <div class="invalid-feedback"></div>
            </div>

            <!-- الصف الثالث: حالة العميل ونتيجة الاتصال -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">حالة العميل </label>
                <select name="customer_status_id" id="customer_status_id" class="form-control select2-modal"
                    data-placeholder="اختر حالة العميل">
                    <option value=""></option>
                    @if (isset($customer_statuses))
                        @foreach ($customer_statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">نتيجة الاتصال <span class="text-danger">*</span></label>
                <select name="contact_result_id" id="contact_result_id" class="form-control select2-modal"
                    data-placeholder="اختر نتيجة الاتصال" required>
                    <option value=""></option>
                    @if (isset($contact_results))
                        @foreach ($contact_results as $result)
                            <option value="{{ $result->id }}">{{ $result->name }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <!-- قسم الحقول الديناميكية - يظهر فقط عند اختيار نتيجة اتصال -->
            <div class="col-12 mt-3" id="contact_result_section" style="display: none;">
                <div class="border rounded p-3">
                    <h6 class="mb-3 fw-semibold">تفاصيل نتيجة الاتصال</h6>
                    <div id="contact_result_fields_container">
                        <!-- الحقول ستظهر هنا بشكل منظم -->
                    </div>
                </div>
            </div>

            <!-- حقل الملاحظات -->
            <div class="col-12 mt-3">
                <label class="form-label fw-semibold">ملاحظات</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="2000"
                    placeholder="أدخل أي ملاحظات إضافية..."></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" id="saveCustomerFollow" class="btn btn-primary">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ المتابعة
    </button>
</div>
