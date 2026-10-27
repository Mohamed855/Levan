@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء عميل جديد')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@php
    $companyPlans = $plans
        ->map(function ($plan) {
            $cycle =
                $plan->subscription_billing_cycle instanceof \BackedEnum
                    ? $plan->subscription_billing_cycle->value
                    : $plan->subscription_billing_cycle?->value ?? $plan->subscription_billing_cycle;

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'subscription_duration_type' => $plan->subscription_duration_type,
                'subscription_billing_cycle' => $cycle ? (string) $cycle : null,
            ];
        })
        ->values();
@endphp

@section('page-script')
    <script>
        window.companyPlans = @json($companyPlans);
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/clients/form-ui.js'])
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
            ['id' => 'client-details-step', 'title' => 'بيانات العميل'],
            ['id' => 'admin-user-step', 'title' => 'المستخدم الإداري الأول'],
            ['id' => 'subscription-step', 'title' => 'بيانات الاشتراك'],
            ['id' => 'review-step', 'title' => 'مراجعة واعتماد'],
        ];
    @endphp

    <div class="row">
        <div class="col-12">
            <x-form.wizard.stepper id="client-wizard" :steps="$steps">
                <x-ui.validation-errors />

                <form id="client-wizard-form" action="{{ route('admin.clients.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}" data-draft-resource-key="admin.client-create">
                    @csrf

                    <!-- 1. بيانات العميل -->
                    <x-form.wizard.step id="client-details-step" :fields="[
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
                            <x-form.controls.input name="company_name" label="اسم العميل / الشركة"
                                placeholder="مثال: شركة التطوير العقاري" maxlength="255" required col="col-md-6" />
                            <x-form.controls.input name="commercial_number" label="رقم السجل التجاري"
                                placeholder="1010123456" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                                data-digits-only required col="col-md-6" />
                            <x-form.controls.input name="tax_number" label="الرقم الضريبي" placeholder="300000000000003"
                                maxlength="15" inputmode="numeric" pattern="[0-9]*" data-digits-only required
                                col="col-md-6" />
                            <x-form.controls.select name="city_id" label="المدينة" :options="$cities"
                                placeholder="اختر المدينة" required col="col-md-6" />
                            <x-form.controls.input name="address" label="العنوان"
                                placeholder="مثال: الرياض - طريق الملك فهد" rows="2" maxlength="1000" required
                                col="col-md-6" />

                            <div class="col-12">
                                <h6 class="fw-bold text-primary mb-0"><i class="ti ti-settings me-1"
                                        aria-hidden="true"></i>إعدادات العميل الخاصة</h6>
                            </div>
                            <x-form.controls.checkbox name="email_notifications_enabled" label="تنبيهات البريد"
                                :checked="true" col="col-md-4">إرسال إشعارات البريد.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="two_factor_enabled" label="التحقق الثنائي (2FA)"
                                col="col-md-4">فرض 2FA على مستخدمي العميل.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="external_integrations_enabled" label="التكاملات الخارجية"
                                col="col-md-4">السماح بربط API والأنظمة.</x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.clients.index')" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
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

                    <!-- 3. بيانات الاشتراك -->
                    <x-form.wizard.step id="subscription-step" :fields="[
                        'plan_id',
                        'start_date',
                        'end_date',
                        'activate_immediately',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.select name="plan_id" label="الباقة" :options="$plans"
                                placeholder="اختر الباقة" required col="col-md-6" />
                            <x-form.controls.input name="start_date" type="date" label="تاريخ البداية" :value="old('start_date', now()->toDateString())"
                                required col="col-md-6" />
                            <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية"
                                help="يُحسب تلقائياً حسب نوع الباقة، ويمكن تعديله عند الحاجة." required col="col-md-6" />
                            <x-form.controls.checkbox name="activate_immediately" label="تفعيل الاشتراك فوراً"
                                :checked="true" col="col-md-6">تفعيل الاشتراك مباشرة ليتمكن العميل من الدخول للنظام فور التسجيل.</x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <!-- 4. مراجعة واعتماد -->
                    <x-form.wizard.step id="review-step">
                        <div class="row g-4 assignment-review">
                            <div class="col-12 d-flex flex-column gap-4">
                                <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                                    <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                                    <span>برجاء مراجعة البيانات قبل الاعتماد. سيتم إنشاء العميل واشتراكه وحساب المدير الخاص
                                        بالعميل.</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <x-form.wizard.review-card title="بيانات العميل" step="1" col=""
                                            class="h-100 assignment-review__card">
                                            <dl class="row mb-0 small">
                                                <dt class="col-5 text-muted">اسم العميل / الشركة</dt>
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
                                        </x-form.wizard.review-card>
                                    </div>

                                    <div class="col-lg-6">
                                        <x-form.wizard.review-card title="المستخدم المسؤول" step="2" col=""
                                            class="h-100 assignment-review__card">
                                            <dl class="row mb-0 small">
                                                <dt class="col-5 text-muted">الاسم</dt>
                                                <dd class="col-7 fw-semibold" id="review-admin-name">-</dd>
                                                <dt class="col-5 text-muted">رقم الجوال</dt>
                                                <dd class="col-7 fw-semibold" id="review-admin-phone">-</dd>
                                                <dt class="col-5 text-muted">البريد الإلكتروني</dt>
                                                <dd class="col-7 fw-semibold" id="review-admin-email">-</dd>
                                            </dl>
                                        </x-form.wizard.review-card>
                                    </div>

                                    <div class="col-lg-6">
                                        <x-form.wizard.review-card title="بيانات الاشتراك" step="3" col=""
                                            class="h-100 assignment-review__card">
                                            <dl class="row mb-0 small">
                                                <dt class="col-5 text-muted">الباقة</dt>
                                                <dd class="col-7 fw-semibold" id="review-plan-name">-</dd>
                                                <dt class="col-5 text-muted">تاريخ البداية</dt>
                                                <dd class="col-7 fw-semibold" id="review-start-date">-</dd>
                                                <dt class="col-5 text-muted">تاريخ النهاية</dt>
                                                <dd class="col-7 fw-semibold" id="review-end-date">-</dd>
                                                <dt class="col-5 text-muted">حالة التفعيل</dt>
                                                <dd class="col-7">
                                                    <span id="review-activation-status" class="badge bg-success">نشط ومفعل</span>
                                                </dd>
                                            </dl>
                                        </x-form.wizard.review-card>
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
