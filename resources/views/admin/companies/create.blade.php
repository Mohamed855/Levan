@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء شركة جديدة')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@php
    $companyPlans = $plans
        ->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'subscription_duration_type' => $plan->subscription_duration_type,
                'subscription_billing_cycle' => $plan->subscription_billing_cycle,
            ];
        })
        ->values();
@endphp

@section('page-script')
    <script>
        window.companyPlans = @json($companyPlans);
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/companies/form-ui.js'])
@endsection

@section('content')
    @if (!empty($trialPrefill))
        <script>
            window.trialPrefill = @json($trialPrefill);
            document.addEventListener('DOMContentLoaded', function() {
                Object.entries(window.trialPrefill).forEach(([name, value]) => {
                    const input = document.querySelector(`[name="${name}"]`);
                    if (input && !input.value) input.value = value;
                });
            });
        </script>
    @endif
    @php
        $steps = [
            ['id' => 'company-details-step', 'title' => 'بيانات الشركة'],
            ['id' => 'admin-user-step', 'title' => 'المستخدم الإداري الأول'],
            ['id' => 'subscription-step', 'title' => 'الاشتراك والدفع'],
            ['id' => 'review-step', 'title' => 'مراجعة واعتماد'],
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <x-form.wizard.stepper id="company-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="company-wizard-form" action="{{ route('admin.companies.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.company-create">
                    @csrf

                    <!-- 1. بيانات الشركة -->
                    <x-form.wizard.step id="company-details-step" :fields="[
                        'company_name',
                        'commercial_number',
                        'tax_number',
                        'city_id',
                        'address',
                        'email_notifications_enabled',
                        'two_factor_enabled',
                        'external_integrations_enabled',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.input name="company_name" label="اسم الشركة"
                                placeholder="مثال: شركة التطوير العقاري" maxlength="255" required col="col-md-6" />
                            <x-form.controls.input name="commercial_number" label="رقم السجل التجاري"
                                placeholder="1010XXXXXX" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                                data-digits-only required col="col-md-6" />
                            <x-form.controls.input name="tax_number" label="الرقم الضريبي" placeholder="300XXXXXXXXXXXX"
                                maxlength="15" inputmode="numeric" pattern="[0-9]*" data-digits-only required
                                col="col-md-6" />
                            <x-form.controls.select name="city_id" label="المدينة" :options="$cities"
                                placeholder="اختر المدينة" required col="col-md-6" />
                            <x-form.controls.textarea name="address" label="العنوان"
                                placeholder="مثال: الرياض - طريق الملك فهد" rows="2" maxlength="1000" required
                                col="col-12" />

                            <div class="col-12">
                                <h6 class="fw-bold text-primary mb-0"><i class="ti ti-settings me-1"
                                        aria-hidden="true"></i>إعدادات الشركة الخاصة</h6>
                            </div>
                            <x-form.controls.checkbox name="email_notifications_enabled" label="تنبيهات البريد"
                                :checked="true" col="col-md-4">إرسال إشعارات البريد.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="two_factor_enabled" label="التحقق الثنائي (2FA)"
                                col="col-md-4">فرض 2FA على مستخدمي الشركة.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="external_integrations_enabled" label="التكاملات الخارجية"
                                col="col-md-4">السماح بربط API والأنظمة.</x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.companies.index')" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button :disabled="true" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <!-- 2. المستخدم الإداري الأول -->
                    <x-form.wizard.step id="admin-user-step" :fields="['name', 'phone', 'email', 'password', 'password_confirmation']">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="اسم المستخدم المسؤول" placeholder="اسم المسؤول"
                                maxlength="255" required col="col-md-6" />
                            <x-form.controls.input name="phone" label="رقم الجوال" placeholder="05xxxxxxxx" maxlength="10"
                                inputmode="numeric" pattern="[0-9]*" required col="col-md-6" />
                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني"
                                placeholder="admin@domain.com" maxlength="255" autocomplete="off" required col="col-md-6" />
                            <x-form.controls.password name="password" label="كلمة المرور" placeholder="••••••••"
                                data-validation-dependents="password_confirmation" required col="col-md-6" />
                            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                                placeholder="••••••••" required col="col-md-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button :disabled="true" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <!-- 3. الاشتراك والدفع -->
                    <x-form.wizard.step id="subscription-step" :fields="[
                        'plan_id',
                        'start_date',
                        'end_date',
                        'total_users',
                        'payment_type',
                        'payment_method',
                        'receipt_upload',
                        'trial_end_date',
                        'has_discount',
                        'discount_value',
                        'activate_immediately',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.select name="plan_id" label="الباقة" :options="$plans"
                                placeholder="اختر الباقة" required col="col-md-6" />
                            <x-form.controls.input name="start_date" type="date" label="تاريخ البداية" :value="now()->toDateString()"
                                required col="col-md-6" />
                            <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية"
                                help="يُحسب تلقائياً حسب نوع الباقة، ويمكن تعديله عند الحاجة." col="col-md-6" />
                            <x-form.controls.input name="total_users" type="number" label="إجمالي المستخدمين"
                                min="1" step="1" inputmode="numeric" col="col-md-6" />
                            <x-form.controls.select name="payment_type" label="حالة الدفع" :enum="\App\Enums\Admin\Billing\BillStatus::class"
                                placeholder="اختر حالة الدفع"
                                data-validation-dependents="payment_method receipt_upload trial_end_date" required
                                col="col-md-6" />
                            <x-form.controls.select name="payment_method" label="وسيلة الدفع" :enum="\App\Enums\Admin\Billing\PaymentMethod::class"
                                placeholder="اختر وسيلة الدفع" data-validation-dependents="receipt_upload"
                                col="col-md-6" />
                            <x-form.controls.file name="receipt_upload" label="إيصال التحويل البنكي"
                                accept="image/jpeg,image/png,image/jpg,application/pdf"
                                help="يُقبل PDF أو صور بحد أقصى 5MB." col="col-md-6" />
                            <x-form.controls.input name="trial_end_date" type="date" label="تاريخ نهاية التجربة"
                                col="col-md-6" />
                            <x-form.controls.select name="has_discount" label="هل يوجد خصم أو تسوية؟" :enum="\App\Enums\Shared\YesNo::class"
                                :value="'no'" data-validation-dependents="discount_value" col="col-md-6" />
                            <x-form.controls.input name="discount_value" type="number"
                                label="مبلغ الخصم / التسوية (ر.س)" min="0" step="0.01" inputmode="decimal"
                                col="col-md-6" />
                            <x-form.controls.checkbox name="activate_immediately" label="تفعيل الشركة والاشتراك فوراً"
                                :checked="true" col="col-12">تصبح الشركة والاشتراك متاحين مباشرة حتى لو لم يتم الدفع
                                بعد.</x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button :disabled="true" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <!-- 4. مراجعة واعتماد -->
                    <x-form.wizard.step id="review-step">
                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <div class="card border shadow-none h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ti ti-building text-primary me-1"
                                                aria-hidden="true"></i>بيانات الشركة</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">اسم الشركة</dt>
                                            <dd class="col-7 fw-semibold" id="review-company-name">-</dd>
                                            <dt class="col-5 text-muted">السجل التجاري</dt>
                                            <dd class="col-7 fw-semibold" id="review-commercial-number">-</dd>
                                            <dt class="col-5 text-muted">الرقم الضريبي</dt>
                                            <dd class="col-7 fw-semibold" id="review-tax-number">-</dd>
                                            <dt class="col-5 text-muted">المدينة</dt>
                                            <dd class="col-7 fw-semibold" id="review-city">-</dd>
                                            <dt class="col-5 text-muted">العنوان</dt>
                                            <dd class="col-7 fw-semibold" id="review-address">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border shadow-none h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ti ti-user text-primary me-1"
                                                aria-hidden="true"></i>المستخدم المسؤول</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">الاسم</dt>
                                            <dd class="col-7 fw-semibold" id="review-admin-name">-</dd>
                                            <dt class="col-5 text-muted">رقم الجوال</dt>
                                            <dd class="col-7 fw-semibold" id="review-admin-phone">-</dd>
                                            <dt class="col-5 text-muted">البريد الإلكتروني</dt>
                                            <dd class="col-7 fw-semibold" id="review-admin-email">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card border shadow-none h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="ti ti-packages text-primary me-1"
                                                aria-hidden="true"></i>الاشتراك والدفع</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">الباقة</dt>
                                            <dd class="col-7 fw-semibold" id="review-plan-name">-</dd>
                                            <dt class="col-5 text-muted">تاريخ البداية</dt>
                                            <dd class="col-7 fw-semibold" id="review-start-date">-</dd>
                                            <dt class="col-5 text-muted">تاريخ النهاية</dt>
                                            <dd class="col-7 fw-semibold" id="review-end-date">-</dd>
                                            <dt class="col-5 text-muted">حالة الدفع</dt>
                                            <dd class="col-7 fw-semibold" id="review-payment-status">-</dd>
                                            <dt class="col-5 text-muted">وسيلة الدفع</dt>
                                            <dd class="col-7 fw-semibold" id="review-payment-method">-</dd>
                                            <dt class="col-5 text-muted">حالة التفعيل</dt>
                                            <dd class="col-7" id="review-activation-status">-</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="alert alert-warning mb-0 d-flex align-items-center gap-2 h-100"
                                    role="alert">
                                    <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                                    <span>برجاء مراجعة البيانات قبل الاعتماد. سيتم إنشاء الشركة واشتراكها وحساب المدير الخاص
                                        بالشركة.</span>
                                </div>
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button text="إنشاء واعتماد الشركة" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
