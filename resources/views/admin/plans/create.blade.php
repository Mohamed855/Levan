@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة باقة جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/plans/form-validation.js'])
    
    <script>
        $(document).ready(function() {
            const $form = $('#wizard-validation-form');
            const $submitBtn = $('.btn-submit');
            const originalText = $submitBtn.html();
            let isSubmitting = false;
            
            // منع الإرسال الافتراضي وإعادة الإرسال يدوياً
            $submitBtn.on('click', function(e) {
                e.preventDefault();
                
                // منع الإرسال المتكرر
                if (isSubmitting) {
                    return false;
                }
                
                // التحقق من وجود ميزات
                const featuresCount = $('#features-list .feature-item').length;
                if (featuresCount === 0) {
                    toastr.error('يجب إضافة ميزة واحدة على الأقل');
                    return false;
                }
                
                // تعيين حالة الإرسال
                isSubmitting = true;
                
                // تعطيل الزر وتغيير النص
                $submitBtn.prop('disabled', true).addClass('disabled');
                $submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>جاري الحفظ...');
                
                // تعطيل زر السابق
                $('.btn-prev').prop('disabled', true);
                
                // إرسال الفورم
                $form[0].submit();
            });
        });
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات الباقة</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية</span>
                            </span>
                        </button>
                    </div>

                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#features-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الميزات</span>
                                <span class="bs-stepper-subtitle">مميزات الباقة</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.plans.store') }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf

                        {{-- الخطوة الأولى: المعلومات الأساسية --}}
                        <div id="info-validation" class="content">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="name">اسم الباقة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name') }}" placeholder="مثال: الباقة الاحترافية" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="price">
                                        سعر الباقة
                                        <span class="text-danger">*</span></label>
                                    <input type="number" step="1" name="price" id="price" class="form-control"
                                        value="{{ old('price') }}" placeholder="500" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="discount_percentage">
                                        نسبة الخصم (%)
                                    </label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        name="discount_percentage" id="discount_percentage" class="form-control"
                                        value="{{ old('discount_percentage', 0) }}" placeholder="مثال: 5 يمثل 5٪" />
                                </div>

                                {{-- سعر التجديد السنوي --}}
                                <div class="col-md-6">
                                    <label class="form-label" for="yearly_renewal_price">
                                        سعر التجديد السنوي
                                    </label>
                                    <input type="number" step="0.01" min="0" name="yearly_renewal_price"
                                        id="yearly_renewal_price" class="form-control"
                                        value="{{ old('yearly_renewal_price') }}" placeholder="مثال: 5000" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="extra_user_license_price">
                                        سعر ترخيص المستخدم الإضافي
                                    </label>
                                    <input type="number" step="0.01" min="0" name="extra_user_license_price"
                                        id="extra_user_license_price" class="form-control"
                                        value="{{ old('extra_user_license_price') }}" placeholder="مثال: 5000" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="included_users">
                                        عدد المستخدمين
                                        <span class="text-danger">*</span></label>
                                    <input type="number" step="1" name="included_users" id="included_users"
                                        class="form-control" value="{{ old('included_users') }}" placeholder="10"
                                        required />
                                </div>

                                <div class="col-md-6">
                                <div class="col-md-6"><label class="form-label" for="subscription_duration_type">نوع مدة الاشتراك <span class="text-danger">*</span></label><select id="subscription_duration_type" name="subscription_duration_type" class="form-select"><option value="limited" @selected(old('subscription_duration_type', 'limited') === 'limited')>محددة</option><option value="unlimited" @selected(old('subscription_duration_type') === 'unlimited')>غير محددة</option></select></div>
                                <div class="col-md-6" id="billing-cycle-container"><label class="form-label" for="subscription_billing_cycle">دورة الفوترة <span class="text-danger">*</span></label><select id="subscription_billing_cycle" name="subscription_billing_cycle" class="form-select"><option value="monthly" @selected(old('subscription_billing_cycle') === 'monthly')>شهري</option><option value="yearly" @selected(old('subscription_billing_cycle', 'yearly') === 'yearly')>سنوي</option></select></div>
                                    <label for="unlimited_branches" class="form-label">
                                        نوع حد الفروع
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="unlimited_branches" name="unlimited_branches" class="form-select select2"
                                        data-placeholder="اختر النوع">
                                        <option value=""></option>
                                        <option value="0" {{ old('unlimited_branches') == '0' ? 'selected' : '' }}>
                                            محدود</option>
                                        <option value="1" {{ old('unlimited_branches') == '1' ? 'selected' : '' }}>غير
                                            محدود</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="branches_limit_container">
                                    <label class="form-label" for="branches_limit">
                                        عدد الفروع
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" step="1" name="branches_limit" id="branches_limit"
                                        class="form-control" value="{{ old('branches_limit') }}"
                                        placeholder="ادخل العدد" min="1" />
                                </div>

                                <div class="col-md-6">
                                    <label for="domain_management_enabled" class="form-label">إدارة الدومينات</label>
                                    <select name="domain_management_enabled" id="domain_management_enabled"
                                        class="form-select select2" data-placeholder="اختر حالة إدارة الدومينات">
                                        <option value=""></option>
                                        <option value="0"
                                            {{ old('domain_management_enabled') == '0' ? 'selected' : '' }}>غير مفعلة
                                        </option>
                                        <option value="1"
                                            {{ old('domain_management_enabled') == '1' ? 'selected' : '' }}>مفعلة</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="is_active" class="form-label">حالة الباقة <span
                                            class="text-danger">*</span></label>
                                    <select id="is_active" name="is_active" class="form-select select2"
                                        data-placeholder="اختر الحالة">
                                        <option value=""></option>

                                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>
                                            غير مفعلة
                                        </option>
                                        <option value="1" {{ old('is_active') == 1 ? 'selected' : '' }}>
                                            مفعلة
                                        </option>

                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="description">الوصف</label>
                                    <textarea name="description" id="description" class="form-control" rows="3"
                                        placeholder="وصف تفصيلي للباقة ومميزاتها...">{{ old('description') }}</textarea>
                                </div>

                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- الخطوة الثانية: الميزات --}}
                        <div id="features-validation" class="content">
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label mb-0">ميزات الباقة <span
                                                class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-add-feature">
                                            <i class="bx bx-plus me-1"></i> إضافة ميزة
                                        </button>
                                    </div>
                                    <div id="features-list">
                                        {{-- سيتم إضافة الميزات هنا ديناميكياً --}}
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span>السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary waves-effect waves-light btn-submit">
                                        <i class="bx bx-save me-1"></i>
                                        <span>حفظ </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Templates --}}
    <template id="feature-template">
        <div class="border rounded p-3 mb-3 feature-item">
            <div class="row align-items-end g-2">
                <div class="col-md-11">
                    <label class="form-label mb-1 small">اسم الميزة</label>
                    <input type="text" name="features[{index}]" class="form-control"
                        placeholder="مثال: دعم فني على مدار الساعة" required />
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger w-50 btn-remove-feature" title="حذف">
                        <i class="ti ti-trash fs-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
@endsection
