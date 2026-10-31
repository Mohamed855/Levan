@php
    $is_edit = $is_edit ?? false;
    $target = $plan ?? null;
    $hasSubscriptions = !empty($hasSubscriptions);

    $steps = [
        ['id' => 'basics-validation', 'title' => 'الأساسيات والميزات'],
        ['id' => 'pricing-validation', 'title' => 'التسعير والفوترة'],
        ['id' => 'limits-validation', 'title' => 'الحدود'],
        ['id' => 'review-validation', 'title' => 'المراجعة والنشر'],
    ];

    $currentType = old('plan_type', $target?->plan_type?->value ?? ($target?->plan_type ?? ''));
    $currentColor = old('color', $target?->color?->value ?? ($target?->color ?? 'purple'));
    $currentCycle = old(
        'subscription_billing_cycle',
        $target?->subscription_billing_cycle?->value ?? ($target?->subscription_billing_cycle ?? 'monthly'),
    );
    $currentLimitsMode = old('limits_mode', $target?->limits_mode?->value ?? ($target?->limits_mode ?? 'limited'));
    $featuresList = old('features', $target?->features ?? []);
@endphp

@if ($is_edit && $hasSubscriptions)
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="ti ti-alert-triangle fs-3 me-2"></i>
        <div>
            <strong>هذه الباقة مرتبطة باشتراكات.</strong> تم قفل كود الباقة ودورة الفوترة ووضع الحدود لحماية العقود
            التاريخية.
        </div>
    </div>
@endif

<x-form.wizard.stepper id="plan-wizard" :steps="$steps">
    <x-ui.validation-errors />

    <form id="plan-wizard-form"
        action="{{ $is_edit ? route('admin.plans.update', $target->id) : route('admin.plans.store') }}" method="POST"
        autocomplete="off" data-form-validate data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
        @if (!$is_edit) data-draft-url="{{ route('admin.wizard-drafts.store') }}"
            data-draft-resource-key="admin.plan-create" @endif>
        @csrf
        @if ($is_edit)
            @method('PUT')
        @endif

        <!-- 1. الأساسيات والميزات -->
        <x-form.wizard.step id="basics-validation" :fields="[
            'name',
            'code',
            'plan_type',
            'color',
            'description',
            'features',
            'is_active',
            'available_from',
            'admin_notes',
        ]">
            <div class="row g-4">
                <x-form.controls.input name="name" label="اسم الباقة" :value="old('name', $target?->name)"
                    placeholder="مثال: الباقة الاحترافية" required col="col-md-6" />

                @if ($is_edit && $hasSubscriptions)
                    <x-form.controls.input name="code" id="code" label="كود الباقة (English Uppercase)"
                        :value="$target?->code" maxlength="50" pattern="[A-Z][A-Z0-9_]*" required col="col-md-6"
                        class="text-uppercase" readonly help="الكود مقفل لارتباط الباقة باشتراكات." />
                    <input type="hidden" name="code" value="{{ $target?->code }}">
                @else
                    <x-form.controls.input name="code" id="code" label="كود الباقة (English Uppercase)"
                        :value="old('code', $target?->code)" maxlength="50" pattern="[A-Z][A-Z0-9_]*"
                        title="يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط" required col="col-md-6"
                        class="text-uppercase" />
                @endif

                <x-form.controls.select name="plan_type" id="plan_type" label="نوع الباقة" required :value="$currentType"
                    col="col-md-6" :options="array_map(
                        fn($type) => ['value' => $type->value, 'label' => $type->label()],
                        \App\Enums\Admin\Plans\PlanType::cases(),
                    )" />

                <x-form.controls.select name="color" id="color" label="لون الباقة المميز" required
                    :value="$currentColor" col="col-md-6" :options="array_map(
                        fn($color) => ['value' => $color->value, 'label' => $color->label()],
                        \App\Enums\Admin\Plans\PlanColor::cases(),
                    )" />

                <x-form.controls.textarea name="description" id="description" label="وصف الباقة" rows="3"
                    :value="old('description', $target?->description)" placeholder="وصف واضح لقيمة الباقة..." col="col-md-6" />

                <x-form.controls.select name="is_active" id="is_active" label="حالة تفعيل الباقة" required
                    :value="old('is_active', $target?->is_active ?? 1)" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />

                <x-form.controls.input type="date" name="available_from" id="available_from"
                    label="تاريخ بدء الإتاحة" :value="old('available_from', $target?->available_from?->format('Y-m-d'))" col="col-md-6" help="اتركه فارغاً لإتاحتها فوراً." />

                <x-form.controls.textarea name="admin_notes" id="admin_notes" label="ملاحظات إدارية" :value="old('admin_notes', $target?->admin_notes)"
                    rows="2" col="col-md-6" />

                <div class="col-12" data-form-field data-field-name="features">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">ميزات الباقة <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-feature">
                            <i class="ti ti-plus me-1"></i>إضافة ميزة
                        </button>
                    </div>
                    <div id="features-list">
                        @foreach ($featuresList as $index => $feature)
                            <div class="feature-item mb-2">
                                <div class="d-flex align-items-end gap-2">
                                    <div class="flex-grow-1">
                                        <input type="text" name="features[{{ $index }}]" data-form-control
                                            class="form-control" value="{{ $feature }}"
                                            placeholder="مثال: لوحة تحكم مخصصة" required>
                                    </div>
                                    <button type="button" class="btn btn-icon btn-label-danger btn-remove-feature"
                                        aria-label="حذف الميزة">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                </div>
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button :href="route('admin.plans.index')" />
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

        <!-- 2. التسعير والفوترة -->
        <x-form.wizard.step id="pricing-validation" :fields="['price', 'subscription_billing_cycle', 'discount_percentage', 'yearly_renewal_price']">
            <div class="row g-4">
                <x-form.controls.input type="number" step="0.01" min="0" name="price" id="price"
                    label="سعر الدورة الأساسية (ر.س)" placeholder="500" :value="old('price', $target?->price)" required col="col-md-6" />

                @if ($is_edit && $hasSubscriptions)
                    <x-form.controls.select name="subscription_billing_cycle" id="subscription_billing_cycle"
                        label="دورة الفوترة الأساسية" required :value="$currentCycle" col="col-md-6" :options="array_map(
                            fn($cycle) => ['value' => $cycle->value, 'label' => $cycle->label()],
                            \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases(),
                        )"
                        disabled help="دورة الفوترة مقفلة لارتباط الباقة باشتراكات قائمة." />
                    <input type="hidden" name="subscription_billing_cycle" value="{{ $currentCycle }}">
                @else
                    <x-form.controls.select name="subscription_billing_cycle" id="subscription_billing_cycle"
                        label="دورة الفوترة الأساسية" required :value="$currentCycle" col="col-md-6" :options="array_map(
                            fn($cycle) => ['value' => $cycle->value, 'label' => $cycle->label()],
                            \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases(),
                        )" />
                @endif

                <x-form.controls.input type="number" step="0.01" min="0" max="100"
                    name="discount_percentage" id="discount_percentage" label="نسبة الخصم (%)" :value="old('discount_percentage', $target?->discount_percentage ?? 0)"
                    col="col-md-6" />

                <x-form.controls.input type="number" step="0.01" min="0" name="yearly_renewal_price"
                    id="yearly_renewal_price" label="سعر العرض السنوي الترويجي (ر.س)" :value="old('yearly_renewal_price', $target?->yearly_renewal_price)"
                    col="col-md-6" help="عرض سنوي للباقات الشهرية والربع سنوية." />
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

        <!-- 3. الحدود -->
        <x-form.wizard.step id="limits-validation" :fields="[
            'limits_mode',
            'included_users',
            'extra_user_license_price',
            'properties_limit',
            'storage_quota_gb',
            'active_auctions_limit',
            'domain_management_enabled',
        ]">
            <div class="row g-4">
                @if ($is_edit && $hasSubscriptions)
                    <x-form.controls.select name="limits_mode" id="limits_mode" label="وضع الحدود" required
                        :value="$currentLimitsMode" col="col-md-6" :enum="\App\Enums\Admin\Plans\PlanLimitsMode::class" disabled
                        help="وضع الحدود مقفل لارتباط الباقة باشتراكات قائمة." />
                    <input type="hidden" name="limits_mode" value="{{ $currentLimitsMode }}">
                @else
                    <x-form.controls.select name="limits_mode" id="limits_mode" label="وضع الحدود" required
                        :value="$currentLimitsMode" col="col-md-6" :enum="\App\Enums\Admin\Plans\PlanLimitsMode::class" />
                @endif

                <div class="col-12" id="unlimited-mode-alert" hidden>
                    <div class="alert alert-primary mb-0">
                        <i class="ti ti-infinity me-2"></i>لن تُفرض حدود استخدام على هذه الباقة.
                    </div>
                </div>

                <x-form.controls.input type="number" min="1" name="included_users" id="included_users"
                    label="عدد المستخدمين المشمولين" :value="old('included_users', $target?->included_users ?? 5)" required col="col-md-6 limits-field" />

                <x-form.controls.input type="number" step="0.01" min="0" name="extra_user_license_price"
                    id="extra_user_license_price" label="سعر المستخدم الإضافي (ر.س)" :value="old('extra_user_license_price', $target?->extra_user_license_price ?? 50)" required
                    col="col-md-6 limits-field" />

                <x-form.controls.input type="number" min="1" name="properties_limit" id="properties_limit"
                    label="حد العقارات" :value="old('properties_limit', $target?->properties_limit ?? 50)" required col="col-md-6 limits-field" />

                <x-form.controls.input type="number" min="1" name="storage_quota_gb" id="storage_quota_gb"
                    label="حصة التخزين (GB)" :value="old('storage_quota_gb', $target?->storage_quota_gb ?? 10)" required col="col-md-6 limits-field" />

                <x-form.controls.input type="number" min="1" name="active_auctions_limit"
                    id="active_auctions_limit" label="حد المزادات النشطة" :value="old('active_auctions_limit', $target?->active_auctions_limit ?? 10)" required
                    col="col-md-6 limits-field" />

                <x-form.controls.select name="domain_management_enabled" id="domain_management_enabled"
                    label="إدارة الدومينات" required :value="old('domain_management_enabled', $target?->domain_management_enabled ?? 1)" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />
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

        <!-- 4. المراجعة والنشر -->
        <x-form.wizard.step id="review-validation" :fields="[]">
            <div class="row g-4 assignment-review">
                <div class="col-12 d-flex flex-column gap-4">
                    <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                        <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                        <span>برجاء مراجعة بيانات الباقة والتسعير والحدود قبل الحفظ النهائي.</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card h-100 shadow-none border">
                                <div class="card-header pb-2">
                                    <h6 class="mb-0 fw-semibold"><i class="ti ti-layout-grid me-1"></i>معاينة بطاقة
                                        الباقة</h6>
                                </div>
                                <div class="card-body d-flex align-items-center justify-content-center p-3">
                                    <x-ui.package-card preview />
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="الأساسيات والميزات" step="1" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">اسم الباقة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-name">{{ $target?->name ?? '-' }}
                                    </dd>
                                    <dt class="col-5 text-muted">كود الباقة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-code">{{ $target?->code ?? '-' }}
                                    </dd>
                                    <dt class="col-5 text-muted">نوع الباقة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-type">
                                        {{ $target?->plan_type?->label() ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">لون الباقة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-color">
                                        {{ $target?->color?->label() ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">حالة التفعيل</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-status">
                                        {{ $target?->is_active ?? true ? 'نشطة' : 'متوقفة' }}</dd>
                                    <dt class="col-5 text-muted">تاريخ بدء الإتاحة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-available-from">
                                        {{ $target?->available_from?->format('Y-m-d') ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">الوصف</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-description">
                                        {{ $target?->description ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">الميزات المشمولة</dt>
                                    <dd class="col-7" id="plan-review-features">
                                        @if ($target && !empty($target->features))
                                            <ul class="mb-0 ps-3">
                                                @foreach ($target->features as $f)
                                                    <li>{{ $f }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            -
                                        @endif
                                    </dd>
                                </dl>
                            </x-form.wizard.review-card>
                        </div>

                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="التسعير والفوترة" step="2" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">السعر الأساسي</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-price">
                                        {{ $target?->price ? number_format((float) $target->price, 2) . ' ر.س' : '-' }}
                                    </dd>
                                    <dt class="col-5 text-muted">دورة الفوترة الأساسية</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-billing-cycle">
                                        {{ $target?->subscription_billing_cycle?->label() ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">نسبة الخصم</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-discount">
                                        {{ $target?->discount_percentage ? $target->discount_percentage . '%' : '0%' }}
                                    </dd>
                                    <dt class="col-5 text-muted">السعر بعد الخصم</dt>
                                    <dd class="col-7 fw-semibold text-primary" id="plan-review-final-price">
                                        {{ $target?->final_price ? number_format((float) $target->final_price, 2) . ' ر.س' : '-' }}
                                    </dd>
                                    <dt class="col-5 text-muted">سعر العرض السنوي</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-renewal-price">
                                        {{ $target?->yearly_renewal_price ? number_format((float) $target->yearly_renewal_price, 2) . ' ر.س' : '-' }}
                                    </dd>
                                </dl>
                            </x-form.wizard.review-card>
                        </div>

                        <div class="col-lg-6">
                            <x-form.wizard.review-card title="الحدود والصلاحيات" step="3" col=""
                                class="h-100 assignment-review__card">
                                <dl class="row mb-0 small">
                                    <dt class="col-5 text-muted">وضع الحدود</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-limits-mode">
                                        {{ $target?->limits_mode?->label() ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">عدد المستخدمين المشمولين</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-users">
                                        {{ $target?->included_users ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">سعر ترخيص المستخدم الإضافي</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-extra-user-price">
                                        {{ $target?->extra_user_license_price ? number_format((float) $target->extra_user_license_price, 2) . ' ر.س' : '-' }}
                                    </dd>
                                    <dt class="col-5 text-muted">حد العقارات</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-properties">
                                        {{ $target?->properties_limit ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">حصة التخزين</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-storage">
                                        {{ $target?->storage_quota_gb ? $target->storage_quota_gb . ' GB' : '-' }}</dd>
                                    <dt class="col-5 text-muted">حد المزادات النشطة</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-auctions">
                                        {{ $target?->active_auctions_limit ?? '-' }}</dd>
                                    <dt class="col-5 text-muted">إدارة الدومينات</dt>
                                    <dd class="col-7 fw-semibold" id="plan-review-domain">
                                        {{ $target?->domain_management_enabled ?? true ? 'مفعلة' : 'معطلة' }}</dd>
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

<template id="feature-template">
    <div class="feature-item mb-2">
        <div class="d-flex align-items-end gap-2">
            <div class="flex-grow-1">
                <input type="text" name="features[{index}]" data-form-control class="form-control"
                    placeholder="مثال: لوحة تحكم مخصصة" required>
            </div>
            <button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" aria-label="حذف الميزة">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </div>
</template>
