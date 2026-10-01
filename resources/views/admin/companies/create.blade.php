@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء شركة جديدة')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.plansData = @json($plans);
    </script>
    @vite(['resources/assets/js/admin/subscriptions/create-form.js'])
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
            <div id="subscription-wizard" class="bs-stepper">
                <!-- Stepper Header (4 Steps) -->
                <div class="bs-stepper-header">
                    <div class="step" data-target="#step-company">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">بيانات الشركة</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-admin">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">بيانات المستخدم</span>
                                <span class="bs-stepper-subtitle">المستخدم الرئيسي</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-payment">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الاشتراك والدفع</span>
                                <span class="bs-stepper-subtitle">تفاصيل الباقة والدفع</span>
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
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="subscription-form" action="{{ route('admin.companies.store') }}" method="POST"
                        enctype="multipart/form-data" autocomplete="off">
                        @csrf

                        {{-- ═══════════════════════════════════════
                             الخطوة 1: بيانات الشركة
                        ═══════════════════════════════════════ --}}
                        <div id="step-company" class="content">
                            <div class="row g-3">
                                <!-- اسم الشركة -->
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">
                                        اسم الشركة <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name') }}" placeholder="مثال: شركة المجد العقارية" required>
                                </div>

                                <!-- رقم السجل التجاري -->
                                <div class="col-md-6">
                                    <label for="commercial_number" class="form-label">
                                        رقم السجل التجاري <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                        class="form-control" id="commercial_number" name="commercial_number"
                                        placeholder="مثال: 1010123456" value="{{ old('commercial_number') }}" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                </div>

                                    <!-- الرقم الضريبي -->
                                    <div class="col-md-6">
                                        <label for="tax_number" class="form-label">
                                            الرقم الضريبي <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="tax_number" name="tax_number"
                                            value="{{ old('tax_number') }}" autocomplete="one-time-code" inputmode="numeric"
                                            pattern="[0-9]{15}" maxlength="15"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)"
                                            placeholder="مثال: 300000000000003" required>
                                    </div>

                                <!-- المدينة -->
                                <div class="col-md-6">
                                    <label for="city_id" class="form-label">
                                        المدينة <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" id="city_id" name="city_id"
                                        data-placeholder="اختر المدينة" required>
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
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light"
                                    id="btn-prev-company">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-secondary btn-save-draft waves-effect waves-light" data-step="1">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next waves-effect waves-light"
                                            id="btn-next-company">
                                            التالي <i class="ti ti-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 2: بيانات المستخدم
                        ═══════════════════════════════════════ --}}
                        <div id="step-admin" class="content">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        اسم المستخدم <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="اسم المستخدم" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">
                                        رقم الجوال <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" inputmode="numeric" pattern="05[0-9]{8}" maxlength="10"
                                        class="form-control" id="phone" name="phone" placeholder="05xxxxxxxx"
                                        value="{{ old('phone') }}" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        البريد الإلكتروني <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="example@domain.com" autocomplete="off" required>
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
                                        class="form-control" placeholder="••••••••" autocomplete="new-password" required />
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light"
                                    id="btn-prev-admin">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-secondary btn-save-draft waves-effect waves-light" data-step="2">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next waves-effect waves-light"
                                            id="btn-next-admin">
                                            التالي <i class="ti ti-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 3: الاشتراك والدفع
                        ═══════════════════════════════════════ --}}
                        <div id="step-payment" class="content">
                            <div class="row g-3">
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
                                        تاريخ النهاية <span class="text-danger" id="end-date-required">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                    <small class="text-muted" id="end-date-hint"></small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="total_users">
                                        إجمالي المستخدمين <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="1" class="form-control" id="total_users"
                                        name="total_users" value="{{ old('total_users') }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="payment-status">
                                        حالة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر حالة الدفع"
                                        name="payment_type" id="payment-status" required>
                                        <option value=""></option>
                                        <option value="paid" @selected(old('payment_type') == 'paid')>تم الدفع</option>
                                        <option value="unpaid" @selected(old('payment_type') == 'unpaid')>لم يتم الدفع</option>
                                        <option value="trial" @selected(old('payment_type') == 'trial')>فترة تجربة</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="payment-method-wrapper">
                                    <label class="form-label" for="payment-method-select">
                                        وسيلة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" name="payment_method"
                                        data-placeholder="اختر وسيلة الدفع" id="payment-method-select">
                                        <option value=""></option>
                                        <option value="cash" @selected(old('payment_method') == 'cash')>نقداً</option>
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
                                    <small class="text-muted">يرجى رفع صورة أو ملف PDF للإيصال البنكي</small>
                                </div>

                                <div class="col-md-6" id="discount-type-wrapper">
                                    <label class="form-label" for="discount-type">التسوية (الخصم)</label>
                                    <select class="form-select select2" name="has_discount" id="discount-type"
                                        data-placeholder="هل يوجد تسوية">
                                        <option value=""></option>
                                        <option value="no" @selected(old('has_discount', 'no') == 'no')>بدون تسوية</option>
                                        <option value="yes" @selected(old('has_discount') == 'yes')>دفع بتسوية</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="trial-end-date-wrapper" style="display: none;">
                                    <label class="form-label" for="trial-end-date">
                                        تاريخ نهاية التجربة <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="trial_end_date" id="trial-end-date"
                                        value="{{ old('trial_end_date') }}" min="{{ now()->toDateString() }}">
                                </div>

                                <div class="col-md-6" id="discount-value-wrapper" style="display: none;">
                                    <label class="form-label" for="discount-value-input">قيمة التسوية (ر.س)</label>
                                    <input type="number" class="form-control" name="discount_value"
                                        id="discount-value-input" min="0" step="0.01"
                                        value="{{ old('discount_value', 0) }}">
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input type="hidden" name="activate_immediately" value="0">
                                        <input class="form-check-input" type="checkbox" id="activate_immediately"
                                            name="activate_immediately" value="1" @checked(old('activate_immediately', false))>
                                        <label class="form-check-label fw-bold" for="activate_immediately">تفعيل الشركة
                                            فورًا</label>
                                    </div>
                                    <small class="text-muted">عند التفعيل، تصبح الشركة والاشتراك بحالة (نشط) مباشرة، وإلا
                                        تظل (قيد المراجعة).</small>
                                </div>

                                <!-- ملخص التسعير الداخلي للخطوة 3 -->
                                <div class="col-12 mt-2">
                                    <div class="card border shadow-none mt-3">
                                        <div class="card-header py-2 bg-light">
                                            <h6 class="mb-0 card-title small fw-bold">
                                                <i class="ti ti-calculator text-primary me-1"></i> ملخص التسعير
                                            </h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-sm text-center table-bordered mb-0 small">
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-bold">الباقة المختارة</td>
                                                            <td id="final-plan-name">-</td>
                                                            <td class="fw-bold">سعر الباقة</td>
                                                            <td><span id="final-plan-price">0.00</span> ر.س</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold">ضريبة القيمة المضافة (15%)</td>
                                                            <td><span id="final-vat">0.00</span> ر.س</td>
                                                            <td class="fw-bold">الخصم / التسوية</td>
                                                            <td class="text-danger">- <span
                                                                    id="final-discount">0.00</span> ر.س</td>
                                                        </tr>
                                                        <tr class="table-primary">
                                                            <td colspan="2" class="fw-bold">الإجمالي النهائي المستحق
                                                            </td>
                                                            <td colspan="2" class="fw-bold fs-6 text-primary">
                                                                <span id="final-total">0.00</span> ر.س
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light"
                                    id="btn-prev-payment">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-secondary btn-save-draft waves-effect waves-light" data-step="3">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next waves-effect waves-light"
                                            id="btn-next-payment">
                                            التالي <i class="ti ti-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 4: مراجعة واعتماد
                        ═══════════════════════════════════════ --}}
                        <div id="step-review" class="content">
                            <div class="row g-4">
                                <!-- Card 1: بيانات الشركة -->
                                <div class="col-12 col-lg-4">
                                    <div class="card border shadow-none h-100">
                                        <div
                                            class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-building me-1 text-primary"></i> بيانات الشركة
                                            </h6>
                                            <button type="button" class="btn btn-xs btn-label-primary btn-edit-step"
                                                data-target-step="0">
                                                <i class="ti ti-edit me-1"></i> تعديل
                                            </button>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">اسم الشركة:</span>
                                                    <span class="fw-bold text-heading" id="review-company-name">-</span>
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
                                                    <span class="fw-semibold text-wrap text-end" id="review-address"
                                                        style="max-width: 60%;">-</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: بيانات المستخدم -->
                                <div class="col-12 col-lg-4">
                                    <div class="card border shadow-none h-100">
                                        <div
                                            class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-user me-1 text-primary"></i> بيانات المستخدم المسؤول
                                            </h6>
                                            <button type="button" class="btn btn-xs btn-label-primary btn-edit-step"
                                                data-target-step="1">
                                                <i class="ti ti-edit me-1"></i> تعديل
                                            </button>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">اسم المسؤول:</span>
                                                    <span class="fw-bold text-heading" id="review-admin-name">-</span>
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

                                <!-- Card 3: تفاصيل الاشتراك والدفع -->
                                <div class="col-12 col-lg-4">
                                    <div class="card border shadow-none h-100">
                                        <div
                                            class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                            <h6 class="card-title mb-0 small fw-bold">
                                                <i class="ti ti-credit-card me-1 text-primary"></i> تفاصيل الاشتراك والدفع
                                            </h6>
                                            <button type="button" class="btn btn-xs btn-label-primary btn-edit-step"
                                                data-target-step="2">
                                                <i class="ti ti-edit me-1"></i> تعديل
                                            </button>
                                        </div>
                                        <div class="card-body pt-3">
                                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">الباقة:</span>
                                                    <span class="fw-bold text-primary" id="review-plan-name">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">تاريخ البدء:</span>
                                                    <span class="fw-semibold" id="review-start-date">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">تاريخ النهاية:</span>
                                                    <span class="fw-semibold" id="review-end-date">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">المستخدمين:</span>
                                                    <span class="fw-semibold" id="review-total-users">-</span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="text-muted">التفعيل الفوري:</span>
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
                                                <li class="d-flex justify-content-between border-top pt-2 mt-2">
                                                    <span class="fw-bold">المبلغ الإجمالي:</span>
                                                    <span class="fw-bold text-success fs-6" id="review-final-total">0.00
                                                        ر.س</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light"
                                    id="btn-prev-review">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-secondary btn-save-draft waves-effect waves-light" data-step="4">
                                        <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit"
                                            class="btn btn-primary btn-next btn-submit waves-effect waves-light">
                                            <i class="ti ti-check me-1"></i> اعتماد وحفظ نهائي
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
@endsection
