@extends('tenants.layouts.layoutMaster')


@section('title', 'إضافة عقد جديد')


@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection


@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection


@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection


@section('page-script')
    <script>
        window.routes = {
            generateNumber: "{{ tenant_route_name('contracts.generate-number') }}"
        };
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenants/business-center/contracts/validation.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تدمير الـ select2 لو اتعمل initialize على commission_types
            setTimeout(function() {
                if ($('#commission_types').hasClass('select2-hidden-accessible')) {
                    $('#commission_types').select2('destroy');
                }
            }, 100);

            const checkboxes = document.querySelectorAll('input[name="commission_types[]"]');
            const $commissionTypes = $('#commission_types');

            function updateCommissionTypes() {
                const selectedValues = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // عمل trigger للـ change event عشان الـ JS الأصلي يشتغل
                $commissionTypes.trigger('change');
            }

            // Override الـ .val() function للـ #commission_types
            (function() {
                const $element = $('#commission_types');

                $element.data('customVal', function(value) {
                    if (value === undefined) {
                        // Get value
                        return Array.from(checkboxes)
                            .filter(cb => cb.checked)
                            .map(cb => cb.value);
                    } else {
                        // Set value
                        checkboxes.forEach(cb => {
                            cb.checked = value && value.includes(cb.value);
                        });
                    }
                });

                // Override val() for this specific element
                const originalVal = $.fn.val;
                $.fn.val = function(value) {
                    if (this.attr('id') === 'commission_types') {
                        const customVal = this.data('customVal');
                        if (customVal) {
                            return customVal.call(this, value);
                        }
                    }
                    return originalVal.apply(this, arguments);
                };
            })();

            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateCommissionTypes);
            });

            // تشغيل مرة واحدة في البداية
            setTimeout(updateCommissionTypes, 200);
        });
    </script>
@endsection


@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2" data-form-stepper>
                <div class="bs-stepper-header">
                    <div class="step" data-target="#contract-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">بيانات العقد والشركة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#commission-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">نظام العمولة والملاحظات</span>
                                <span class="bs-stepper-subtitle">شرائح العمولات</span>
                            </span>
                        </button>
                    </div>
                </div>


                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <form id="wizard-validation-form" action="{{ tenant_route_name('contracts.store') }}" method="POST"
                        novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                        @csrf


                        <div id="contract-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="company_id">الشركة <span
                                            class="text-danger">*</span></label>
                                    <select id="company_id" name="company_id" class="select2 form-select" required
                                        data-placeholder="اختر شركة">
                                        <option></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="col-sm-6">
                                    <label class="form-label" for="contract_number">رقم العقد</label>
                                    <input type="text" id="contract_number" name="contract_number" class="form-control"
                                        value="{{ old('contract_number', $contractNumber) }}" placeholder="ادخل رقم العقد">
                                </div>


                                <div class="col-sm-4">
                                    <label class="form-label" for="start_date">تاريخ البداية <span
                                            class="text-danger">*</span></label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" required
                                        value="{{ old('start_date') }}">
                                </div>


                                <div class="col-sm-4">
                                    <label class="form-label" for="end_date">تاريخ النهاية <span
                                            class="text-danger">*</span></label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" required
                                        value="{{ old('end_date') }}">
                                </div>


                                <div class="col-sm-4">
                                    <label class="form-label" for="duration_readonly">المدة</label>
                                    <input type="text" id="duration_readonly" class="form-control" value="0"
                                        disabled>
                                    <input type="hidden" id="duration_months" name="duration_months"
                                        value="{{ old('duration_months') }}">
                                </div>


                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span>السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next" disabled>
                                            <span class="me-sm-2">التالي</span>
                                            <i class="ti ti-arrow-right ti-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div id="commission-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-12">
                                    <label class="form-label">أنواع نظام العمولة <span class="text-danger">*</span></label>
                                    <div id="commission_types" class="d-flex gap-4 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="commission_types[]"
                                                value="procedure" id="commission_type_procedure"
                                                {{ in_array('procedure', old('commission_types', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="commission_type_procedure">
                                                حسب الإجراء
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="commission_types[]"
                                                value="duration" id="commission_type_duration"
                                                {{ in_array('duration', old('commission_types', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="commission_type_duration">
                                                حسب المدة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="commission_types[]"
                                                value="delinquency" id="commission_type_delinquency"
                                                {{ in_array('delinquency', old('commission_types', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="commission_type_delinquency">
                                                حسب مدة التعثر
                                            </label>
                                        </div>
                                    </div>
                                    <input type="hidden" id="commission_type" name="commission_type"
                                        value="{{ old('commission_type') }}">
                                </div>


                                <div class="col-12">
                                    <div id="commission-type-buttons" class="mb-3 d-none">
                                        <div class="d-flex gap-2 flex-wrap"></div>
                                    </div>
                                    <div id="commissions-wrapper"></div>
                                </div>


                                <div class="col-12">
                                    <div class="d-flex justify-content-start">
                                        <button type="button" class="btn btn-primary d-none" id="add-commission-btn">
                                            <i class="ti ti-plus me-1"></i>إضافة شريحة عمولة
                                        </button>
                                    </div>
                                </div>


                                <div class="col-12">
                                    <label class="form-label" for="notes">ملاحظات</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="اكتب أي ملاحظات...">{{ old('notes') }}</textarea>
                                </div>


                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span>السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <span>حفظ</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>


            </div>
        </div>
    </div>


    <template id="commission-row-template">
        <div class="commission-row border rounded p-3 mb-3 bg-white" data-index="__INDEX__" data-type="__TYPE__">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="mb-0 text-primary fw-bold">
                    <i class="ti ti-percentage me-1"></i>
                    شريحة <span class="commission-type-label">__TYPE_LABEL__</span> -
                    <span class="commission-number">__NUMBER__</span>
                </h6>
                <button type="button" class="btn btn-danger btn-sm remove-commission-btn" title="حذف الشريحة">
                    <i class="ti ti-trash"></i>
                </button>
            </div>


            <div class="row g-4">
                <input type="hidden" name="commissions[__INDEX__][commission_type]" value="__TYPE__">


                <div class="col-md-4 procedure-type-col" style="display:none;">
                    <label class="form-label fw-semibold">نوع الإجراء <span class="text-danger">*</span></label>
                    <select name="commissions[__INDEX__][procedure_type]" class="select2-commission form-select">
                        <option value="">اختر نوع الإجراء</option>
                        <option value="friendly">ودي</option>
                        <option value="legal">قانوني</option>
                    </select>
                </div>


                <div class="col-md-4 duration-from-col duration-fields" style="display:none;">
                    <label class="form-label fw-semibold">من (يوم) <span class="text-danger">*</span></label>
                    <input type="number" name="commissions[__INDEX__][duration_from]" class="form-control"
                        placeholder="1" min="1" step="1">
                </div>

                <div class="col-md-4 duration-to-col duration-fields" style="display:none;">
                    <label class="form-label fw-semibold">إلى (يوم) <span class="text-danger">*</span></label>
                    <input type="number" name="commissions[__INDEX__][duration_to]" class="form-control"
                        placeholder="30" min="1" step="1">
                </div>

                <div class="col-md-4 commission-rate-col">
                    <label class="form-label fw-semibold">نسبة العمولة (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="commissions[__INDEX__][commission_rate]"
                        class="form-control commission-rate-input" placeholder="0.00" min="0.01" max="100">
                </div>
            </div>
        </div>
    </template>
@endsection
