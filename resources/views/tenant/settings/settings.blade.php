@extends('tenant.layouts.layoutMaster')

@section('title', 'إعدادات النظام')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection


@section('page-style')
    <style>
        .input-group.has-select2 {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: stretch !important;
        }

        .input-group.has-select2 > .select2-container {
            display: flex !important;
            flex-direction: column !important;
            justify-content: stretch !important;
            flex: 1 1 auto !important;
            width: 1% !important;
            min-width: 0 !important;
            margin: 0 !important;
        }

        .input-group.has-select2 > .select2-container > .selection {
            display: flex !important;
            flex: 1 1 auto !important;
            width: 100% !important;
            height: 100% !important;
        }

        .input-group.has-select2 > .select2-container > .selection > .select2-selection {
            display: flex !important;
            flex: 1 1 auto !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 42px !important;
            border-start-end-radius: 0 !important;
            border-end-end-radius: 0 !important;
            border-inline-end: 0 !important;
            box-sizing: border-box !important;
        }

        .input-group.has-select2 > .btn-select-all-regions {
            align-self: stretch !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: auto !important;
            min-height: 42px !important;
            max-height: none !important;
            border-start-start-radius: 0 !important;
            border-end-start-radius: 0 !important;
            border-inline-start: 0 !important;
            white-space: nowrap !important;
            user-select: none;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0.55rem 0.85rem !important;
        }

        /* Validation border styling for Invalid state */
        .input-group.has-select2.is-invalid,
        .input-group.has-select2:has(.is-invalid),
        .input-group.has-select2:has(.select2-container.is-invalid),
        [data-form-field].is-invalid .input-group.has-select2,
        [data-form-field].has-error .input-group.has-select2 {
            border-color: var(--bs-danger, #ff3e1d) !important;
        }

        .input-group.has-select2.is-invalid > .select2-container .select2-selection,
        .input-group.has-select2:has(.is-invalid) > .select2-container .select2-selection,
        .input-group.has-select2:has(.select2-container.is-invalid) > .select2-container .select2-selection,
        [data-form-field].is-invalid .input-group.has-select2 > .select2-container .select2-selection,
        [data-form-field].has-error .input-group.has-select2 > .select2-container .select2-selection,
        .select2-container.is-invalid .select2-selection {
            border-color: var(--bs-danger, #ff3e1d) !important;
            border-inline-end: 0 !important;
        }

        .input-group.has-select2.is-invalid > .btn-select-all-regions,
        .input-group.has-select2:has(.is-invalid) > .btn-select-all-regions,
        .input-group.has-select2:has(.select2-container.is-invalid) > .btn-select-all-regions,
        [data-form-field].is-invalid .input-group.has-select2 > .btn-select-all-regions,
        [data-form-field].has-error .input-group.has-select2 > .btn-select-all-regions {
            border-color: var(--bs-danger, #ff3e1d) !important;
            border-inline-start: 0 !important;
        }

        /* Validation border styling for Valid state */
        .input-group.has-select2.is-valid,
        .input-group.has-select2:has(.is-valid),
        .input-group.has-select2:has(.select2-container.is-valid),
        [data-form-field].is-valid .input-group.has-select2 {
            border-color: var(--bs-success, #71dd37) !important;
        }

        .input-group.has-select2.is-valid > .select2-container .select2-selection,
        .input-group.has-select2:has(.is-valid) > .select2-container .select2-selection,
        .input-group.has-select2:has(.select2-container.is-valid) > .select2-container .select2-selection,
        [data-form-field].is-valid .input-group.has-select2 > .select2-container .select2-selection,
        .select2-container.is-valid .select2-selection {
            border-color: var(--bs-success, #71dd37) !important;
            border-inline-end: 0 !important;
        }

        .input-group.has-select2.is-valid > .btn-select-all-regions,
        .input-group.has-select2:has(.is-valid) > .btn-select-all-regions,
        .input-group.has-select2:has(.select2-container.is-valid) > .btn-select-all-regions,
        [data-form-field].is-valid .input-group.has-select2 > .btn-select-all-regions {
            border-color: var(--bs-success, #71dd37) !important;
            border-inline-start: 0 !important;
        }
    </style>
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btn-select-all-regions');
            const select = document.getElementById('operational_regions');
            if (!btn || !select) return;

            function updateButtonState() {
                const totalOptions = select.options.length;
                const selectedCount = Array.from(select.selectedOptions).length;
                const textSpan = document.getElementById('select-all-regions-text');

                if (totalOptions > 0 && selectedCount === totalOptions) {
                    if (textSpan) textSpan.textContent = 'إلغاء التحديد';
                    btn.setAttribute('title', 'إلغاء تحديد جميع مناطق العمل');
                } else {
                    if (textSpan) textSpan.textContent = 'تحديد الكل';
                    btn.setAttribute('title', 'تحديد جميع مناطق العمل');
                }
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const totalOptions = select.options.length;
                const selectedCount = Array.from(select.selectedOptions).length;
                const shouldSelectAll = !(totalOptions > 0 && selectedCount === totalOptions);

                for (let i = 0; i < select.options.length; i++) {
                    select.options[i].selected = shouldSelectAll;
                }

                if (window.jQuery) {
                    window.jQuery(select).trigger('change');
                } else {
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.add('ti-spin');
                    setTimeout(() => icon.classList.remove('ti-spin'), 500);
                }

                updateButtonState();
            });

            if (window.jQuery) {
                window.jQuery(select).on('change', updateButtonState);
            } else {
                select.addEventListener('change', updateButtonState);
            }

            updateButtonState();
        });
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <form id="formGeneralSettings" method="POST" action="{{ tenant_route_name('settings.update') }}"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body pt-4">
                        <div class="row g-4">
                            <x-form.controls.file name="image" label="شعار الشركة" :value="$settings?->image ? tasset($settings->image) : null"
                                accept="image/png, image/jpeg, image/webp"
                                help="الملفات المسموح بها: JPG, PNG أو WebP. الأبعاد الموصى بها: 150px × 150px"
                                :max-size="10" col="col-12" />

                            <x-form.controls.input name="office_name" label="اسم الشركة" :value="old('office_name', $settings->office_name ?? '')"
                                placeholder="أدخل اسم الشركة" col="col-md-6" />

                            <x-form.controls.input name="logo_text" label="النص بجانب الشعار" :value="old('logo_text', $settings->logo_text ?? '')"
                                placeholder="أدخل النص بجانب الشعار" maxlength="14" col="col-md-6"
                                help="الحد الأقصى لعدد الأحرف: 14." />

                            <div class="col-12 mt-4 pt-3 border-top">
                                <h6 class="fw-bold mb-3">بيانات التواصل في تقارير المزادات</h6>
                                <div class="row g-4">
                                    <x-form.controls.input name="report_phone" label="هاتف التواصل" :value="old('report_phone', $settings->report_phone)" col="col-md-4" />
                                    <x-form.controls.input name="report_unified_phone" label="الرقم الموحد" :value="old('report_unified_phone', $settings->report_unified_phone)" col="col-md-4" />
                                    <x-form.controls.input name="report_social" label="حساب التواصل الاجتماعي" :value="old('report_social', $settings->report_social)" col="col-md-4" />
                                </div>
                                <div class="form-text mt-2">تُستخدم أحدث بيانات التواصل والشعار عند إعادة توليد التقارير؛ تحديث الإعدادات وحده لا يعيد توليدها.</div>
                            </div>
                            <div class="col-12 mt-4 pt-3 border-top">
                                <h6 class="fw-bold mb-3 d-flex align-items-center">
                                    <i class="ti ti-map-pin text-primary me-2"></i>مناطق العمل المعتمدة
                                </h6>
                                <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                                    <i class="ti ti-info-circle fs-3 me-2 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-bold mb-1">تحديد نطاق العمل الجغرافي للنظام (مناطق العمل)</div>
                                        <span class="small">
                                            حدد المناطق الإدارية التي يعمل بها مكتبك أو شركتك. المناطق التي يتم اختيارها
                                            وتحديدها هنا هي فقط التي ستظهر للمستخدمين في جميع القوائم المنسدلة الخاصة
                                            بالمناطق في النظام (نماذج إضافة وتعديل العقارات، وفلاتر البحث).
                                        </span>
                                    </div>
                                </div>

                                <x-form.controls.select name="operational_regions" id="operational_regions"
                                    label="مناطق العمل المتاحة" :options="$allRegions ?? []" :value="old('operational_regions', $settings->operational_regions ?? [])" multiple
                                    placeholder="اختر المناطق التي تعمل بها..." col="col-12" :input-group="true">
                                    <button type="button" class="input-group-text cursor-pointer btn-select-all-regions"
                                        id="btn-select-all-regions" title="تحديد جميع مناطق العمل">
                                        <i class="ti ti-check-double me-1 text-primary"></i> <span id="select-all-regions-text">تحديد الكل</span>
                                    </button>
                                </x-form.controls.select>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i>حفظ التعديلات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
