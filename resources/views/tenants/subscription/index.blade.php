@extends('tenants.layouts.layoutMaster')

@section('title', 'إدارة الاشتراك')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/subscriptions/index.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">خطة الاشتراك</span>
                            <div class="d-flex align-items-center my-1">
                                <small class="mb-3 me-2">{{ $subscription_formatted['plan_name'] }}</small>
                            </div>
                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-playstation-circle ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">حالة الاشتراك</span>
                            <div class="d-flex align-items-center my-1">
                                <small class="mb-3 me-2 text-{{ $subscription_formatted['status_color'] }}">
                                    {{ $subscription_formatted['status'] }}
                                </small>
                            </div>
                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-{{ $subscription_formatted['status_color'] }}">
                                <i class="ti ti-help-circle ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">صلاحية الاشتراك
                                <small class="text-muted">
                                    (
                                    @if ($subscription_formatted['paid_days_remaining'] > 0)
                                        متبقي {{ $subscription_formatted['paid_days_remaining'] }} يوم
                                    @else
                                        <span class="badge bg-label-danger">منتهية</span>
                                    @endif
                                    )
                                </small>
                            </span>
                            <div class="d-flex align-items-center my-1">
                                <small class="mb-3 me-2">
                                    حتي تاريخ
                                    {{ $subscription_formatted['paid_period_end'] }}

                                </small>
                            </div>
                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-calendar-event ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">عدد التراخيص
                                <small class="text-muted">
                                    (متبقي
                                    {{ ($subscription_formatted['total_users'] ?? 0) - ($subscription_formatted['used_users'] ?? 0) }}
                                    ترخيص
                                    )
                                </small>
                            </span>

                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="progress flex-grow-1">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ $subscription_formatted['total_users'] > 0 ? ($subscription_formatted['used_users'] / $subscription_formatted['total_users']) * 100 : 0 }}%"
                                        aria-valuenow="{{ $subscription_formatted['used_users'] ?? 0 }}" aria-valuemin="0"
                                        aria-valuemax="{{ $subscription_formatted['total_users'] ?? 0 }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center my-1">
                                    <small
                                        class="mb-0 text-primary fw-bold">{{ $subscription_formatted['used_users'] ?? 0 }}</small>
                                    <span class="text-muted">/</span>
                                    <small class="mb-0 fw-bold">{{ $subscription_formatted['total_users'] ?? 0 }}</small>
                                </div>
                            </div>

                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-license ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <!---- تفاصيل الاشتراك  --->
            <div class="card h-100">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-description text-primary me-2"></i>
                        تفاصيل الاشتراك
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">رقم الاشتراك</small>
                                <h6 class="mb-0">{{ $subscription_formatted['subscription_number'] }}</h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1"> حالة الاشتراك</small>
                                <h6 class="mb-0">
                                    <span class="badge bg-label-{{ $subscription_formatted['status_color'] }}">
                                        {{ $subscription_formatted['status'] }}
                                    </span>
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">دورة الفوترة</small>
                                <h6 class="mb-0">
                                    سنوي
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">عدد التراخيص </small>
                                <h6 class="mb-0">
                                    {{ $subscription_formatted['total_users'] ?? 0 }} ترخيص
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">موعد الدفع القادم</small>
                                <h6 class="mb-0">{{ $subscription_formatted['next_payment_date'] }}</h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">سعر التجديد القادم </small>
                                <h6 class="mb-0">{{ $subscription_formatted['yearly_renewal_price'] }}</h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">التجديد التلقائي</small>
                                <h6 class="mb-0">
                                    @if ($subscription_formatted['auto_renew'])
                                        <span class="badge bg-label-success">
                                            مفعّل
                                        </span>
                                    @else
                                        <span class="badge bg-label-warning">
                                            معطّل
                                        </span>
                                    @endif
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">حد العقارات</small>
                                <h6 class="mb-0">
                                    {{ !empty($subscription_formatted['unlimited_properties']) ? 'غير محدود' : ($subscription_formatted['properties_limit'] ?? 'غير محدد') }}
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">المساحة التخزينية</small>
                                <h6 class="mb-0">
                                    {{ !empty($subscription_formatted['storage_quota_gb']) ? $subscription_formatted['storage_quota_gb'] . ' جيجابايت' : 'غير محدد' }}
                                </h6>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex flex-column">
                                <small class="small mb-1">المزادات النشطة</small>
                                <h6 class="mb-0">
                                    {{ !empty($subscription_formatted['unlimited_auctions']) ? 'غير محدود' : ($subscription_formatted['active_auctions_limit'] ?? 'غير محدد') }}
                                </h6>
                            </div>
                        </div>
                    </div>

                    <hr class="mb-4 mt-7">

                    <div class="text-end">
                        @can('subscriptions.renew')
                            <a href="{{ tenant_route_name('subscriptions.renew.checkout') }}" class="btn btn-primary ">
                                تجديد الاشتراك
                                <i class="ti ti-refresh mx-2"></i>
                            </a>
                        @endcan

                    </div>

                </div>
            </div>
        </div>

        <!----  التراخيص  --->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-description text-primary me-2"></i>
                        شراء التراخيص
                        <small class="text-muted">
                            (المستخدمين الاضافيين)
                        </small>
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>

                @if ($subscription_formatted['status_value'] !== 'active')
                    <div class="card-body pb-0">
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="ti ti-alert-circle ti-lg me-3"></i>
                            <div class="small">
                                <p class="mb-0">لا يمكنك شراء تراخيص حالياً. يرجى تجديد الاشتراك او دفع الفواتير
                                    المتاخرة للاستفادة
                                    من جميع المميزات.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <form action="{{ tenant_route_name('subscriptions.licenses.checkout') }}" method="GET"
                        id="upgrade-form">
                        <div class="card-body pb-0">
                            <div class="mb-4">
                                <div class="input-group input-group-md">

                                    <input name="new_licenses_count" type="number"
                                        class="form-control text-center fw-bold" id="custom-licenses" min="0"
                                        max="1000" value="0">
                                    <button class="btn btn-outline-secondary" type="button" id="increase-licenses">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="alert card mb-4">

                                <div class="d-flex justify-content-between align-items-center">
                                    <small>السعر لكل ترخيص (شامل الضريبة )</small>
                                    <small class=" mb-0">
                                        {{ $subscription_formatted['extra_user_license_price'] ?? 0 }}<span
                                            class="icon-saudi_riyal ms-1"></span>
                                    </small>
                                </div>

                                <hr class="my-2">

                                <table class="table table-borderless">
                                    <tbody>
                                        <tr class="">
                                            <td class="small pt-2">سيتم ترقية باقتك بعد التحديث للباقة </td>
                                            <td class="text-end small pt-2">
                                                <span class="badge bg-label-info will-change-badge fw-bold"
                                                    id="summary-new-range"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="  price-summary mb-0">
                                <div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small fw-bold">الاجمالي </span>
                                        <div>
                                            <strong id="licenses-cost">0.00</strong>
                                            <span class="icon-saudi_riyal ms-1"></span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <hr class="mb-4">


                            @can('subscriptions.add_licenses')
                                <div class="mt-5 text-end">
                                    <button type="submit" class="btn btn-primary disabled">
                                        تحديث الاشتراك
                                    </button>
                                </div>
                            @endcan

                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ==================== البيانات الأساسية ====================
            const currentPlanUsers = {{ $subscription_data->plan->included_users ?? 0 }};
            const currentTotalUsers = {{ $subscription_formatted['total_users'] ?? 0 }};
            const pricePerLicense =
                {{ str_replace(',', '', $subscription_formatted['extra_user_license_price']) }};

            const plans = @json($plans);
            plans.sort((a, b) => a.included_users - b.included_users);

            const currentPlan = {
                id: {{ $subscription_data->plan->id ?? 0 }},
                name: "{{ $subscription_data->plan->name ?? '' }}",
                included_users: currentPlanUsers
            };

            // ==================== عناصر الصفحة ====================
            const input = document.getElementById('custom-licenses');
            const increaseBtn = document.getElementById('increase-licenses');
            const totalCostElement = document.getElementById('licenses-cost');
            const newPlanBadge = document.getElementById('summary-new-range');
            const submitBtn = document.querySelector('#upgrade-form button[type="submit"]');
            const form = document.getElementById('upgrade-form');

            // ==================== دالة تحديد الباقة المناسبة ====================
            function getPlanForUsers(totalUsers) {
                if (totalUsers === 0) return currentPlan;

                for (let plan of plans) {
                    if (totalUsers <= plan.included_users) {
                        return plan;
                    }
                }

                return plans[plans.length - 1];
            }

            // ==================== دالة الحساب ====================
            function calculate() {
                const newLicensesToBuy = parseInt(input.value) || 0;
                const newTotalUsers = currentTotalUsers + newLicensesToBuy;
                const totalCost = newLicensesToBuy * pricePerLicense;
                const newPlan = getPlanForUsers(newTotalUsers);

                return {
                    newLicensesToBuy,
                    newTotalUsers,
                    totalCost,
                    newPlan,
                    planChanged: newPlan && newPlan.id !== currentPlan.id
                };
            }

            // ==================== تحديث الواجهة ====================
            function updateUI() {
                const calc = calculate();

                // عرض السعر
                totalCostElement.textContent = calc.totalCost.toFixed(2);

                // عرض الباقة
                if (calc.planChanged) {
                    newPlanBadge.textContent = calc.newPlan.name;
                    newPlanBadge.className = 'badge bg-label-success will-change-badge';
                } else {
                    newPlanBadge.textContent = currentPlan.name;
                    newPlanBadge.className = 'badge bg-label-info will-change-badge';
                }

                // تفعيل/تعطيل الزر
                if (calc.newLicensesToBuy === 0) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('disabled');
                } else {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('disabled');
                }
            }

            // ==================== الأحداث ====================
            increaseBtn.onclick = () => {
                const current = parseInt(input.value) || 0;
                if (current < 1000) {
                    input.value = current + 1;
                    updateUI();
                }
            };

            input.oninput = function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 0) this.value = 0;
                else if (value > 1000) this.value = 1000;
                updateUI();
            };

            // ==================== التهيئة ====================
            input.value = 0;
            updateUI();
        });
    </script>
@endsection
