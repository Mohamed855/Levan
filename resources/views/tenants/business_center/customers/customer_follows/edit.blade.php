<div class="modal-header">
    <h5 class="modal-title fw-bold">تعديل المتابعة - {{ $customer->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="editCustomerFollowForm" data-customer-id="{{ $customer->id }}" data-follow-id="{{ $customerFollow->id }}"
        novalidate>
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">وسيلة الاتصال <span class="text-danger">*</span></label>
                <select name="contact_method_id" id="edit_contact_method_id" class="form-control select2-modal"
                    data-placeholder="اختر وسيلة الاتصال" required>
                    <option value=""></option>
                    @if (isset($contact_methods))
                        @foreach ($contact_methods as $method)
                            <option value="{{ $method->id }}"
                                {{ $customerFollow->contact_method_id == $method->id ? 'selected' : '' }}>
                                {{ $method->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">مكان الزيارة</label>
                <input type="text" name="contact_location" id="edit_contact_location" class="form-control"
                    maxlength="255" value="{{ $customerFollow->contact_location }}"
                    placeholder="أدخل مكان الزيارة إن وجد" />
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">الشخص المتصل به </label>
                <select name="contacted_person" id="edit_contacted_person" class="form-control select2-modal"
                    data-placeholder="اختر الشخص المتصل به">
                    <option value=""></option>
                    <x-form.controls.enum-options :enum="\App\Enums\Tenants\Customers\ContactedPerson::class"
                        :selected="old('contacted_person', $customerFollow->contacted_person)" />
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6" id="contacted_person_name_field" style="display: none;">
                <label class="form-label fw-semibold">اسم الشخص المتصل به </label>
                <input type="text" name="contacted_person_name" id="edit_contacted_person_name" class="form-control"
                    maxlength="255" value="{{ $customerFollow->contacted_person_name }}"
                    placeholder="أدخل اسم الشخص المتصل به" />
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">حالة العميل </label>
                <select name="customer_status_id" id="edit_customer_status_id" class="form-control select2-modal"
                    data-placeholder="اختر حالة العميل">
                    <option value=""></option>
                    @if (isset($customer_statuses))
                        @foreach ($customer_statuses as $status)
                            <option value="{{ $status->id }}"
                                {{ $customerFollow->customer_status_id == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">نتيجة الاتصال <span class="text-danger">*</span></label>
                <select name="contact_result_id" id="edit_contact_result_id" class="form-control select2-modal"
                    data-placeholder="اختر نتيجة الاتصال" required>
                    <option value=""></option>
                    @if (isset($contact_results))
                        @foreach ($contact_results as $result)
                            <option value="{{ $result->id }}"
                                {{ $customerFollow->contact_result_id == $result->id ? 'selected' : '' }}>
                                {{ $result->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="col-12 mt-3" id="edit_contact_result_section" style="display: none;">
                <div class="border rounded p-3">
                    <h6 class="mb-3 fw-semibold">تفاصيل نتيجة الاتصال</h6>
                    <div id="edit_contact_result_fields_container">
                        <!-- الحقول ستظهر هنا بشكل منظم -->
                    </div>
                </div>
            </div>

            <!-- حقل الملاحظات -->
            <div class="col-12 mt-3">
                <label class="form-label fw-semibold">ملاحظات</label>
                <textarea name="notes" id="edit_notes" class="form-control" rows="3" maxlength="2000"
                    placeholder="أدخل أي ملاحظات إضافية...">{{ $customerFollow->notes }}</textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" id="updateCustomerFollow" class="btn btn-primary">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        تحديث المتابعة
    </button>
</div>

<!-- إضافة بيانات التفاصيل الحالية لاستخدامها في JavaScript -->
<script>
    window.currentFollowDetails = {
        contact_result_id: {{ $customerFollow->contact_result_id }},
        @if ($customerFollow->fullPaymentDetails)
            fullPaymentDetails: {
                payment_amount: {{ $customerFollow->fullPaymentDetails->payment_amount }},
                payment_date: "{{ $customerFollow->fullPaymentDetails->payment_date }}",
                payment_type: "{{ $customerFollow->fullPaymentDetails->payment_type }}",
                collection_type: "{{ $customerFollow->fullPaymentDetails->collection_type ?? 'cash' }}",
                payment_method_id: {{ $customerFollow->fullPaymentDetails->payment_method_id }},
                payment_receipt_path: "{{ $customerFollow->fullPaymentDetails->payment_receipt_path ?? '' }}"
            },
        @endif

        @if ($customerFollow->partialPaymentDetails)
            partialPaymentDetails: {
                partial_payment_amount: {{ $customerFollow->partialPaymentDetails->partial_payment_amount }},
                partial_payment_date: "{{ $customerFollow->partialPaymentDetails->partial_payment_date }}",
                partial_payment_type: "{{ $customerFollow->partialPaymentDetails->partial_payment_type }}",
                collection_type: "{{ $customerFollow->partialPaymentDetails->collection_type ?? 'scheduled' }}",
                payment_method_id: {{ $customerFollow->partialPaymentDetails->payment_method_id }},
                next_partial_payment_date: "{{ $customerFollow->partialPaymentDetails->next_partial_payment_date ?? '' }}",
                partial_payment_receipt_path: "{{ $customerFollow->partialPaymentDetails->partial_payment_receipt_path ?? '' }}"
            },
        @endif

        @if ($customerFollow->promisePaymentDetails)
            promisePaymentDetails: {
                promise_amount: {{ $customerFollow->promisePaymentDetails->promise_amount }},
                promise_date: "{{ $customerFollow->promisePaymentDetails->promise_date }}"
            },
        @endif
        @if ($customerFollow->callbackDetails)
            callbackDetails: {
                callback_date: "{{ $customerFollow->callbackDetails->callback_date }}",
                callback_reason: "{{ $customerFollow->callbackDetails->callback_reason }}",
                callback_reason_other: "{{ $customerFollow->callbackDetails->callback_reason_other }}"
            },
        @endif
        @if ($customerFollow->documentRequestDetails)
            documentRequestDetails: {
                documents_date: "{{ $customerFollow->documentRequestDetails->documents_date }}",
                documents_reason: "{{ $customerFollow->documentRequestDetails->documents_reason }}",
                documents_reason_id: {{ $customerFollow->documentRequestDetails->documents_reason_id }}
            },
        @endif
        @if ($customerFollow->objectionDetails)
            objectionDetails: {
                objection_reason: "{{ $customerFollow->objectionDetails->objection_reason }}"
            },
        @endif
        @if ($customerFollow->recoveryDetails)
            recoveryDetails: {
                recovery_reason: "{{ $customerFollow->recoveryDetails->recovery_reason }}",
                recovery_reason_other: "{{ $customerFollow->recoveryDetails->recovery_reason_other }}"
            },
        @endif
        @if ($customerFollow->temporaryContactLossDetails)
            temporaryContactLossDetails: {
                temporary_contact_reason: "{{ $customerFollow->temporaryContactLossDetails->temporary_contact_reason }}",
                temporary_contact_reason_other: "{{ $customerFollow->temporaryContactLossDetails->temporary_contact_reason_other ?? '' }}"
            },
        @endif
    };
</script>
