@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الباقة - ' . $plan->name)

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
            let isSubmitting = false;

            $submitBtn.on('click', function(e) {
                e.preventDefault();

                if (isSubmitting) {
                    return false;
                }

                const featuresCount = $('#features-list .feature-item').length;
                if (featuresCount === 0) {
                    toastr.error('يجب إضافة ميزة واحدة على الأقل');
                    return false;
                }

                isSubmitting = true;
                $submitBtn.prop('disabled', true).addClass('disabled');
                $submitBtn.html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>جاري الحفظ...'
                );
                $('.btn-prev').prop('disabled', true);

                sessionStorage.removeItem(`wizard-draft:${new URL($form.attr('action')).pathname}`);
                $form[0].submit();
            });
        });
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            @if (!empty($hasSubscriptions))
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="ti ti-alert-triangle fs-3 me-2"></i>
                    <div>
                        <strong>تنبيه: هذه الباقة مرتبطة باشتراكات سابقة أو حالية.</strong>
                        تم قفل تعديل (كود الباقة، دورة الفوترة، ووضع الحدود) حفاظاً على سلامة العقود التاريخية. التعديلات في الأسعار والميزات تسري فقط على الاشتراكات الجديدة.
                    </div>
                </div>
            @endif

            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">البيانات والتسعير والحدود</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية والحصص</span>
                            </span>
                        </button>
                    </div>

                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#features-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الميزات</span>
                                <span class="bs-stepper-subtitle">ميزات الباقة التسويقية</span>
                            </span>
                        </button>
                    </div>

                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#review-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">المراجعة والنشر</span>
                                <span class="bs-stepper-subtitle">معاينة الباقة وتأكيد التحديث</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.plans.update', $plan->id) }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        {{-- الخطوة الأولى: البيانات الأساسية والتسعير والحدود --}}
                        <div id="info-validation" class="content">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                        <i class="ti ti-info-circle me-1"></i>البيانات الأساسية
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="name">اسم الباقة <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name', $plan->name) }}" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="code">كود الباقة (English Uppercase) <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="code" class="form-control font-monospace text-uppercase"
                                        value="{{ old('code', $plan->code) }}" maxlength="50"
                                        @if (!empty($hasSubscriptions)) readonly @endif required />
                                    @if (!empty($hasSubscriptions))
                                        <small class="text-warning">الكود مقفل لارتباط الباقة باشتراكات.</small>
                                    @else
                                        <small class="text-muted">يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط (A-Z, 0-9, _).</small>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="plan_type">نوع الباقة <span class="text-danger">*</span></label>
                                    <select id="plan_type" name="plan_type" class="form-select select2" data-placeholder="اختر نوع الباقة" required>
                                        <option value=""></option>
                                        @php
                                            $currentType = $plan->plan_type instanceof \App\Enums\Admin\Plans\PlanType ? $plan->plan_type->value : (string) $plan->plan_type;
                                        @endphp
                                        @foreach (\App\Enums\Admin\Plans\PlanType::cases() as $type)
                                            <option value="{{ $type->value }}" @selected(old('plan_type', $currentType) === $type->value)>
                                                {{ $type->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="color">لون الباقة المميز <span class="text-danger">*</span></label>
                                    <select id="color" name="color" class="form-select select2" data-placeholder="اختر لون الباقة" required>
                                        <option value=""></option>
                                        @php
                                            $currentColor = $plan->color instanceof \App\Enums\Admin\Plans\PlanColor ? $plan->color->value : (string) $plan->color;
                                        @endphp
                                        @foreach (\App\Enums\Admin\Plans\PlanColor::cases() as $col)
                                            <option value="{{ $col->value }}" @selected(old('color', $currentColor) === $col->value)>
                                                {{ $col->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="available_from">تاريخ بدء الإتاحة</label>
                                    <input type="date" name="available_from" id="available_from" class="form-control"
                                        value="{{ old('available_from', $plan->available_from ? $plan->available_from->format('Y-m-d') : '') }}" />
                                    <small class="text-muted">اتركه فارغاً لإتاحة الباقة فوراً، أو حدد موعداً مستقبلياً.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="is_active">حالة تفعيل الباقة <span class="text-danger">*</span></label>
                                    <select id="is_active" name="is_active" class="form-select select2" data-placeholder="اختر الحالة" required>
                                        <option value=""></option>
                                        <option value="1" @selected(old('is_active', (string)(int)$plan->is_active) === '1')>مفعلة (تظهر في القوائم)</option>
                                        <option value="0" @selected(old('is_active', (string)(int)$plan->is_active) === '0')>معطلة (مخفية عن العملاء)</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                        <i class="ti ti-cash me-1"></i>التسعير ودورة الفوترة
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="subscription_billing_cycle">دورة الفوترة الأساسية <span class="text-danger">*</span></label>
                                    @php
                                        $currentCycle = $plan->subscription_billing_cycle instanceof \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle
                                            ? $plan->subscription_billing_cycle->value
                                            : (string) $plan->subscription_billing_cycle;
                                    @endphp
                                    @if (!empty($hasSubscriptions))
                                        <input type="text" class="form-control" value="{{ $plan->subscription_billing_cycle?->label() ?? $currentCycle }}" disabled />
                                        <input type="hidden" name="subscription_billing_cycle" value="{{ $currentCycle }}" />
                                        <small class="text-warning">دورة الفوترة مقفلة لارتباط الباقة باشتراكات.</small>
                                    @else
                                        <select id="subscription_billing_cycle" name="subscription_billing_cycle" class="form-select select2" data-placeholder="اختر الدورة" required>
                                            <option value=""></option>
                                            @foreach (\App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases() as $cycle)
                                                <option value="{{ $cycle->value }}" @selected(old('subscription_billing_cycle', $currentCycle) === $cycle->value)>
                                                    {{ $cycle->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="price">سعر الدورة الأساسية (ر.س) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="price" id="price" class="form-control"
                                        value="{{ old('price', $plan->price) }}" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="discount_percentage">نسبة الخصم (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" name="discount_percentage" id="discount_percentage" class="form-control"
                                        value="{{ old('discount_percentage', $plan->discount_percentage) }}" />
                                </div>

                                <div class="col-md-6" id="yearly_renewal_price_container">
                                    <label class="form-label" for="yearly_renewal_price">سعر العرض السنوي الترويجي (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="yearly_renewal_price" id="yearly_renewal_price" class="form-control"
                                        value="{{ old('yearly_renewal_price', $plan->yearly_renewal_price) }}" />
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                        <i class="ti ti-adjustments me-1"></i>وضع الحدود والحصص (Limits Mode)
                                    </h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="limits_mode">وضع الحدود <span class="text-danger">*</span></label>
                                    @php
                                        $currentLimitsMode = $plan->limits_mode instanceof \App\Enums\Admin\Plans\PlanLimitsMode
                                            ? $plan->limits_mode->value
                                            : (string) ($plan->limits_mode ?? 'limited');
                                    @endphp
                                    @if (!empty($hasSubscriptions))
                                        <input type="text" class="form-control" value="{{ $plan->isUnlimited() ? 'غير محدود' : 'محدود' }}" disabled />
                                        <input type="hidden" name="limits_mode" id="limits_mode" value="{{ $currentLimitsMode }}" />
                                        <small class="text-warning">وضع الحدود مقفل لارتباط الباقة باشتراكات قائمة.</small>
                                    @else
                                        <select id="limits_mode" name="limits_mode" class="form-select select2" data-placeholder="اختر وضع الحدود" required>
                                            <option value="limited" @selected(old('limits_mode', $currentLimitsMode) === 'limited')>محدود (تطبق الحصص المحددة)</option>
                                            <option value="unlimited" @selected(old('limits_mode', $currentLimitsMode) === 'unlimited')>غير محدود (∞ لا توجد قيود استخدام)</option>
                                        </select>
                                    @endif
                                </div>

                                <div class="col-12" id="unlimited-mode-alert" style="display: none;">
                                    <div class="alert alert-primary d-flex align-items-center mb-0">
                                        <i class="ti ti-infinity fs-3 me-2"></i>
                                        <div>
                                            <strong>وضع الباقة غير المحدودة:</strong>
                                            لن يتم فرض أي حدود على عدد المستخدمين، العقارات، التخزين، المزادات، أو الفروع خلال مدة الاشتراك.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="included_users">عدد المستخدمين المشمولين <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="1" name="included_users" id="included_users" class="form-control"
                                        value="{{ old('included_users', $plan->included_users) }}" />
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="extra_user_license_price">سعر ترخيص المستخدم الإضافي (ر.س) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="extra_user_license_price" id="extra_user_license_price" class="form-control"
                                        value="{{ old('extra_user_license_price', $plan->extra_user_license_price) }}" />
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="properties_limit">حد العقارات <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="1" name="properties_limit" id="properties_limit" class="form-control"
                                        value="{{ old('properties_limit', $plan->properties_limit) }}" />
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="storage_quota_gb">حصة التخزين (جيجابايت) <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="1" name="storage_quota_gb" id="storage_quota_gb" class="form-control"
                                        value="{{ old('storage_quota_gb', $plan->storage_quota_gb) }}" />
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="active_auctions_limit">حد المزادات النشطة <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="1" name="active_auctions_limit" id="active_auctions_limit" class="form-control"
                                        value="{{ old('active_auctions_limit', $plan->active_auctions_limit) }}" />
                                </div>

                                <div class="col-md-6 limits-field">
                                    <label class="form-label" for="unlimited_branches">نوع حد الفروع <span class="text-danger">*</span></label>
                                    <select id="unlimited_branches" name="unlimited_branches" class="form-select select2">
                                        <option value="0" @selected(old('unlimited_branches', (string)(int)$plan->unlimited_branches) === '0')>محدود بعدد معين</option>
                                        <option value="1" @selected(old('unlimited_branches', (string)(int)$plan->unlimited_branches) === '1')>فروع غير محدودة</option>
                                    </select>
                                </div>

                                <div class="col-md-6 limits-field" id="branches_limit_container">
                                    <label class="form-label" for="branches_limit">حد الفروع المسموحة <span class="text-danger">*</span></label>
                                    <input type="number" step="1" min="1" name="branches_limit" id="branches_limit" class="form-control"
                                        value="{{ old('branches_limit', $plan->branches_limit) }}" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="domain_management_enabled">ميزة إدارة الدومينات <span class="text-danger">*</span></label>
                                    <select name="domain_management_enabled" id="domain_management_enabled" class="form-select select2" required>
                                        <option value="1" @selected(old('domain_management_enabled', (string)(int)$plan->domain_management_enabled) === '1')>مفعّلة</option>
                                        <option value="0" @selected(old('domain_management_enabled', (string)(int)$plan->domain_management_enabled) === '0')>غير مفعّلة</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="description">وصف الباقة</label>
                                    <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $plan->description) }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="admin_notes">ملاحظات إدارية (داخلية للإدارة فقط)</label>
                                    <textarea name="admin_notes" id="admin_notes" class="form-control" rows="2">{{ old('admin_notes', $plan->admin_notes) }}</textarea>
                                </div>

                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary waves-effect">
                                        <i class="ti ti-arrow-left me-1"></i>رجوع
                                    </a>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light">
                                        التالي <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- الخطوة الثانية: الميزات --}}
                        <div id="features-validation" class="content">
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-primary"><i class="ti ti-list-check me-1"></i>ميزات الباقة التسويقية</h6>
                                            <small class="text-muted">هذه الميزات للعرض والتسويق أمام العملاء في صفحات التسجيل والـ Checkout.</small>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-add-feature">
                                            <i class="ti ti-plus me-1"></i>إضافة ميزة
                                        </button>
                                    </div>
                                    <div id="features-list">
                                        @foreach (old('features', $plan->features ?? []) as $index => $feature)
                                            <div class="border rounded p-3 mb-3 feature-item bg-light">
                                                <div class="d-flex align-items-end gap-2">
                                                    <div class="flex-grow-1">
                                                        <label class="form-label mb-1 small">نص الميزة</label>
                                                        <input type="text" name="features[{{ $index }}]" class="form-control"
                                                            value="{{ $feature }}" required />
                                                    </div>
                                                    <button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" title="حذف الميزة">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev waves-effect">
                                        <i class="ti ti-arrow-left me-1"></i>السابق
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next waves-effect waves-light">
                                        التالي (المراجعة والنشر) <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- الخطوة الثالثة: المراجعة والنشر --}}
                        <div id="review-validation" class="content">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div id="publish-status-alert" class="alert alert-success d-flex align-items-center mb-0">
                                        <i class="ti ti-check-circle fs-3 me-2" id="publish-status-icon"></i>
                                        <div id="publish-status-text">
                                            الباقة نشطة ومتاحة الآن، ستظهر للعملاء في قوائم الاختيار والـ Checkout فور النشر.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card border shadow-none bg-light">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                                <div>
                                                    <h5 class="fw-bold mb-1" id="review-plan-name">-</h5>
                                                    <span class="badge bg-label-dark font-monospace" id="review-plan-code">-</span>
                                                    <span class="badge bg-label-primary" id="review-plan-type">-</span>
                                                    <span class="badge bg-label-info" id="review-billing-cycle">-</span>
                                                    <span class="badge bg-label-success" id="review-limits-mode">-</span>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fs-4 fw-bold text-success" id="review-final-price">0.00 ر.س</div>
                                                    <div class="small text-muted" id="review-price-breakdown">السعر الأساسي: 0.00 ر.س</div>
                                                </div>
                                            </div>

                                            <div class="row g-3 border-top pt-3">
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">العرض السنوي</small>
                                                    <strong id="review-annual-offer">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">المستخدمين</small>
                                                    <strong id="review-users">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">العقارات</small>
                                                    <strong id="review-properties">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">التخزين</small>
                                                    <strong id="review-storage">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">المزادات النشطة</small>
                                                    <strong id="review-auctions">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">الفروع</small>
                                                    <strong id="review-branches">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">إدارة الدومينات</small>
                                                    <strong id="review-domains">-</strong>
                                                </div>
                                                <div class="col-md-3 col-6">
                                                    <small class="text-muted d-block">بدء الإتاحة</small>
                                                    <strong id="review-available-from">متاحة فوراً</strong>
                                                </div>
                                            </div>

                                            <div class="border-top pt-3 mt-3">
                                                <h6 class="fw-bold mb-2">الميزات المشمولة:</h6>
                                                <ul class="mb-0 ps-3" id="review-features-list">
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev waves-effect">
                                        <i class="ti ti-arrow-left me-1"></i>السابق
                                    </button>
                                    <button type="button" class="btn btn-success waves-effect waves-light btn-submit">
                                        <i class="ti ti-device-floppy me-1"></i>تحديث وحفظ الباقة
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
        <div class="border rounded p-3 mb-3 feature-item bg-light">
            <div class="d-flex align-items-end gap-2">
                <div class="flex-grow-1">
                    <label class="form-label mb-1 small">نص الميزة</label>
                    <input type="text" name="features[{index}]" class="form-control"
                        placeholder="مثال: لوحة تحكم مخصصة" required />
                </div>
                <button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" title="حذف الميزة">
                    <i class="ti ti-trash fs-5"></i>
                </button>
            </div>
        </div>
    </template>
@endsection
