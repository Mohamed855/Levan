@php
    $is_edit = $is_edit ?? false;
    $steps = [
        ['id' => 'step-company-plan', 'title' => 'الشركة والباقة'],
        ['id' => 'step-payment-details', 'title' => 'تفاصيل الاشتراك والدفع'],
        ['id' => 'step-subscription-review', 'title' => 'مراجعة واعتماد'],
    ];
@endphp

<div class="row">
    <div class="col-12">
        <x-form.wizard.stepper id="subscription-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="subscription-wizard-form"
                action="{{ $is_edit ? '#' : route('admin.subscriptions.store') }}"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.subscription-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

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
                            <div class="card border border-primary bg-primary shadow-none mb-0">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold text-white mb-3">
                                        <i class="ti ti-building me-1 text-white"></i> بيانات الشركة المحددة
                                    </h6>
                                    <div class="row g-3 text-white">
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">الاسم التجاري</small>
                                            <span class="fw-bold fs-6" id="cd-name">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">السجل التجاري</small>
                                            <span class="fw-semibold" id="cd-commercial">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">الرقم الضريبي</small>
                                            <span class="fw-semibold" id="cd-tax">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">المدينة</small>
                                            <span class="fw-semibold" id="cd-city">-</span>
                                        </div>
                                        <div class="col-12 border-top border-white pt-2 mt-2">
                                            <small class="d-block text-white">حالة الاشتراك الحالي</small>
                                            <span class="badge bg-danger mt-1" id="cd-last-sub">منتهي الصلاحية</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- بطاقة ملخص الباقة المحددة (للقراءة فقط) -->
                        <div class="col-12" id="plan-details-card" style="display: none;">
                            <div class="card border border-success bg-success shadow-none mb-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title fw-bold text-white mb-0">
                                            <i class="ti ti-package me-1 text-white"></i> تفاصيل الباقة المحددة
                                        </h6>
                                        <span class="badge bg-white text-success fw-bold fs-6" id="pd-price">-</span>
                                    </div>
                                    <div class="row g-3 text-white">
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">أقصى عدد مستخدمين</small>
                                            <span class="fw-bold" id="pd-users">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">أقصى عدد عقارات</small>
                                            <span class="fw-bold" id="pd-props">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">المساحة التخزينية</small>
                                            <span class="fw-bold" id="pd-storage">-</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <small class="d-block text-white">أقصى عدد مزادات نشطة</small>
                                            <span class="fw-bold" id="pd-auctions">-</span>
                                        </div>
                                        <div class="col-12 border-top border-white pt-2 mt-2" id="pd-features-wrapper">
                                            <small class="d-block text-white mb-1">المميزات الإضافية</small>
                                            <div class="d-flex flex-wrap gap-1" id="pd-features"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="route('admin.subscriptions.index')" />
                        <div class="d-flex gap-2">
                            @if (!$is_edit)
                                <x-form.wizard.draft-button step="1" />
                            @endif
                            <x-form.wizard.next-button />
                        </div>
                    </x-form.wizard.actions>
                </x-form.wizard.step>

                {{-- ═══════════════════════════════════════
                     الخطوة 2: تفاصيل الاشتراك والدفع
                ═══════════════════════════════════════ --}}
                <x-form.wizard.step id="step-payment-details" :fields="[
                    'start_date',
                    'end_date',
                    'period_months',
                    'coupon_code',
                    'payment_method',
                    'payment_status',
                    'transaction_id',
                    'payment_proof',
                ]">
                    <div class="row g-4">
                        <!-- مدة الاشتراك بالشهور -->
                        <x-form.controls.input type="number" name="period_months" id="period_months"
                            label="مدة الاشتراك (بالشهور)" value="12" min="1" max="120" required
                            col="col-md-4" help="الافتراضي: 12 شهر (سنة كاملة)" />

                        <!-- تاريخ البداية -->
                        <x-form.controls.input type="date" name="start_date" id="start_date"
                            label="تاريخ بداية الاشتراك" :value="date('Y-m-d')" required col="col-md-4" />

                        <!-- تاريخ النهاية (محسوب تلقائياً) -->
                        <x-form.controls.input type="date" name="end_date" id="end_date" label="تاريخ نهاية الاشتراك"
                            :value="date('Y-m-d', strtotime('+1 year'))" required readonly col="col-md-4"
                            help="يتم حسابه تلقائياً بناءً على المدة والبداية" />

                        <!-- كوبون الخصم -->
                        <div class="col-md-6">
                            <label class="form-label" for="coupon_code">كود كوبون الخصم</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" id="coupon_code" class="form-control text-uppercase"
                                    placeholder="أدخل كود الكوبون إن وجد" style="text-transform: uppercase;">
                                <button class="btn btn-outline-primary" type="button" id="btn-validate-coupon">
                                    <i class="ti ti-ticket me-1"></i> فحص الكوبون
                                </button>
                            </div>
                            <div id="coupon-feedback" class="form-text mt-1"></div>
                        </div>

                        <!-- طريقة الدفع -->
                        <x-form.controls.select name="payment_method" id="payment_method" label="طريقة الدفع" required
                            :options="__('subscriptions.payment_methods')" value="bank_transfer" col="col-md-6" />

                        <!-- حالة الدفع -->
                        <x-form.controls.select name="payment_status" id="payment_status" label="حالة الدفع الآن" required
                            :options="__('subscriptions.payment_statuses')" value="completed" col="col-md-6"
                            help="في حالة 'مكتمل' سيتم تفعيل الاشتراك وإنشاء الفاتورة فوراً." />

                        <!-- رقم المرجع / الحوالة -->
                        <x-form.controls.input name="transaction_id" id="transaction_id"
                            label="رقم العملية / الحوالة البنكية" placeholder="مثال: TRX-98234190" col="col-md-6"
                            help="مهم لتوثيق التحويلات البنكية ومطابقة الحسابات" />

                        <!-- إيصال الدفع -->
                        <x-form.controls.file name="payment_proof" id="payment_proof" label="إيصال الدفع / الحوالة (مستند)"
                            accept="image/*,application/pdf" col="col-12"
                            help="الامتدادات المسموحة: PDF, PNG, JPG, JPEG (الحد الأقصى 5 ميجابايت)" />

                        <!-- ملاحظات إدارية -->
                        <x-form.controls.textarea name="admin_notes" id="admin_notes" label="ملاحظات إدارية داخلية"
                            placeholder="أي ملاحظات تخص الاتفاق أو الاستثناءات..." rows="3" col="col-12" />

                        <!-- بطاقة الحسابات المالية الحية -->
                        <div class="col-12">
                            <div class="card border border-info bg-light shadow-none mb-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-info">
                                        <i class="ti ti-calculator me-1"></i> الحسبة المالية المتوقعة للاشتراك
                                    </h6>
                                    <div class="row g-2 text-dark">
                                        <div class="col-sm-6 col-md-3">
                                            <span class="text-muted d-block small">قيمة الباقة الأصلية:</span>
                                            <span class="fw-bold fs-6" id="calc-plan-price">0.00 ر.س</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <span class="text-muted d-block small">قيمة الخصم:</span>
                                            <span class="fw-bold fs-6 text-danger" id="calc-discount">0.00 ر.س</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <span class="text-muted d-block small">ضريبة القيمة المضافة (15%):</span>
                                            <span class="fw-bold fs-6" id="calc-tax">0.00 ر.س</span>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <span class="text-muted d-block small">المبلغ الإجمالي شامل الضريبة:</span>
                                            <span class="fw-bold fs-5 text-primary" id="calc-final-total">0.00 ر.س</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button />
                        <div class="d-flex gap-2">
                            @if (!$is_edit)
                                <x-form.wizard.draft-button step="2" />
                            @endif
                            <x-form.wizard.next-button />
                        </div>
                    </x-form.wizard.actions>
                </x-form.wizard.step>

                {{-- ═══════════════════════════════════════
                     الخطوة 3: مراجعة واعتماد
                ═══════════════════════════════════════ --}}
                <x-form.wizard.step id="step-subscription-review">
                    <div class="row g-4">
                        <div class="col-12 d-flex flex-column gap-4">
                            <!-- تنبيه المراجعة -->
                            <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                                <i class="ti ti-alert-triangle fs-3"></i>
                                <span>
                                    يرجى مراجعة كافة بيانات الاشتراك والباقة والدفع بدقة قبل الاعتماد النهائي؛ حيث سيتم
                                    تفعيل الصلاحيات وتوليد الفاتورة فوراً.
                                </span>
                            </div>

                            <div class="row g-3">
                                <!-- ملخص الشركة -->
                                <div class="col-lg-6">
                                    <x-form.wizard.review-card title="بيانات الشركة المستفيدة" step="1" col=""
                                        class="h-100 assignment-review__card">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">اسم الشركة</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-company-name">-</dd>
                                            <dt class="col-5 text-muted">السجل التجاري</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-commercial-number">-</dd>
                                            <dt class="col-5 text-muted">الرقم الضريبي</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-tax-number">-</dd>
                                            <dt class="col-5 text-muted">المدينة</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-city">-</dd>
                                            <dt class="col-5 text-muted">آخر اشتراك منتهٍ</dt>
                                            <dd class="col-7 fw-semibold text-danger" id="sub_review-last-sub">-</dd>
                                        </dl>
                                    </x-form.wizard.review-card>
                                </div>

                                <!-- ملخص الباقة والحدود -->
                                <div class="col-lg-6">
                                    <x-form.wizard.review-card title="تفاصيل الباقة والحدود" step="1" col=""
                                        class="h-100 assignment-review__card">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">الباقة</dt>
                                            <dd class="col-7 fw-semibold text-primary" id="sub_review-plan-name">-</dd>
                                            <dt class="col-5 text-muted">عدد المستخدمين</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-plan-users">-</dd>
                                            <dt class="col-5 text-muted">حد العقارات</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-plan-props">-</dd>
                                            <dt class="col-5 text-muted">مساحة التخزين</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-plan-storage">-</dd>
                                            <dt class="col-5 text-muted">المزادات النشطة</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-plan-auctions">-</dd>
                                            <dt class="col-5 text-muted">المميزات</dt>
                                            <dd class="col-7" id="sub_review-plan-features">-</dd>
                                        </dl>
                                    </x-form.wizard.review-card>
                                </div>

                                <!-- مدة الاشتراك والمالية -->
                                <div class="col-lg-6">
                                    <x-form.wizard.review-card title="مدة الاشتراك والمالية" step="2" col=""
                                        class="h-100 assignment-review__card">
                                        <dl class="row mb-0 small">
                                            <dt class="col-5 text-muted">تاريخ البداية</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-start-date">-</dd>
                                            <dt class="col-5 text-muted">تاريخ النهاية</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-end-date">-</dd>
                                            <dt class="col-5 text-muted">طريقة الدفع</dt>
                                            <dd class="col-7 fw-semibold" id="sub_review-payment-method">-</dd>
                                            <dt class="col-5 text-muted">حالة الدفع</dt>
                                            <dd class="col-7">
                                                <span id="sub_review-payment-status" class="badge bg-secondary">-</span>
                                            </dd>
                                            <dt class="col-5 text-muted">حالة التفعيل المتوقعة</dt>
                                            <dd class="col-7">
                                                <span id="sub_review-activation-status" class="badge bg-success">مفعل فوراً</span>
                                            </dd>
                                            <dt class="col-5 text-muted">المبلغ الإجمالي النهائي</dt>
                                            <dd class="col-7 fw-bold text-primary fs-5" id="sub_review-final-total">0.00 ر.س</dd>
                                        </dl>
                                    </x-form.wizard.review-card>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button />
                        <div class="d-flex gap-2">
                            @if (!$is_edit)
                                <x-form.wizard.draft-button />
                            @endif
                            <x-form.wizard.save-button />
                        </div>
                    </x-form.wizard.actions>
                </x-form.wizard.step>
            </form>
        </x-form.wizard.stepper>
    </div>
</div>
