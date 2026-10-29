@php
    $is_edit = $is_edit ?? false;
    $target = $client ?? $company ?? null;
    $companySettings = $target?->companySettings;

    $steps = [
        ['id' => 'client-details-step', 'title' => 'بيانات العميل'],
        ['id' => 'admin-user-step', 'title' => $is_edit ? 'بيانات المسؤول' : 'المستخدم الإداري الأول'],
        ['id' => 'subscription-step', 'title' => 'بيانات الاشتراك'],
        ['id' => 'review-step', 'title' => 'مراجعة واعتماد'],
    ];

    $plansList = $plans ?? collect();
    $companyPlans = $plansList
        ->map(function ($plan) {
            $cycle =
                $plan->subscription_billing_cycle instanceof \BackedEnum
                    ? $plan->subscription_billing_cycle->value
                    : $plan->subscription_billing_cycle?->value ?? $plan->subscription_billing_cycle;

            return [
                'id' => $plan->id,
                'code' => $plan->code,
                'name' => $plan->name,
                'subscription_duration_type' => $plan->subscription_duration_type,
                'subscription_billing_cycle' => $cycle ? (string) $cycle : null,
            ];
        })
        ->values();
@endphp

<script>
    window.companyPlans = @json($companyPlans);
</script>

<x-form.wizard.stepper id="client-wizard" :steps="$steps">
    <x-ui.validation-errors />

    <form id="client-wizard-form"
        action="{{ $is_edit ? route('admin.clients.update', $target) : route('admin.clients.store') }}"
        method="POST"
        enctype="multipart/form-data"
        autocomplete="off"
        data-form-validate
        data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
        @if (!$is_edit)
            data-draft-url="{{ route('admin.wizard-drafts.store') }}"
            data-draft-resource-key="admin.client-create"
        @endif>
        @csrf
        @if ($is_edit)
            @method('PUT')
        @endif

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
                    :value="old('company_name', $target?->company_name)"
                    placeholder="مثال: شركة التطوير العقاري" maxlength="255" required col="col-md-6" />

                @if ($is_edit)
                    <x-form.controls.input name="commercial_number" label="رقم السجل التجاري"
                        :value="$target?->commercial_number"
                        placeholder="1010xxxxxx" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                        data-digits-only readonly required col="col-md-6" help="رقم السجل التجاري ثابت للشركة." />
                    <x-form.controls.input name="tax_number" label="الرقم الضريبي"
                        :value="$target?->tax_number"
                        placeholder="3xxxxxxxxxxxxx3" maxlength="15" inputmode="numeric" pattern="[0-9]*"
                        data-digits-only readonly col="col-md-6" help="الرقم الضريبي ثابت للشركة." />
                @else
                    <x-form.controls.input name="commercial_number" label="رقم السجل التجاري"
                        :value="old('commercial_number')"
                        placeholder="1010xxxxxx" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                        data-digits-only required col="col-md-6" />
                    <x-form.controls.input name="tax_number" label="الرقم الضريبي"
                        :value="old('tax_number')"
                        placeholder="3xxxxxxxxxxxxx3" maxlength="15" inputmode="numeric" pattern="[0-9]*"
                        data-digits-only required col="col-md-6" />
                @endif

                <x-form.controls.select name="city_id" label="المدينة" :options="$cities"
                    :value="old('city_id', $target?->city_id)"
                    placeholder="اختر المدينة" required col="col-md-6" />

                <x-form.controls.input name="address" label="العنوان"
                    :value="old('address', $target?->address)"
                    placeholder="مثال: الرياض - طريق الملك فهد" rows="2" maxlength="1000" required
                    col="col-md-6" />

                <div class="col-12">
                    <h6 class="fw-bold text-primary mb-0">
                        <i class="ti ti-settings me-1" aria-hidden="true"></i>إعدادات العميل الخاصة
                    </h6>
                </div>
                <x-form.controls.checkbox name="email_notifications_enabled" label="تنبيهات البريد"
                    :checked="old('email_notifications_enabled', $companySettings?->email_notifications_enabled ?? true)" col="col-md-4">
                    إرسال إشعارات البريد.
                </x-form.controls.checkbox>
                <x-form.controls.checkbox name="two_factor_enabled" label="التحقق الثنائي (2FA)"
                    :checked="old('two_factor_enabled', $companySettings?->two_factor_enabled ?? false)" col="col-md-4">
                    فرض 2FA على مستخدمي العميل.
                </x-form.controls.checkbox>
                <x-form.controls.checkbox name="external_integrations_enabled" label="التكاملات الخارجية"
                    :checked="old('external_integrations_enabled', $companySettings?->external_integrations_enabled ?? false)" col="col-md-4">
                    السماح بربط API والأنظمة.
                </x-form.controls.checkbox>
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button :href="route('admin.clients.index')" />
                @if (!$is_edit)
                    <div class="d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <x-form.wizard.next-button />
                    </div>
                @else
                    <x-form.wizard.next-button />
                @endif
            </x-form.wizard.actions>
        </x-form.wizard.step>

        <!-- 2. بيانات المسؤول -->
        <x-form.wizard.step id="admin-user-step" :fields="$is_edit ? ['primary_contact_name', 'primary_contact_phone', 'primary_contact_email'] : ['name', 'phone', 'email', 'password', 'password_confirmation']">
            <div class="row g-4">
                @if ($is_edit)
                    <x-form.controls.input name="primary_contact_name" label="اسم المسؤول"
                        :value="old('primary_contact_name', $target?->primary_contact_name)"
                        placeholder="اسم المسؤول" maxlength="255" col="col-md-6" />
                    <x-form.controls.input name="primary_contact_phone" label="رقم الجوال"
                        :value="old('primary_contact_phone', $target?->primary_contact_phone)"
                        placeholder="05xxxxxxxx" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                        col="col-md-6" />
                    <x-form.controls.input name="primary_contact_email" type="email" label="البريد الإلكتروني"
                        :value="old('primary_contact_email', $target?->primary_contact_email)"
                        placeholder="admin@domain.com" maxlength="255" col="col-md-6" />
                @else
                    <x-form.controls.input name="name" label="اسم المستخدم المسؤول"
                        :value="old('name')" placeholder="اسم المسؤول"
                        maxlength="255" required col="col-md-6" />
                    <x-form.controls.input name="phone" label="رقم الجوال"
                        :value="old('phone')" placeholder="05xxxxxxxx" maxlength="10"
                        inputmode="numeric" pattern="[0-9]*" required col="col-md-6" />
                    <x-form.controls.input name="email" type="email" label="البريد الإلكتروني"
                        :value="old('email')" placeholder="admin@domain.com" maxlength="255" autocomplete="off" required col="col-md-6" />
                    <x-form.controls.password name="password" label="كلمة المرور" placeholder="••••••••"
                        data-validation-dependents="password_confirmation" required col="col-md-6" />
                    <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                        placeholder="••••••••" required col="col-md-6" />
                @endif
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                @if (!$is_edit)
                    <div class="d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <x-form.wizard.next-button />
                    </div>
                @else
                    <x-form.wizard.next-button />
                @endif
            </x-form.wizard.actions>
        </x-form.wizard.step>

        <!-- 3. بيانات الاشتراك -->
        <x-form.wizard.step id="subscription-step" :fields="$is_edit ? [] : ['plan_id', 'start_date', 'end_date', 'activate_immediately']">
            @if ($is_edit)
                @php
                    $activeSub = $target?->activeSubscription;
                    $planName = $activeSub?->plan?->name ?? 'لا توجد باقة نشطة حالياً';
                    $subStart = $activeSub?->starts_at?->format('Y-m-d') ?? '-';
                    $subEnd = $activeSub?->ends_at?->format('Y-m-d') ?? '-';
                @endphp
                <div class="row g-4">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center justify-content-between mb-0" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-info-circle fs-4"></i>
                                <span>تعديل الباقة والاشتراكات يتم عبر إدارة الاشتراكات المخصصة للحفاظ على القيود المالية.</span>
                            </div>
                            <a href="{{ route('admin.subscription-management.index') }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-calendar-stats me-1"></i>إدارة الاشتراكات
                            </a>
                        </div>
                    </div>
                    <x-form.controls.input name="current_plan_name" label="الباقة الحالية"
                        :value="$planName" readonly col="col-md-6" />
                    <x-form.controls.input name="sub_start_date" label="تاريخ بداية الاشتراك"
                        :value="$subStart" readonly col="col-md-6" />
                    <x-form.controls.input name="sub_end_date" label="تاريخ نهاية الاشتراك"
                        :value="$subEnd" readonly col="col-md-6" />
                </div>
            @else
                <div class="row g-4">
                    <x-form.controls.select name="plan_id" label="الباقة" :options="$plans"
                        :value="old('plan_id')" placeholder="اختر الباقة" required col="col-md-6" />
                    <x-form.controls.input name="start_date" type="date" label="تاريخ البداية"
                        :value="old('start_date', now()->toDateString())" required col="col-md-6" />
                    <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية"
                        :value="old('end_date')" help="يُحسب تلقائياً حسب نوع الباقة، ويمكن تعديله عند الحاجة."
                        required col="col-md-6" />
                    <x-form.controls.checkbox name="activate_immediately" label="تفعيل الاشتراك فوراً"
                        :checked="old('activate_immediately', true)" col="col-md-6">
                        تفعيل الاشتراك مباشرة ليتمكن العميل من الدخول للنظام فور التسجيل.
                    </x-form.controls.checkbox>
                </div>
            @endif

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                @if (!$is_edit)
                    <div class="d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <x-form.wizard.next-button />
                    </div>
                @else
                    <x-form.wizard.next-button />
                @endif
            </x-form.wizard.actions>
        </x-form.wizard.step>

        <!-- 4. مراجعة واعتماد -->
        <x-form.wizard.step id="review-step">
            <div class="row g-4 assignment-review">
                <div class="col-12 d-flex flex-column gap-4">
                    <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                        <span>
                            @if ($is_edit)
                                برجاء مراجعة البيانات قبل الحفظ لتحديث سجل العميل في النظام.
                            @else
                                برجاء مراجعة البيانات قبل الاعتماد. سيتم إنشاء العميل واشتراكه وحساب المدير الخاص بالعميل.
                            @endif
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="بيانات العميل" step="1" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">اسم العميل / الشركة</dt>
                                    <dd class="col-7 fw-semibold" id="review-company-name">{{ $target?->company_name ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">السجل التجاري</dt>
                                    <dd class="col-7 fw-semibold" id="review-commercial-number">{{ $target?->commercial_number ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">الرقم الضريبي</dt>
                                    <dd class="col-7 fw-semibold" id="review-tax-number">{{ $target?->tax_number ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">المدينة</dt>
                                    <dd class="col-7 fw-semibold" id="review-city">{{ $target?->city?->name ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">العنوان</dt>
                                    <dd class="col-7 fw-semibold" id="review-address">{{ $target?->address ?? '-' }}</dd>
                                </dl>
                            </x-form.wizard.review-card>
                        </div>

                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="المستخدم المسؤول" step="2" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">الاسم</dt>
                                    <dd class="col-7 fw-semibold" id="review-admin-name">{{ $target?->primary_contact_name ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">الجوال</dt>
                                    <dd class="col-7 fw-semibold" id="review-admin-phone">{{ $target?->primary_contact_phone ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">البريد الإلكتروني</dt>
                                    <dd class="col-7 fw-semibold" id="review-admin-email">{{ $target?->primary_contact_email ?? '-' }}</dd>
                                </dl>
                            </x-form.wizard.review-card>
                        </div>

                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="بيانات الاشتراك" step="3" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">الباقة</dt>
                                    <dd class="col-7 fw-semibold" id="review-plan-name">{{ $target?->activeSubscription?->plan?->name ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">تاريخ البداية</dt>
                                    <dd class="col-7 fw-semibold" id="review-start-date">{{ $target?->activeSubscription?->starts_at?->format('Y-m-d') ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">تاريخ النهاية</dt>
                                    <dd class="col-7 fw-semibold" id="review-end-date">{{ $target?->activeSubscription?->ends_at?->format('Y-m-d') ?? '-' }}</dd>
                                    @if (!$is_edit)
                                        <dt class="col-5 text-muted">حالة التفعيل</dt>
                                        <dd class="col-7 fw-semibold" id="review-activation-status"><span class="badge bg-success">نشط ومفعل</span></dd>
                                    @endif
                                </dl>
                            </x-form.wizard.review-card>
                        </div>
                    </div>
                </div>
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                @if (!$is_edit)
                    <div class="d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <x-form.wizard.save-button />
                    </div>
                @else
                    <x-form.wizard.save-button />
                @endif
            </x-form.wizard.actions>
        </x-form.wizard.step>
    </form>
</x-form.wizard.stepper>
