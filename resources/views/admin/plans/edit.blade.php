@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الباقة - ' . $plan->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/scss/shared/package-card.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/plans/form-ui.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'basics-validation', 'title' => 'الأساسيات والميزات', 'subtitle' => 'هوية الباقة وقيمتها'],
            ['id' => 'pricing-validation', 'title' => 'التسعير', 'subtitle' => 'السعر والخصومات'],
            ['id' => 'billing-validation', 'title' => 'دورة الفوترة', 'subtitle' => 'مدة الاشتراك'],
            ['id' => 'limits-validation', 'title' => 'الحدود', 'subtitle' => 'الحصص والصلاحيات'],
            ['id' => 'review-validation', 'title' => 'المراجعة والحفظ', 'subtitle' => 'تأكيد التعديلات'],
        ];
        $hasSubscriptions = !empty($hasSubscriptions);
        $currentType = $plan->plan_type?->value ?? $plan->plan_type;
        $currentColor = $plan->color?->value ?? $plan->color;
        $currentCycle = $plan->subscription_billing_cycle?->value ?? $plan->subscription_billing_cycle;
        $currentCycleLabel = $plan->subscription_billing_cycle?->label() ?? $currentCycle;
        $currentLimitsMode = $plan->limits_mode?->value ?? $plan->limits_mode ?? 'limited';
    @endphp

    @if ($hasSubscriptions)
        <div class="alert alert-warning d-flex align-items-center mb-4">
            <i class="ti ti-alert-triangle fs-3 me-2"></i>
            <div><strong>هذه الباقة مرتبطة باشتراكات.</strong> تم قفل كود الباقة ودورة الفوترة ووضع الحدود لحماية العقود التاريخية.</div>
        </div>
    @endif

    <x-form.wizard.stepper id="plan-wizard" :steps="$steps">
        <x-ui.validation-errors class="mb-4" />

        <form id="plan-wizard-form" action="{{ route('admin.plans.update', $plan->id) }}" method="POST" autocomplete="off"
            data-form-validate data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
            @csrf
            @method('PUT')

            <x-form.wizard.step id="basics-validation" :fields="['name', 'code', 'plan_type', 'color', 'description', 'features', 'is_active', 'available_from', 'admin_notes']">
                <div class="row g-3">
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2 mb-0 text-primary"><i class="ti ti-sparkles me-1"></i>أساسيات الباقة ومميزاتها</h6></div>
                    <x-form.controls.input name="name" label="اسم الباقة" :value="$plan->name" placeholder="مثال: الباقة الاحترافية" required col="col-md-6" />
                    <div class="col-md-6" data-form-field data-field-name="code">
                        <label class="form-label" for="code">كود الباقة (English Uppercase) <span class="text-danger">*</span></label>
                        <input id="code" name="code" type="text" value="{{ old('code', $plan->code) }}" maxlength="50" pattern="[A-Z][A-Z0-9_]*" required data-form-control
                            class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror" @readonly($hasSubscriptions)>
                        <div id="code-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                        @if ($hasSubscriptions)<small class="text-warning">الكود مقفل لارتباط الباقة باشتراكات.</small>@endif
                    </div>
                    <x-form.controls.select name="plan_type" id="plan_type" label="نوع الباقة" required :value="$currentType" col="col-md-6" :options="array_map(fn($type) => ['value' => $type->value, 'label' => $type->label()], \App\Enums\Admin\Plans\PlanType::cases())" />
                    <x-form.controls.select name="color" id="color" label="لون الباقة المميز" required :value="$currentColor" col="col-md-6" :options="array_map(fn($color) => ['value' => $color->value, 'label' => $color->label()], \App\Enums\Admin\Plans\PlanColor::cases())" />
                    <x-form.controls.textarea name="description" id="description" label="وصف الباقة" :value="$plan->description" rows="3" placeholder="وصف واضح لقيمة الباقة..." col="col-12" />

                    <div class="col-12" data-form-field data-field-name="features">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">ميزات الباقة <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-label-primary btn-sm" id="btn-add-feature"><i class="ti ti-plus me-1"></i>إضافة ميزة</button>
                        </div>
                        <div id="features-list">
                            @foreach (old('features', $plan->features ?? []) as $index => $feature)
                                <div class="border rounded p-3 mb-2 feature-item bg-lighter"><div class="d-flex align-items-end gap-2"><div class="flex-grow-1"><input type="text" name="features[{{ $index }}]" data-form-control class="form-control" value="{{ $feature }}" placeholder="مثال: لوحة تحكم مخصصة" required></div><button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" aria-label="حذف الميزة"><i class="ti ti-trash"></i></button></div></div>
                            @endforeach
                        </div>
                        <div class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                    </div>

                    <x-form.controls.select name="is_active" id="is_active" label="حالة تفعيل الباقة" required :value="$plan->is_active ? '1' : '0'" col="col-md-6" :options="[['value' => '1', 'label' => 'مفعلة'], ['value' => '0', 'label' => 'معطلة']]" />
                    <x-form.controls.input type="date" name="available_from" id="available_from" label="تاريخ بدء الإتاحة" :value="$plan->available_from?->format('Y-m-d')" col="col-md-6" help="اتركه فارغاً لإتاحتها فوراً." />
                    <x-form.controls.textarea name="admin_notes" id="admin_notes" label="ملاحظات إدارية" :value="$plan->admin_notes" rows="2" col="col-12" />
                    <x-form.wizard.actions class="col-12"><x-form.wizard.previous-button :href="route('admin.plans.index')" text="رجوع" /><x-form.wizard.next-button /></x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="pricing-validation" :fields="['price', 'discount_percentage', 'yearly_renewal_price']">
                <div class="row g-3"><div class="col-12"><h6 class="fw-bold border-bottom pb-2 mb-0 text-primary"><i class="ti ti-cash me-1"></i>التسعير والعروض</h6></div>
                    <x-form.controls.input type="number" step="0.01" min="0" name="price" id="price" label="سعر الدورة الأساسية (ر.س)" :value="$plan->price" required col="col-md-6" />
                    <x-form.controls.input type="number" step="0.01" min="0" max="100" name="discount_percentage" id="discount_percentage" label="نسبة الخصم (%)" :value="$plan->discount_percentage" col="col-md-6" />
                    <x-form.controls.input type="number" step="0.01" min="0" name="yearly_renewal_price" id="yearly_renewal_price" label="سعر العرض السنوي الترويجي (ر.س)" :value="$plan->yearly_renewal_price" col="col-md-6" help="عرض سنوي اختياري للباقات الشهرية والربع سنوية." />
                    <x-form.wizard.actions class="col-12"><x-form.wizard.previous-button /><x-form.wizard.next-button /></x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="billing-validation" :fields="['subscription_billing_cycle']">
                <div class="row g-3"><div class="col-12"><h6 class="fw-bold border-bottom pb-2 mb-0 text-primary"><i class="ti ti-calendar-repeat me-1"></i>دورة الفوترة</h6></div>
                    @if ($hasSubscriptions)
                        <div class="col-md-6" data-form-field data-field-name="subscription_billing_cycle"><label class="form-label">دورة الفوترة الأساسية</label><input type="text" class="form-control" value="{{ $currentCycleLabel }}" readonly><input id="subscription_billing_cycle" type="hidden" name="subscription_billing_cycle" value="{{ $currentCycle }}" data-plan-label="{{ $currentCycleLabel }}" data-form-control></div>
                    @else
                        <x-form.controls.select name="subscription_billing_cycle" id="subscription_billing_cycle" label="دورة الفوترة الأساسية" required :value="$currentCycle" col="col-md-6" :options="array_map(fn($cycle) => ['value' => $cycle->value, 'label' => $cycle->label()], \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases())" />
                    @endif
                    <x-form.wizard.actions class="col-12"><x-form.wizard.previous-button /><x-form.wizard.next-button /></x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="limits-validation" :fields="['limits_mode', 'included_users', 'extra_user_license_price', 'properties_limit', 'storage_quota_gb', 'active_auctions_limit', 'unlimited_branches', 'branches_limit', 'domain_management_enabled']">
                <div class="row g-3"><div class="col-12"><h6 class="fw-bold border-bottom pb-2 mb-0 text-primary"><i class="ti ti-adjustments me-1"></i>الحدود والحصص</h6></div>
                    @if ($hasSubscriptions)
                        <div class="col-md-6" data-form-field data-field-name="limits_mode"><label class="form-label">وضع الحدود</label><input type="text" class="form-control" value="{{ $currentLimitsMode === 'unlimited' ? 'غير محدود' : 'محدود' }}" readonly><input id="limits_mode" type="hidden" name="limits_mode" value="{{ $currentLimitsMode }}" data-form-control></div>
                    @else
                        <x-form.controls.select name="limits_mode" id="limits_mode" label="وضع الحدود" required :value="$currentLimitsMode" col="col-md-6" :options="[['value' => 'limited', 'label' => 'محدود'], ['value' => 'unlimited', 'label' => 'غير محدود']]" />
                    @endif
                    <div class="col-12" id="unlimited-mode-alert" hidden><div class="alert alert-primary mb-0"><i class="ti ti-infinity me-2"></i>لن تُفرض حدود استخدام على هذه الباقة.</div></div>
                    <x-form.controls.input type="number" min="1" name="included_users" id="included_users" label="عدد المستخدمين المشمولين" :value="$plan->included_users" required col="col-md-6 limits-field" />
                    <x-form.controls.input type="number" step="0.01" min="0" name="extra_user_license_price" id="extra_user_license_price" label="سعر المستخدم الإضافي (ر.س)" :value="$plan->extra_user_license_price" required col="col-md-6 limits-field" />
                    <x-form.controls.input type="number" min="1" name="properties_limit" id="properties_limit" label="حد العقارات" :value="$plan->properties_limit" required col="col-md-6 limits-field" />
                    <x-form.controls.input type="number" min="1" name="storage_quota_gb" id="storage_quota_gb" label="حصة التخزين (GB)" :value="$plan->storage_quota_gb" required col="col-md-6 limits-field" />
                    <x-form.controls.input type="number" min="1" name="active_auctions_limit" id="active_auctions_limit" label="حد المزادات النشطة" :value="$plan->active_auctions_limit" required col="col-md-6 limits-field" />
                    <x-form.controls.select name="unlimited_branches" id="unlimited_branches" label="حد الفروع" :value="$plan->unlimited_branches ? '1' : '0'" col="col-md-6 limits-field" :options="[['value' => '0', 'label' => 'محدود بعدد'], ['value' => '1', 'label' => 'غير محدود']]" />
                    <div class="col-md-6 limits-field" id="branches_limit_container"><x-form.controls.input type="number" min="1" name="branches_limit" id="branches_limit" label="عدد الفروع" :value="$plan->branches_limit" required col="col-12" /></div>
                    <x-form.controls.select name="domain_management_enabled" id="domain_management_enabled" label="إدارة الدومينات" required :value="$plan->domain_management_enabled ? '1' : '0'" col="col-md-6" :options="[['value' => '1', 'label' => 'مفعلة'], ['value' => '0', 'label' => 'غير مفعلة']]" />
                    <x-form.wizard.actions class="col-12"><x-form.wizard.previous-button /><x-form.wizard.next-button text="التالي للمراجعة" /></x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="review-validation" :fields="[]"><div class="row justify-content-center"><div class="col-lg-7"><x-ui.package-card :plan="$plan" preview /><x-form.wizard.actions><x-form.wizard.previous-button /><x-form.wizard.save-button text="حفظ التعديلات" class="btn-success" /></x-form.wizard.actions></div></div></x-form.wizard.step>
        </form>
    </x-form.wizard.stepper>

    <template id="feature-template"><div class="border rounded p-3 mb-2 feature-item bg-lighter"><div class="d-flex align-items-end gap-2"><div class="flex-grow-1"><input type="text" name="features[{index}]" data-form-control class="form-control" placeholder="مثال: لوحة تحكم مخصصة" required></div><button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" aria-label="حذف الميزة"><i class="ti ti-trash"></i></button></div></div></template>
@endsection
