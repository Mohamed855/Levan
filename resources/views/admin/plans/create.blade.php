@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة باقة جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            [
                'id' => 'info-validation',
                'title' => 'البيانات والتسعير والحدود',
                'subtitle' => 'البيانات الأساسية والحصص',
            ],
            ['id' => 'features-validation', 'title' => 'الميزات', 'subtitle' => 'ميزات الباقة التسويقية'],
            ['id' => 'review-validation', 'title' => 'المراجعة والنشر', 'subtitle' => 'معاينة الباقة وتأكيد الحفظ'],
        ];

        $infoFields = [
            'name',
            'code',
            'plan_type',
            'color',
            'is_active',
            'subscription_billing_cycle',
            'price',
            'discount_percentage',
            'yearly_renewal_price',
            'limits_mode',
            'included_users',
            'extra_user_license_price',
            'properties_limit',
            'storage_quota_gb',
            'active_auctions_limit',
            'unlimited_branches',
            'branches_limit',
            'domain_management_enabled',
            'description',
            'admin_notes',
            'available_from',
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="plan-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="plan-wizard-form" action="{{ route('admin.plans.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}">
                    @csrf

                    {{-- الخطوة الأولى: البيانات الأساسية والتسعير والحدود --}}
                    <x-form.wizard.step id="info-validation" :fields="$infoFields">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                    <i class="ti ti-info-circle me-1"></i>البيانات الأساسية
                                </h6>
                            </div>

                            <x-form.controls.input name="name" label="اسم الباقة" placeholder="مثال: الباقة الاحترافية"
                                required col="col-md-6" />

                            <x-form.controls.input name="code" label="كود الباقة (English Uppercase)"
                                placeholder="مثال: PRO_2026" maxlength="50" pattern="[A-Z][A-Z0-9_]*"
                                title="كود الباقة يجب أن يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط" required
                                col="col-md-6" help="يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط (A-Z, 0-9, _)."
                                class="font-monospace text-uppercase" />

                            <x-form.controls.select name="plan_type" id="plan_type" label="نوع الباقة" required
                                col="col-md-6" :options="array_map(
                                    fn($t) => ['value' => $t->value, 'label' => $t->label()],
                                    \App\Enums\Admin\Plans\PlanType::cases(),
                                )" />

                            <x-form.controls.select name="color" id="color" label="لون الباقة المميز" required
                                value="purple" col="col-md-6" :options="array_map(
                                    fn($c) => ['value' => $c->value, 'label' => $c->label()],
                                    \App\Enums\Admin\Plans\PlanColor::cases(),
                                )" />

                            <x-form.controls.input type="date" name="available_from" id="available_from"
                                label="تاريخ بدء الإتاحة" col="col-md-6"
                                help="اتركه فارغاً لإتاحة الباقة فوراً، أو حدد موعداً مستقبلياً لجدولتها." />

                            <x-form.controls.select name="is_active" id="is_active" label="حالة تفعيل الباقة" required
                                value="1" col="col-md-6" :options="[
                                    ['value' => '1', 'label' => 'مفعلة (تظهر في القوائم)'],
                                    ['value' => '0', 'label' => 'معطلة (مخفية عن العملاء)'],
                                ]" />

                            <div class="col-12 mt-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                    <i class="ti ti-cash me-1"></i>التسعير ودورة الفوترة
                                </h6>
                            </div>

                            <x-form.controls.select name="subscription_billing_cycle" id="subscription_billing_cycle"
                                label="دورة الفوترة الأساسية" required value="monthly" col="col-md-6" :options="array_map(
                                    fn($c) => ['value' => $c->value, 'label' => $c->label()],
                                    \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::cases(),
                                )" />

                            <x-form.controls.input type="number" step="0.01" min="0" name="price"
                                id="price" label="سعر الدورة الأساسية (ر.س)" placeholder="500" required
                                col="col-md-6" />

                            <x-form.controls.input type="number" step="0.01" min="0" max="100"
                                name="discount_percentage" id="discount_percentage" label="نسبة الخصم (%)"
                                placeholder="مثال: 10" value="0" col="col-md-6" />

                            <x-form.controls.input type="number" step="0.01" min="0" name="yearly_renewal_price"
                                id="yearly_renewal_price" label="سعر العرض السنوي الترويجي (ر.س)" placeholder="مثال: 5000"
                                col="col-md-6" help="عرض سنوي متاح للباقات الشهرية والربع سنوية." />

                            <div class="col-12 mt-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                                    <i class="ti ti-adjustments me-1"></i>وضع الحدود والحصص (Limits Mode)
                                </h6>
                            </div>

                            <x-form.controls.select name="limits_mode" id="limits_mode" label="وضع الحدود" required
                                value="limited" col="col-md-6" :options="[
                                    ['value' => 'limited', 'label' => 'محدود (تطبق الحصص المحددة)'],
                                    ['value' => 'unlimited', 'label' => 'غير محدود (∞ لا توجد قيود استخدام)'],
                                ]" />

                            <div class="col-12" id="unlimited-mode-alert" style="display: none;">
                                <div class="alert alert-primary d-flex align-items-center mb-0">
                                    <i class="ti ti-infinity fs-3 me-2"></i>
                                    <div>
                                        <strong>وضع الباقة غير المحدودة:</strong>
                                        لن يتم فرض أي حدود على عدد المستخدمين، العقارات، التخزين، المزادات، أو الفروع خلال
                                        مدة الاشتراك.
                                    </div>
                                </div>
                            </div>

                            <x-form.controls.input type="number" step="1" min="1" name="included_users"
                                id="included_users" label="عدد المستخدمين المشمولين" placeholder="مثال: 5"
                                value="5" required col="col-md-6 limits-field" />

                            <x-form.controls.input type="number" step="0.01" min="0"
                                name="extra_user_license_price" id="extra_user_license_price"
                                label="سعر ترخيص المستخدم الإضافي (ر.س)" placeholder="مثال: 50" value="50" required
                                col="col-md-6 limits-field" />

                            <x-form.controls.input type="number" step="1" min="1" name="properties_limit"
                                id="properties_limit" label="حد العقارات" placeholder="مثال: 50" value="50" required
                                col="col-md-6 limits-field" />

                            <x-form.controls.input type="number" step="1" min="1" name="storage_quota_gb"
                                id="storage_quota_gb" label="حصة التخزين (جيجابايت)" placeholder="مثال: 10"
                                value="10" required col="col-md-6 limits-field" />

                            <x-form.controls.input type="number" step="1" min="1"
                                name="active_auctions_limit" id="active_auctions_limit" label="حد المزادات النشطة"
                                placeholder="مثال: 10" value="10" required col="col-md-6 limits-field" />

                            <x-form.controls.select name="unlimited_branches" id="unlimited_branches"
                                label="نوع حد الفروع" value="0" col="col-md-6 limits-field" :options="[
                                    ['value' => '0', 'label' => 'محدود بعدد معين'],
                                    ['value' => '1', 'label' => 'فروع غير محدودة'],
                                ]" />

                            <div class="col-md-6 limits-field" id="branches_limit_container">
                                <x-form.controls.input type="number" step="1" min="1"
                                    name="branches_limit" id="branches_limit" label="حد الفروع المسموحة"
                                    placeholder="مثال: 1" value="1" required col="col-12" />
                            </div>

                            <x-form.controls.select name="domain_management_enabled" id="domain_management_enabled"
                                label="ميزة إدارة الدومينات" required value="1" col="col-md-6" :options="[
                                    ['value' => '1', 'label' => 'مفعّلة'],
                                    ['value' => '0', 'label' => 'غير مفعّلة'],
                                ]" />

                            <x-form.controls.textarea name="description" id="description" label="وصف الباقة"
                                rows="2" placeholder="وصف تفصيلي للباقة ومميزاتها..." col="col-12" />

                            <x-form.controls.textarea name="admin_notes" id="admin_notes"
                                label="ملاحظات إدارية (داخلية للإدارة فقط)" rows="2"
                                placeholder="ملاحظات سرية أو إدارية..." col="col-12" />

                            <x-form.wizard.actions class="col-12">
                                <x-form.wizard.previous-button :href="route('admin.plans.index')" text="رجوع" />
                                <div class="d-flex gap-2">
                                    <x-form.wizard.draft-button step="1" />
                                    <x-form.wizard.next-button :disabled="true" />
                                </div>
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="features-validation" :fields="['features']">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-primary"><i class="ti ti-list-check me-1"></i>ميزات
                                            الباقة التسويقية</h6>
                                        <small class="text-muted">هذه الميزات للعرض والتسويق أمام العملاء في صفحات التسجيل
                                            والـ Checkout.</small>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-feature">
                                        <i class="ti ti-plus me-1"></i>إضافة ميزة
                                    </button>
                                </div>
                                <div id="features-list">
                                    @foreach (old('features', ['دعم فني على مدار الساعة', 'تقارير وإحصاءات متقدمة']) as $index => $feature)
                                        <div class="border rounded p-3 mb-3 feature-item bg-light">
                                            <div class="d-flex align-items-end gap-2">
                                                <div class="flex-grow-1">
                                                    <label class="form-label mb-1 small">نص الميزة <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="features[{{ $index }}]"
                                                        class="form-control" value="{{ $feature }}"
                                                        placeholder="مثال: لوحة تحكم مخصصة" required />
                                                </div>
                                                <button type="button"
                                                    class="btn btn-icon btn-label-danger btn-remove-feature"
                                                    title="حذف الميزة">
                                                    <i class="ti ti-trash fs-5"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <x-form.wizard.actions class="col-12">
                                <x-form.wizard.previous-button />
                                <div class="d-flex gap-2">
                                    <x-form.wizard.draft-button step="2" />
                                    <x-form.wizard.next-button text="التالي (المراجعة والنشر)" />
                                </div>
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    {{-- الخطوة الثالثة: المراجعة والنشر --}}
                    <x-form.wizard.step id="review-validation" :fields="[]">
                        <div class="row g-4">
                            <div class="col-12">
                                <div id="publish-status-alert" class="alert alert-success d-flex align-items-center mb-0">
                                    <i class="ti ti-check-circle fs-3 me-2" id="publish-status-icon"></i>
                                    <div id="publish-status-text">
                                        الباقة نشطة ومتاحة الآن، ستظهر للعملاء في قوائم الاختيار والـ Checkout فور النشر.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card border shadow-none bg-light">
                                    <div class="card-body">
                                        <div
                                            class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <div>
                                                <h5 class="fw-bold mb-1" id="review-plan-name">-</h5>
                                                <span class="badge bg-label-dark font-monospace"
                                                    id="review-plan-code">-</span>
                                                <span class="badge bg-label-primary" id="review-plan-type">-</span>
                                                <span class="badge bg-label-info" id="review-billing-cycle">-</span>
                                                <span class="badge bg-label-success" id="review-limits-mode">-</span>
                                            </div>
                                            <div class="text-end">
                                                <div class="fs-4 fw-bold text-success" id="review-final-price">0.00 ر.س
                                                </div>
                                                <div class="small text-muted" id="review-price-breakdown">السعر الأساسي:
                                                    0.00 ر.س</div>
                                            </div>
                                        </div>

                                        <div class="row g-3 border-top pt-3">
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">العرض السنوي</small>
                                                <strong id="review-annual-offer">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">المستخدمين</small>
                                                <strong id="review-users">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">العقارات</small>
                                                <strong id="review-properties">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">التخزين</small>
                                                <strong id="review-storage">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">المزادات النشطة</small>
                                                <strong id="review-auctions">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">الفروع</small>
                                                <strong id="review-branches">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">إدارة الدومينات</small>
                                                <strong id="review-domains">-</strong>
                                            </div>
                                            <div class="col-md-3 col-6">
                                                <small class="text-muted d-block">بدء الإتاحة</small>
                                                <strong id="review-available-from">متاحة فوراً</strong>
                                            </div>
                                        </div>

                                        <div class="border-top pt-3 mt-3">
                                            <h6 class="fw-bold mb-2">الميزات المشمولة:</h6>
                                            <ul class="mb-0 ps-3" id="review-features-list">
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-form.wizard.actions class="col-12">
                                <x-form.wizard.previous-button />
                                <div class="d-flex gap-2">
                                    <x-form.wizard.draft-button step="3" />
                                    <x-form.wizard.save-button text="تأكيد ونشر الباقة" class="btn-success"
                                        icon="ti ti-device-floppy me-1" />
                                </div>
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                </form>
            </x-form.wizard.stepper>
        </div>
    </div>

    {{-- Templates --}}
    <template id="feature-template">
        <div class="border rounded p-3 mb-3 feature-item bg-light">
            <div class="d-flex align-items-end gap-2">
                <div class="flex-grow-1">
                    <label class="form-label mb-1 small">نص الميزة <span class="text-danger">*</span></label>
                    <input type="text" name="features[{index}]" class="form-control"
                        placeholder="مثال: لوحة تحكم مخصصة" required />
                </div>
                <button type="button" class="btn btn-icon btn-label-danger btn-remove-feature" title="حذف الميزة">
                    <i class="ti ti-trash fs-5"></i>
                </button>
            </div>
        </div>
    </template>
@endsection
