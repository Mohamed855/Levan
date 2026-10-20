@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة اشتراك جديد')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@php
    $companyOptions = $companies
        ->map(
            fn($c) => [
                'value' => $c->id,
                'label' => $c->company_name . ' (' . ($c->commercial_number ?? $c->id) . ')',
            ],
        )
        ->values()
        ->all();

    $planOptions = $plans
        ->map(
            fn($p) => [
                'value' => $p->id,
                'label' => $p->name . ' (' . number_format($p->price_after_discount, 2) . ' ر.س)',
            ],
        )
        ->values()
        ->all();
@endphp

@section('page-script')
    <script>
        window.companiesData = @json($companies);
        window.plansData = @json($plans);
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/subscriptions/create-wizard.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-company-plan', 'title' => 'الشركة والباقة'],
            ['id' => 'step-payment-details', 'title' => 'تفاصيل الاشتراك والدفع'],
            ['id' => 'step-subscription-review', 'title' => 'مراجعة واعتماد'],
        ];
    @endphp

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

            <x-form.wizard.stepper id="subscription-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="subscription-wizard-form" action="{{ route('admin.subscriptions.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.subscription-create">
                    @csrf

                    {{-- ═══════════════════════════════════════
                         الخطوة 1: اختيار الشركة والباقة
                    ═══════════════════════════════════════ --}}
                    <x-form.wizard.step id="step-company-plan" :fields="['company_id', 'plan_id']">
                        <div class="row g-4">
                            <!-- اختيار الشركة -->
                            <x-form.controls.select name="company_id" id="company_id" label="الشركة المستفيدة" required
                                :options="$companyOptions" placeholder="اختر الشركة (فقط الشركات ذات الاشتراكات المنتهية)"
                                help="يتم عرض الشركات التي ليس لها اشتراك حالي أو انتهت صلاحية آخر اشتراك لها فقط."
                                col="col-md-6" />

                            <!-- اختيار الباقة -->
                            <x-form.controls.select name="plan_id" id="plan_id" label="الباقة المطلوبة" required
                                :options="$planOptions" placeholder="اختر الباقة" col="col-md-6" />

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
                                    <x-form.controls.input name="sub_plan_users_display" id="sub_plan_users_display"
                                        label="عدد المستخدمين المسموح به" value="-" readonly disabled col="col-md-6"
                                        class="bg-secondary" />
                                    <x-form.controls.input name="sub_plan_properties_display"
                                        id="sub_plan_properties_display" label="حد العقارات" value="-" readonly
                                        disabled col="col-md-6" class="bg-secondary" />
                                    <x-form.controls.input name="sub_plan_storage_display" id="sub_plan_storage_display"
                                        label="المساحة التخزينية" value="-" readonly disabled col="col-md-6"
                                        class="bg-secondary" />
                                    <x-form.controls.input name="sub_plan_auctions_display" id="sub_plan_auctions_display"
                                        label="المزادات النشطة" value="-" readonly disabled col="col-md-6"
                                        class="bg-secondary" />
                                    <div class="col-12">
                                        <label class="form-label">المميزات والخصائص المضمنة:</label>
                                        <div id="sub_plan_features_display"
                                            class="d-flex flex-wrap gap-1 p-2 bg-secondary rounded min-h-px-40">
                                            <span class="text-muted">اختر الباقة لعرض مميزاتها</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.subscriptions.index')" text="رجوع" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- ═══════════════════════════════════════
                         الخطوة 2: مدة الاشتراك وتفاصيل الدفع
                    ═══════════════════════════════════════ --}}
                    <x-form.wizard.step id="step-payment-details" :fields="[
                        'start_date',
                        'end_date',
                        'payment_type',
                        'payment_method',
                        'receipt_upload',
                        'trial_end_date',
                        'has_discount',
                        'discount_value',
                        'activate_immediately',
                    ]">
                        <div class="row g-4">
                            <!-- تاريخ البداية -->
                            <x-form.controls.input type="date" name="start_date" id="sub_start_date"
                                label="تاريخ بداية الاشتراك" :value="date('Y-m-d')" required col="col-md-6" />

                            <!-- تاريخ النهاية -->
                            <x-form.controls.input type="date" name="end_date" id="sub_end_date"
                                label="تاريخ نهاية الاشتراك"
                                help="يتم احتسابه تلقائياً بناءً على دورة الفوترة للباقة ويمكن تعديله" col="col-md-6" />

                            <!-- حالة الدفع -->
                            <x-form.controls.select name="payment_type" id="sub_payment-status" label="حالة الدفع"
                                required :enum="\App\Enums\Admin\Billing\BillStatus::class" col="col-md-6" />

                            <!-- طريقة الدفع -->
                            <x-form.controls.select name="payment_method" id="sub_payment-method-select"
                                wrapper-id="sub_payment-method-wrapper" label="طريقة الدفع" col="col-md-6"
                                :enum="\App\Enums\Admin\Billing\PaymentMethod::class" />

                            <!-- إيصال التحويل البنكي -->
                            <x-form.controls.file name="receipt_upload" id="sub_receipt_upload"
                                wrapper-id="sub_receipt-upload-wrapper" label="إيصال التحويل"
                                accept="image/jpeg,image/png,image/jpg,application/pdf"
                                help="يُقبل PDF أو صور بحد أقصى 5 ميجابايت" col="col-md-6" />

                            <!-- تاريخ نهاية التجربة -->
                            <x-form.controls.input type="date" name="trial_end_date" id="sub_trial_end_date"
                                wrapper-id="sub_trial-date-wrapper" wrapper-style="display: none;"
                                label="تاريخ نهاية التجربة" col="col-md-6" />

                            <!-- تطبيق خصم -->
                            <x-form.controls.select name="has_discount" id="sub_discount-type"
                                label="تطبيق خصم أو تسوية؟" :enum="\App\Enums\Shared\YesNo::class" col="col-md-6" />

                            <x-form.controls.input type="number" step="0.01" min="0" name="discount_value"
                                id="sub_discount_value" wrapper-id="sub_discount-wrapper" wrapper-style="display: none;"
                                label="مبلغ الخصم (ر.س)" placeholder="0.00" value="0" col="col-md-6" />

                            <!-- التفعيل الفوري -->
                            <x-form.controls.checkbox name="activate_immediately" id="sub_activate_immediately"
                                label="تفعيل الاشتراك فوراً" :checked="true" value="1" col="col-md-6">
                                عند تفعيل هذا الخيار، سيتم تحويل حالة الشركة وحالة الاشتراك إلى
                                <span class="badge bg-success">نشط (Active)</span> فوراً حتى لو كانت الفاتورة غير مدفوعة.
                            </x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- ═══════════════════════════════════════
                         الخطوة 3: مراجعة واعتماد
                    ═══════════════════════════════════════ --}}
                    <x-form.wizard.step id="step-subscription-review" :fields="[]">
                        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                            <i class="ti ti-info-circle fs-4 me-2"></i>
                            <div>يرجى مراجعة كافة تفاصيل الاشتراك الجديد قبل الاعتماد النهائي والإنشاء.</div>
                        </div>

                        <div class="row g-4">
                            <!-- ملخص الشركة -->
                            <div class="col-md-6">
                                <div class="card border h-100 shadow-none">
                                    <div class="card-header bg-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-building me-1"></i> الشركة المستفيدة</h6>
                                    </div>
                                    <div class="card-body pt-3">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted ps-0">اسم الشركة:</td>
                                                <td class="fw-semibold text-end" id="sub_review-company-name">-</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">السجل التجاري:</td>
                                                <td class="fw-semibold text-end" id="sub_review-commercial-number">-</td>
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
                                                <td class="fw-semibold text-end text-danger" id="sub_review-last-sub">-
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ملخص الباقة والحدود -->
                            <div class="col-md-6">
                                <div class="card border h-100 shadow-none">
                                    <div class="card-header bg-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-package me-1"></i> تفاصيل الباقة والحدود
                                        </h6>
                                    </div>
                                    <div class="card-body pt-3">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted ps-0">الباقة:</td>
                                                <td class="fw-semibold text-end text-primary" id="sub_review-plan-name">-
                                                </td>
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
                                    <div class="card-header bg-secondary py-2">
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
                                                <span id="sub_review-payment-status" class="badge bg-secondary">-</span>
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

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
