@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة باقة جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/scss/shared/package-card.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/plans/form-ui.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'basics-validation', 'title' => 'الأساسيات والميزات'],
            ['id' => 'pricing-validation', 'title' => 'التسعير والفوترة'],
            ['id' => 'limits-validation', 'title' => 'الحدود'],
            ['id' => 'review-validation', 'title' => 'المراجعة والنشر'],
        ];
    @endphp

    <div class="row g-4 align-items-start">
        <div class="col-lg-9" data-plan-form-column>
            <x-form.wizard.stepper id="plan-wizard" class="h-100" :steps="$steps">
                <x-ui.validation-errors />

                <form id="plan-wizard-form" action="{{ route('admin.plans.store') }}" method="POST" autocomplete="off"
                    data-form-validate data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}" data-draft-resource-key="admin.plan-create">
                    @csrf
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
                        <div class="row g-3">
                            <x-form.controls.input name="name" label="اسم الباقة" placeholder="مثال: الباقة الاحترافية"
                                required col="col-md-6" />
                            <x-form.controls.input name="code" label="كود الباقة (English Uppercase)"
                                placeholder="مثال: PRO_2026" maxlength="50" pattern="[A-Z][A-Z0-9_]*"
                                title="يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط" required col="col-md-6"
                                class="font-monospace text-uppercase" />
                            <x-form.controls.select name="plan_type" id="plan_type" label="نوع الباقة" required
                                col="col-md-6" :options="array_map(
                                    fn($type) => ['value' => $type->value, 'label' => $type->label()],
                                    \App\Enums\Admin\Plans\PlanType::cases(),
                                )" />
                            <x-form.controls.select name="color" id="color" label="لون الباقة المميز" required
                                value="purple" col="col-md-6" :options="array_map(
                                    fn($color) => ['value' => $color->value, 'label' => $color->label()],
                                    \App\Enums\Admin\Plans\PlanColor::cases(),
                                )" />
                            <x-form.controls.textarea name="description" id="description" label="وصف الباقة" rows="3"
                                placeholder="وصف واضح لقيمة الباقة..." col="col-md-6" />

                            <x-form.controls.select name="is_active" id="is_active" label="حالة تفعيل الباقة" required
                                value="1" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />
                            <x-form.controls.input type="date" name="available_from" id="available_from"
                                label="تاريخ بدء الإتاحة" col="col-md-6" help="اتركه فارغاً لإتاحتها فوراً." />
                            <x-form.controls.textarea name="admin_notes" id="admin_notes" label="ملاحظات إدارية"
                                rows="2" col="col-md-6" />

                            <div class="col-12" data-form-field data-field-name="features">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">ميزات الباقة <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-primary" id="btn-add-feature"><i
                                            class="ti ti-plus me-1"></i>إضافة ميزة</button>
                                </div>
                                <div id="features-list">
                                    @foreach (old('features', []) as $index => $feature)
                                        <div class="feature-item mb-2">
                                            <div class="d-flex align-items-end gap-2">
                                                <div class="flex-grow-1"><input type="text"
                                                        name="features[{{ $index }}]" data-form-control
                                                        class="form-control" value="{{ $feature }}"
                                                        placeholder="مثال: لوحة تحكم مخصصة" required></div><button
                                                    type="button" class="btn btn-icon btn-danger btn-remove-feature"
                                                    aria-label="حذف الميزة"><i class="ti ti-trash"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                            </div>
                        </div>
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.plans.index')" text="رجوع" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="pricing-validation" :fields="['price', 'subscription_billing_cycle', 'discount_percentage', 'yearly_renewal_price']">
                        <div class="row g-3">
                            <x-form.controls.input type="number" step="0.01" min="0" name="price"
                                id="price" label="سعر الدورة الأساسية (ر.س)" placeholder="500" required
                                col="col-md-6" />
                            <x-form.controls.select name="subscription_billing_cycle" id="subscription_billing_cycle"
                                label="دورة الفوترة الأساسية" required value="monthly" col="col-md-6" :options="array_map(
                                    fn($cycle) => ['value' => $cycle->value, 'label' => $cycle->label()],
                                    \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases(),
                                )" />
                            <x-form.controls.input type="number" step="0.01" min="0" max="100"
                                name="discount_percentage" id="discount_percentage" label="نسبة الخصم (%)"
                                value="0" col="col-md-6" />
                            <x-form.controls.input type="number" step="0.01" min="0"
                                name="yearly_renewal_price" id="yearly_renewal_price"
                                label="سعر العرض السنوي الترويجي (ر.س)" col="col-md-6"
                                help="عرض سنوي اختياري للباقات الشهرية والربع سنوية." />
                        </div>
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="limits-validation" :fields="[
                        'limits_mode',
                        'included_users',
                        'extra_user_license_price',
                        'properties_limit',
                        'storage_quota_gb',
                        'active_auctions_limit',
                        'domain_management_enabled',
                    ]">
                        <div class="row g-3">
                            <x-form.controls.select name="limits_mode" id="limits_mode" label="وضع الحدود" required
                                value="limited" col="col-md-6" :enum="\App\Enums\Admin\Plans\PlanLimitsMode::class" />
                            <div class="col-12" id="unlimited-mode-alert" hidden>
                                <div class="alert alert-primary mb-0"><i class="ti ti-infinity me-2"></i>لن تُفرض حدود
                                    استخدام على هذه الباقة.</div>
                            </div>
                            <x-form.controls.input type="number" min="1" name="included_users"
                                id="included_users" label="عدد المستخدمين المشمولين" value="5" required
                                col="col-md-6 limits-field" />
                            <x-form.controls.input type="number" step="0.01" min="0"
                                name="extra_user_license_price" id="extra_user_license_price"
                                label="سعر المستخدم الإضافي (ر.س)" value="50" required col="col-md-6 limits-field" />
                            <x-form.controls.input type="number" min="1" name="properties_limit"
                                id="properties_limit" label="حد العقارات" value="50" required
                                col="col-md-6 limits-field" />
                            <x-form.controls.input type="number" min="1" name="storage_quota_gb"
                                id="storage_quota_gb" label="حصة التخزين (GB)" value="10" required
                                col="col-md-6 limits-field" />
                            <x-form.controls.input type="number" min="1" name="active_auctions_limit"
                                id="active_auctions_limit" label="حد المزادات النشطة" value="10" required
                                col="col-md-6 limits-field" />
                            <x-form.controls.select name="domain_management_enabled" id="domain_management_enabled"
                                label="إدارة الدومينات" required value="1" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />
                        </div>
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button text="التالي للمراجعة" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="review-validation" :fields="[]">
                        <div class="row g-4 mb-4 assignment-review">
                            <div class="col-lg-9 d-flex flex-column gap-4">
                                <x-form.wizard.review-card title="الأساسيات والميزات" step="1"
                                    class="assignment-review__card">
                                    <dl class="row mb-0 small">
                                        <dt class="col-5 text-muted">اسم الباقة</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-name">-</dd>
                                        <dt class="col-5 text-muted">كود الباقة</dt>
                                        <dd class="col-7 fw-semibold font-monospace" id="plan-review-code">-</dd>
                                        <dt class="col-5 text-muted">نوع الباقة</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-type">-</dd>
                                        <dt class="col-5 text-muted">لون الباقة</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-color">-</dd>
                                        <dt class="col-5 text-muted">حالة التفعيل</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-status">-</dd>
                                        <dt class="col-5 text-muted">تاريخ بدء الإتاحة</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-available-from">-</dd>
                                        <dt class="col-5 text-muted">الوصف</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-description">-</dd>
                                        <dt class="col-5 text-muted">الميزات المشمولة</dt>
                                        <dd class="col-7" id="plan-review-features">-</dd>
                                    </dl>
                                </x-form.wizard.review-card>

                                <x-form.wizard.review-card title="التسعير والفوترة" step="2"
                                    class="assignment-review__card">
                                    <dl class="row mb-0 small">
                                        <dt class="col-5 text-muted">السعر الأساسي</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-price">-</dd>
                                        <dt class="col-5 text-muted">دورة الفوترة الأساسية</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-billing-cycle">-</dd>
                                        <dt class="col-5 text-muted">نسبة الخصم</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-discount">-</dd>
                                        <dt class="col-5 text-muted">السعر بعد الخصم</dt>
                                        <dd class="col-7 fw-semibold text-primary" id="plan-review-final-price">-</dd>
                                        <dt class="col-5 text-muted">سعر العرض السنوي</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-renewal-price">-</dd>
                                    </dl>
                                </x-form.wizard.review-card>

                                <x-form.wizard.review-card title="الحدود والصلاحيات" step="3"
                                    class="assignment-review__card">
                                    <dl class="row mb-0 small">
                                        <dt class="col-5 text-muted">وضع الحدود</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-limits-mode">-</dd>
                                        <dt class="col-5 text-muted">عدد المستخدمين المشمولين</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-users">-</dd>
                                        <dt class="col-5 text-muted">سعر ترخيص المستخدم الإضافي</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-extra-user-price">-</dd>
                                        <dt class="col-5 text-muted">حد العقارات</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-properties">-</dd>
                                        <dt class="col-5 text-muted">حصة التخزين</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-storage">-</dd>
                                        <dt class="col-5 text-muted">حد المزادات النشطة</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-auctions">-</dd>
                                        <dt class="col-5 text-muted">إدارة الدومينات</dt>
                                        <dd class="col-7 fw-semibold" id="plan-review-domain">-</dd>
                                    </dl>
                                </x-form.wizard.review-card>
                            </div>

                            <div class="col-lg-3">
                                <x-ui.package-card preview />
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
        <aside class="col-lg-3 d-none d-lg-block" data-package-preview-aside>
            <div class="position-sticky" style="top: 1.5rem;"><x-ui.package-card preview /></div>
        </aside>
    </div>

    <template id="feature-template">
        <div class="feature-item mb-2">
            <div class="d-flex align-items-end gap-2">
                <div class="flex-grow-1"><input type="text" name="features[{index}]" data-form-control
                        class="form-control" placeholder="مثال: لوحة تحكم مخصصة" required></div><button type="button"
                    class="btn btn-icon btn-danger btn-remove-feature" aria-label="حذف الميزة"><i
                        class="ti ti-trash"></i></button>
            </div>
        </div>
    </template>
@endsection
