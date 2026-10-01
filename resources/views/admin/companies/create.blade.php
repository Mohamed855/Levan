@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء شركة جديدة')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.plansData = @json($plans);
        window.wizardResourceKey = 'admin.company-create';
    </script>
    @vite(['resources/assets/js/admin/companies/create-wizard.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div id="company-wizard" class="bs-stepper">
                <!-- Stepper Header (4 Steps) -->
                <div class="bs-stepper-header">
                    <div class="step" data-target="#step-company">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">بيانات الشركة</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية والإعدادات</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-admin">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">المستخدم الإداري الأول</span>
                                <span class="bs-stepper-subtitle">حساب المسؤول الرئيسي</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-payment">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الاشتراك والدفع</span>
                                <span class="bs-stepper-subtitle">الباقة وحدودها وتفاصيل الدفع</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-review">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">4</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">مراجعة واعتماد</span>
                                <span class="bs-stepper-subtitle">تأكيد البيانات والحفظ</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="p-4 bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="company-wizard-form" action="{{ route('admin.companies.store') }}" method="POST"
                        enctype="multipart/form-data" autocomplete="off">
                        @csrf
                        <input type="hidden" name="_wizard_draft_id" id="_wizard_draft_id" value="">
                        <input type="hidden" name="_wizard_resource_key" value="admin.company-create">

                        {{-- ═══════════════════════════════════════
                             الخطوة 1: بيانات الشركة
                        ═══════════════════════════════════════ --}}
                        <div id="step-company" class="content">
                            <div class="row g-4">
                                <!-- اسم الشركة -->
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">
                                        اسم الشركة <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name') }}" placeholder="مثال: شركة التطوير العقاري" required>
                                </div>

                                <!-- السجل التجاري -->
                                <div class="col-md-6">
                                    <label for="commercial_number" class="form-label">
                                        رقم السجل التجاري <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="commercial_number"
                                        name="commercial_number" value="{{ old('commercial_number') }}"
                                        placeholder="10 أرقام" maxlength="10" required>
                                </div>

                                <!-- الرقم الضريبي -->
                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label">
                                        الرقم الضريبي <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number"
                                        value="{{ old('tax_number') }}" placeholder="الرقم الضريبي للشركة" required>
                                </div>

                                <!-- المدينة -->
                                <div class="col-md-6">
                                    <label for="city_id" class="form-label">
                                        المدينة <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" id="city_id" name="city_id" required
                                        data-placeholder="اختر المدينة">
                                        <option value=""></option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- العنوان -->
                                <div class="col-12">
                                    <label for="address" class="form-label">
                                        العنوان <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="2"
                                        placeholder="مثال: الرياض - طريق الملك فهد" required>{{ old('address') }}</textarea>
                                </div>

                                <!-- إعدادات الشركة الإضافية -->
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary mb-3">
                                        <i class="ti ti-settings me-1"></i>إعدادات الشركة الخاصة
                                    </h6>
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <div class="card border p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">تنبيهات البريد</h6>
                                                        <small class="text-muted">إرسال إشعارات البريد</small>
                                                    </div>
                                                    <div class="form-check form-switch mb-0">
                                                        <input type="checkbox" class="form-check-input"
                                                            name="email_notifications_enabled" value="1" checked>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card border p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">التحقق الثنائي (2FA)</h6>
                                                        <small class="text-muted">فرض 2FA على مستخدمي الشركة</small>
                                                    </div>
                                                    <div class="form-check form-switch mb-0">
                                                        <input type="checkbox" class="form-check-input"
                                                            name="two_factor_enabled" value="1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card border p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">التكاملات الخارجية</h6>
                                                        <small class="text-muted">السماح بربط API والأنظمة</small>
                                                    </div>
                                                    <div class="form-check form-switch mb-0">
                                                        <input type="checkbox" class="form-check-input"
                                                            name="external_integrations_enabled" value="1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary waves-effect">
                                    <i class="ti ti-arrow-left me-1"></i> إلغاء
                                </a>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary btn-save-draft" data-step="1">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next" id="btn-next-company">
                                        التالي <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 2: المستخدم الإداري الأول
                        ═══════════════════════════════════════ --}}
                        <div id="step-admin" class="content">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        اسم المستخدم المسؤول <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="اسم المسؤول" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">
                                        رقم الجوال <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        placeholder="05xxxxxxxx" value="{{ old('phone') }}" maxlength="10" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        البريد الإلكتروني <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="admin@domain.com" autocomplete="off"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="password">
                                        كلمة المرور <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="••••••••" autocomplete="new-password" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="password_confirmation">
                                        تأكيد كلمة المرور <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="••••••••" autocomplete="new-password"
                                        required />
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev" id="btn-prev-admin">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary btn-save-draft" data-step="2">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next" id="btn-next-admin">
                                        التالي <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 3: بيانات الاشتراك والدفع
                        ═══════════════════════════════════════ --}}
                        <div id="step-payment" class="content">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="plan_id" class="form-label">
                                        الباقة <span class="text-danger">*</span>
                                    </label>
                                    <select id="plan_id" name="plan_id" class="form-select select2" required
                                        data-placeholder="اختر الباقة">
                                        <option value=""></option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan['id'] }}" @selected(old('plan_id') == $plan['id'])>
                                                {{ $plan['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="start_date">
                                        تاريخ البداية <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="{{ old('start_date', now()->toDateString()) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="end_date">
                                        تاريخ النهاية
                                    </label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                    <small class="text-muted" id="end-date-hint">يُحسب تلقائياً حسب نوع الباقة</small>
                                </div>

                                {{-- <div class="col-12 mt-2">
                                    <div class="p-3 bg-light rounded border">
                                        <h6 class="mb-2 fw-semibold text-dark"><i
                                                class="ti ti-shield-check me-1 text-primary"></i>حدود الباقة المحددة
                                            (للقراءة فقط)</h6>
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <label class="form-label small">عدد المستخدمين المشمولين</label>
                                                <input type="text" class="form-control form-control-sm bg-white"
                                                    id="plan_users_display" readonly disabled value="-">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">حد العقارات</label>
                                                <input type="text" class="form-control form-control-sm bg-white"
                                                    id="plan_properties_display" readonly disabled value="-">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">مساحة التخزين (GB)</label>
                                                <input type="text" class="form-control form-control-sm bg-white"
                                                    id="plan_storage_display" readonly disabled value="-">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">عدد المزادات النشطة</label>
                                                <input type="text" class="form-control form-control-sm bg-white"
                                                    id="plan_auctions_display" readonly disabled value="-">
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="col-md-6">
                                    <label class="form-label" for="payment-status">
                                        حالة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر حالة الدفع"
                                        name="payment_type" id="payment-status" required>
                                        <option value=""></option>
                                        <option value="paid" @selected(old('payment_type') == 'paid')>تم الدفع</option>
                                        <option value="unpaid" @selected(old('payment_type', 'unpaid') == 'unpaid')>لم يتم الدفع</option>
                                        <option value="trial" @selected(old('payment_type') == 'trial')>فترة تجربة</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="payment-method-wrapper" style="display: none;">
                                    <label class="form-label" for="payment-method-select">
                                        وسيلة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" name="payment_method"
                                        data-placeholder="اختر وسيلة الدفع" id="payment-method-select">
                                        <option value=""></option>
                                        <option value="online" @selected(old('payment_method') == 'online')>دفع إلكتروني</option>
                                        <option value="bank_transfer" @selected(old('payment_method') == 'bank_transfer')>تحويل بنكي</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="receipt-upload-wrapper" style="display: none;">
                                    <label class="form-label" for="receipt-upload">
                                        إيصال التحويل البنكي <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" name="receipt_upload" id="receipt-upload"
                                        accept="image/*,.pdf">
                                    <small class="text-muted">ملف صورة أو PDF بحجم أقصى 5MB</small>
                                </div>

                                <div class="col-md-6" id="trial-date-wrapper" style="display: none;">
                                    <label class="form-label" for="trial-end-date">
                                        تاريخ نهاية التجربة <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="trial_end_date" id="trial-end-date"
                                        value="{{ old('trial_end_date') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="discount-type">هل يوجد خصم أو تسوية؟</label>
                                    <select class="form-select" id="discount-type" name="has_discount">
                                        <option value="no" @selected(old('has_discount') == 'no')>لا</option>
                                        <option value="yes" @selected(old('has_discount') == 'yes')>نعم</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="discount-wrapper" style="display: none;">
                                    <label class="form-label" for="discount_value">مبلغ الخصم / التسوية (ر.س)</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        id="discount_value" name="discount_value"
                                        value="{{ old('discount_value', 0) }}">
                                </div>

                                <!-- التفعيل الفوري -->
                                <div class="col-12 mt-3">
                                    <div class="card border p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark">تفعيل الشركة والاشتراك فوراً</h6>
                                                <small class="text-muted">عند التفعيل، تصبح الشركة والاشتراك بحالة نشطة
                                                    مباشرة حتى لو لم يتم الدفع</small>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input"
                                                    name="activate_immediately" id="activate_immediately" value="1"
                                                    @checked(old('activate_immediately', true))>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev" id="btn-prev-payment">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary btn-save-draft" data-step="3">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next" id="btn-next-payment">
                                        التالي <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 4: مراجعة واعتماد
                        ═══════════════════════════════════════ --}}
                        <div id="step-review" class="content">
                            <div class="row g-4">
                                <!-- Card 1: ملخص بيانات الشركة -->
                                <div class="col-12 col-lg-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-building me-1 text-primary"></i> ملخص بيانات الشركة
                                            </h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">اسم الشركة:</span>
                                                    <span class="fw-bold" id="review-company-name">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">السجل التجاري:</span>
                                                    <span class="fw-semibold" id="review-commercial-number">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">الرقم الضريبي:</span>
                                                    <span class="fw-semibold" id="review-tax-number">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">المدينة:</span>
                                                    <span class="fw-semibold" id="review-city">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">العنوان:</span>
                                                    <span class="fw-semibold" id="review-address">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between border-top pt-2">
                                                    <span class="text-muted">الإعدادات الإضافية:</span>
                                                    <span class="fw-semibold" id="review-settings-summary">-</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: ملخص المستخدم المسؤول -->
                                <div class="col-12 col-lg-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-user me-1 text-primary"></i> بيانات المستخدم المسؤول
                                            </h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">اسم المسؤول:</span>
                                                    <span class="fw-bold" id="review-admin-name">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">رقم الجوال:</span>
                                                    <span class="fw-semibold" id="review-admin-phone">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">البريد الإلكتروني:</span>
                                                    <span class="fw-semibold" id="review-admin-email">-</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: ملخص الباقة وحدودها والمميزات -->
                                <div class="col-12 col-lg-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-packages me-1 text-primary"></i> ملخص الباقة والحدود
                                            </h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">اسم الباقة:</span>
                                                    <span class="fw-bold text-primary" id="review-plan-name">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">المستخدمون المشمولون:</span>
                                                    <span class="fw-semibold" id="review-plan-users">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">حد العقارات:</span>
                                                    <span class="fw-semibold" id="review-plan-props">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">مساحة التخزين:</span>
                                                    <span class="fw-semibold" id="review-plan-storage">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">المزادات النشطة:</span>
                                                    <span class="fw-semibold" id="review-plan-auctions">-</span>
                                                </li>
                                                <li class="border-top pt-2">
                                                    <span class="text-muted d-block mb-1">مميزات الباقة:</span>
                                                    <div id="review-plan-features" class="d-flex flex-wrap gap-1">-</div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 4: ملخص الاشتراك والدفع -->
                                <div class="col-12 col-lg-6">
                                    <div class="card border shadow-none h-100">
                                        <div class="card-header py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-credit-card me-1 text-primary"></i> تفاصيل الاشتراك والدفع
                                            </h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">تاريخ البدء:</span>
                                                    <span class="fw-semibold" id="review-start-date">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">تاريخ النهاية:</span>
                                                    <span class="fw-semibold" id="review-end-date">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">حالة التفعيل:</span>
                                                    <span class="badge" id="review-activation-status">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">حالة الدفع:</span>
                                                    <span class="badge" id="review-payment-status">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">وسيلة الدفع:</span>
                                                    <span class="fw-semibold" id="review-payment-method">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between border-top pt-2 mt-1">
                                                    <span class="fw-bold">المبلغ الإجمالي:</span>
                                                    <span class="fw-bold text-success fs-6" id="review-final-total">0.00
                                                        ر.س</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- رسالة التنبيه الإلزامية -->
                                <div class="col-12">
                                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                                        <i class="ti ti-alert-triangle ti-md me-2"></i>
                                        <div>
                                            <strong>تنبيه هام:</strong> تفعيل الشركة والاشتراك فوراً سيجعل الشركة والاشتراك
                                            بحالة <strong>نشطة (Active)</strong> ومتاحة للاستخدام المباشر فور الاعتماد، حتى
                                            لو لم يتم الدفع بعد.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev" id="btn-prev-review">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary btn-save-draft" data-step="4">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-submit">
                                        <i class="ti ti-check me-1"></i> إنشاء واعتماد الشركة
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
