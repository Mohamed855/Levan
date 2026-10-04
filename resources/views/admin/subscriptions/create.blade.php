@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة اشتراك جديد')

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
        window.companiesData = @json($companies);
        window.plansData = @json($plans);
        window.wizardResourceKey = 'admin.subscription-create';
    </script>
    @vite(['resources/assets/js/admin/subscriptions/create-wizard.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1 fw-bold">إضافة اشتراك جديد</h4>
                    <p class="text-muted mb-0">إنشاء اشتراك جديد لشركة انتهى اشتراكها السابق</p>
                </div>
                <div>
                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-right me-1"></i> العودة لقائمة الاشتراكات
                    </a>
                </div>
            </div>

            <div id="subscription-wizard" class="bs-stepper">
                <!-- Stepper Header (3 Steps) -->
                <div class="bs-stepper-header">
                    <div class="step" data-target="#step-company-plan">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الشركة والباقة</span>
                                <span class="bs-stepper-subtitle">اختيار الشركة وتحديد الباقة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-payment-details">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">تفاصيل الاشتراك والدفع</span>
                                <span class="bs-stepper-subtitle">المدة، حالة الدفع والتفعيل</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-subscription-review">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">مراجعة واعتماد</span>
                                <span class="bs-stepper-subtitle">تأكيد البيانات وإنشاء الاشتراك</span>
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

                    <form id="subscription-wizard-form" action="{{ route('admin.subscriptions.store') }}" method="POST"
                        enctype="multipart/form-data" autocomplete="off">
                        @csrf
                        <input type="hidden" name="_wizard_draft_id" id="_wizard_draft_id" value="">
                        <input type="hidden" name="_wizard_resource_key" value="admin.subscription-create">

                        {{-- ═══════════════════════════════════════
                             الخطوة 1: اختيار الشركة والباقة
                        ═══════════════════════════════════════ --}}
                        <div id="step-company-plan" class="content">
                            <div class="row g-4">
                                <!-- اختيار الشركة -->
                                <div class="col-md-6">
                                    <label for="company_id" class="form-label">
                                        الشركة المستفيدة <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" id="company_id" name="company_id" required
                                        data-placeholder="اختر الشركة (فقط الشركات ذات الاشتراكات المنتهية)">
                                        <option value=""></option>
                                        @foreach ($companies as $comp)
                                            <option value="{{ $comp->id }}" @selected(old('company_id') == $comp->id)>
                                                {{ $comp->company_name }} ({{ $comp->commercial_number ?? $comp->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted">
                                        يتم عرض الشركات التي ليس لها اشتراك حالي أو انتهت صلاحية آخر اشتراك لها فقط.
                                    </div>
                                </div>

                                <!-- اختيار الباقة -->
                                <div class="col-md-6">
                                    <label for="plan_id" class="form-label">
                                        الباقة المطلوبة <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" id="plan_id" name="plan_id" required
                                        data-placeholder="اختر الباقة">
                                        <option value=""></option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)
                                                data-price="{{ $plan->price_after_discount }}"
                                                data-users="{{ $plan->included_users }}"
                                                data-properties="{{ $plan->properties_limit }}"
                                                data-storage="{{ $plan->storage_quota_gb }}"
                                                data-auctions="{{ $plan->active_auctions_limit }}">
                                                {{ $plan->name }} ({{ number_format($plan->price_after_discount, 2) }}
                                                ر.س)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- بطاقة بيانات الشركة المحددة (للقراءة فقط) -->
                                <div class="col-12" id="company-details-card" style="display: none;">
                                    <div class="card border border-primary bg-label-primary shadow-none mb-0">
                                        <div class="card-body">
                                            <h6 class="card-title fw-bold text-primary mb-3">
                                                <i class="ti ti-building me-1"></i> بيانات الشركة المحددة
                                            </h6>
                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">السجل التجاري</small>
                                                    <span class="fw-semibold text-heading" id="company-commercial">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">الرقم الضريبي</small>
                                                    <span class="fw-semibold text-heading" id="company-tax">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">المدينة</small>
                                                    <span class="fw-semibold text-heading" id="company-city">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">العنوان</small>
                                                    <span class="fw-semibold text-heading" id="company-address">-</span>
                                                </div>
                                                <div class="col-12 mt-2 pt-2 border-top border-primary">
                                                    <small class="text-muted d-block">آخر اشتراك للشركة:</small>
                                                    <span class="fw-semibold" id="company-last-sub-text">لا يوجد اشتراك
                                                        سابق</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- حدود الباقة المختارة (Disabled inputs) -->
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                                        <i class="ti ti-adjustments me-1 text-primary"></i> حدود ومواصفات الباقة المختارة
                                    </h6>
                                    <div class="row g-4">
                                        <div class="col-md-3">
                                            <label class="form-label">عدد المستخدمين المسموح به</label>
                                            <input type="text" class="form-control bg-light"
                                                id="sub_plan_users_display" value="-" readonly disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">حد العقارات</label>
                                            <input type="text" class="form-control bg-light"
                                                id="sub_plan_properties_display" value="-" readonly disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">المساحة التخزينية</label>
                                            <input type="text" class="form-control bg-light"
                                                id="sub_plan_storage_display" value="-" readonly disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">المزادات النشطة</label>
                                            <input type="text" class="form-control bg-light"
                                                id="sub_plan_auctions_display" value="-" readonly disabled>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">المميزات والخصائص المضمنة:</label>
                                            <div id="sub_plan_features_display"
                                                class="d-flex flex-wrap gap-1 p-2 bg-light rounded min-h-px-40">
                                                <span class="text-muted">اختر الباقة لعرض مميزاتها</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-wizard.actions>
                                <x-wizard.btn-draft step="1" />
                                <x-wizard.btn-next data-step="1" />
                            </x-wizard.actions>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 2: مدة الاشتراك وتفاصيل الدفع
                        ═══════════════════════════════════════ --}}
                        <div id="step-payment-details" class="content">
                            <div class="row g-4">
                                <!-- تاريخ البداية -->
                                <div class="col-md-6">
                                    <label for="sub_start_date" class="form-label">
                                        تاريخ بداية الاشتراك <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="sub_start_date" name="start_date"
                                        value="{{ old('start_date', date('Y-m-d')) }}" required>
                                </div>

                                <!-- تاريخ النهاية -->
                                <div class="col-md-6">
                                    <label for="sub_end_date" class="form-label">
                                        تاريخ نهاية الاشتراك
                                    </label>
                                    <input type="date" class="form-control" id="sub_end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                    <div class="form-text text-muted">يتم احتسابه تلقائياً بناءً على دورة الفوترة للباقة
                                        ويمكن تعديله</div>
                                </div>

                                <!-- حالة الدفع -->
                                <div class="col-md-6">
                                    <label for="sub_payment-status" class="form-label">
                                        حالة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="sub_payment-status" name="payment_type" required>
                                        <option value="paid" @selected(old('payment_type') == 'paid')>تم الدفع</option>
                                        <option value="unpaid" @selected(old('payment_type') == 'unpaid')>لم يتم الدفع (معلق)</option>
                                        <option value="trial" @selected(old('payment_type') == 'trial')>فترة تجربة (مجاني)</option>
                                    </select>
                                </div>

                                <!-- طريقة الدفع -->
                                <div class="col-md-6" id="sub_payment-method-wrapper">
                                    <label for="sub_payment-method-select" class="form-label">
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="sub_payment-method-select" name="payment_method">
                                        <option value="bank_transfer" @selected(old('payment_method') == 'bank_transfer')>تحويل بنكي</option>
                                        <option value="online" @selected(old('payment_method') == 'online')>دفع إلكتروني (بطاقة / سداد)
                                        </option>
                                    </select>
                                </div>

                                <!-- إيصال التحويل البنكي -->
                                <div class="col-md-6" id="sub_receipt-upload-wrapper">
                                    <label for="sub_receipt_upload" class="form-label">
                                        إيصال التحويل <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="sub_receipt_upload"
                                        name="receipt_upload" accept="image/jpeg,image/png,image/jpg,application/pdf">
                                    <div class="form-text text-muted">يُقبل PDF أو صور بحد أقصى 5 ميجابايت</div>
                                </div>

                                <!-- تاريخ نهاية التجربة -->
                                <div class="col-md-6" id="sub_trial-date-wrapper" style="display: none;">
                                    <label for="sub_trial_end_date" class="form-label">
                                        تاريخ نهاية التجربة <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="sub_trial_end_date"
                                        name="trial_end_date" value="{{ old('trial_end_date') }}">
                                </div>

                                <!-- خصم / تسوية -->
                                <div class="col-md-6">
                                    <label for="sub_discount-type" class="form-label">تطبيق خصم أو تسوية؟</label>
                                    <select class="form-select" id="sub_discount-type" name="has_discount">
                                        <option value="no" @selected(old('has_discount') == 'no')>لا يوجد خصم</option>
                                        <option value="yes" @selected(old('has_discount') == 'yes')>نعم، يوجد خصم</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="sub_discount-wrapper" style="display: none;">
                                    <label for="sub_discount_value" class="form-label">مبلغ الخصم (ر.س)</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        id="sub_discount_value" name="discount_value"
                                        value="{{ old('discount_value', 0) }}" placeholder="0.00">
                                </div>

                                <!-- التفعيل الفوري -->
                                <div class="col-12 mt-3">
                                    <div class="card bg-label-warning border-0">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    id="sub_activate_immediately" name="activate_immediately"
                                                    value="1" @checked(old('activate_immediately', 1))>
                                                <label class="form-check-label fw-bold text-heading"
                                                    for="sub_activate_immediately">
                                                    تفعيل الاشتراك فوراً
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                <i class="ti ti-alert-triangle text-warning me-1"></i>
                                                عند تفعيل هذا الخيار، سيتم تحويل حالة الشركة وحالة الاشتراك إلى
                                                <span class="badge bg-success">نشط (Active)</span> فوراً حتى لو كانت
                                                الفاتورة غير مدفوعة.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-wizard.actions>
                                <x-wizard.btn-prev data-step="2" />
                                <div>
                                    <x-wizard.btn-draft step="2" class="me-2" />
                                    <x-wizard.btn-next data-step="2" />
                                </div>
                            </x-wizard.actions>
                        </div>

                        {{-- ═══════════════════════════════════════
                             الخطوة 3: مراجعة واعتماد
                        ═══════════════════════════════════════ --}}
                        <div id="step-subscription-review" class="content">
                            <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                                <i class="ti ti-info-circle fs-4 me-2"></i>
                                <div>يرجى مراجعة كافة تفاصيل الاشتراك الجديد قبل الاعتماد النهائي والإنشاء.</div>
                            </div>

                            <div class="row g-4">
                                <!-- ملخص الشركة -->
                                <div class="col-md-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 fw-bold"><i class="ti ti-building me-1"></i> الشركة المستفيدة
                                            </h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted ps-0">اسم الشركة:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-company-name">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">السجل التجاري:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-commercial-number">-
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">الرقم الضريبي:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-tax-number">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">المدينة:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-city">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">آخر اشتراك منتهٍ:</td>
                                                    <td class="fw-semibold text-end text-danger" id="sub_review-last-sub">
                                                        -</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- ملخص الباقة والحدود -->
                                <div class="col-md-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 fw-bold"><i class="ti ti-package me-1"></i> تفاصيل الباقة
                                                والحدود</h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted ps-0">الباقة:</td>
                                                    <td class="fw-semibold text-end text-primary"
                                                        id="sub_review-plan-name">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">عدد المستخدمين:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-plan-users">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">حد العقارات:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-plan-props">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">مساحة التخزين:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-plan-storage">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">المزادات النشطة:</td>
                                                    <td class="fw-semibold text-end" id="sub_review-plan-auctions">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted ps-0">المميزات:</td>
                                                    <td class="text-end" id="sub_review-plan-features">-</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- ملخص الاشتراك والدفع -->
                                <div class="col-12">
                                    <div class="card border shadow-none">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 fw-bold"><i class="ti ti-file-invoice me-1"></i> مدة الاشتراك
                                                والمالية</h6>
                                        </div>
                                        <div class="card-body pt-3">
                                            <div class="row g-4">
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">تاريخ البداية</small>
                                                    <span class="fw-semibold" id="sub_review-start-date">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">تاريخ النهاية</small>
                                                    <span class="fw-semibold" id="sub_review-end-date">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">حالة الدفع</small>
                                                    <span id="sub_review-payment-status"
                                                        class="badge bg-secondary">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">طريقة الدفع</small>
                                                    <span class="fw-semibold" id="sub_review-payment-method">-</span>
                                                </div>
                                                <div class="col-md-6 mt-3 pt-3 border-top">
                                                    <small class="text-muted d-block">حالة التفعيل المتوقعة</small>
                                                    <span id="sub_review-activation-status"
                                                        class="badge bg-label-success">مفعل فوراً</span>
                                                </div>
                                                <div class="col-md-6 mt-3 pt-3 border-top text-md-end">
                                                    <small class="text-muted d-block">المبلغ الإجمالي النهائي</small>
                                                    <span class="h4 fw-bold text-primary mb-0"
                                                        id="sub_review-final-total">0.00 ر.س</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-wizard.actions>
                                <x-wizard.btn-prev data-step="3" />
                                <x-wizard.btn-save type="submit" id="btn-submit-subscription" class="btn-success btn-lg"
                                    text="اعتماد وإنشاء الاشتراك" />
                            </x-wizard.actions>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
