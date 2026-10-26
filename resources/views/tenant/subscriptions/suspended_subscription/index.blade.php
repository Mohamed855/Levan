@extends('tenant.layouts.layoutMaster')

@section('title', ' الاشتراك')

@section('page-style')
    @vite(['resources/assets/scss/tenant/pages/subscription-plans.scss', 'resources/assets/css/components/icons.css', 'resources/assets/vendor/scss/pages/front-page-pricing.scss'])
@endsection

<script>
    window.plansData = @json($plans->load('userRanges'));
</script>

@section('page-script')
    @vite(['resources/assets/js/web/basic-tamplate/pricing.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <section class="section-py bg-body-secondary">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-danger border-0 shadow-sm mb-0 text-center" role="alert">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="m-0">
                                    للاستفادة من جميع مميزات النظام وبدء استخدام الخدمات، يرجى اختيار الباقة المناسبة لك
                                    والاشتراك أولاً.
                                </p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Plans -->
    <section class="section-py">
        <div class="container">

            <div class="row g-4">
                <!-- Features Sidebar -->
                <div class="col-lg-5">
                    <div class="features-card card border-0 p-4">
                        <!-- Features Content -->
                        <div class="tab-content p-2" id="planTabsContent">
                            @foreach ($plans as $index => $plan)
                                <div class="text-center mb-4 pt-3">
                                    <h3 class="fw-bold mb-2 text-capitalize">{{ $plan->name }}</h3>
                                    <p class="text-muted mb-0">{{ $plan->description }}</p>
                                </div>

                                <h5 class="mb-5 fw-bold">
                                    مميزات الباقات
                                </h5>
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                    id="features-{{ $plan->id }}" role="tabpanel">
                                    <div class="features-list">
                                        @if (isset($plan->features) && count($plan->features) > 0)
                                            @foreach ($plan->features as $feature)
                                                <div class="feature-item">
                                                    <i class="ti ti-circle-check text-price me-2 fs-5"></i>
                                                    <span>{{ $feature }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-center text-muted py-4">
                                                <i class="ti ti-info-circle fs-3 mb-2"></i>
                                                <p>لا توجد مميزات محددة</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Plans Cards -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 pt-9 pb-2">
                        <label class="switch switch-md  ps-sm-12 me-0">
                            <span class="switch-label fs-6 text-body">شهري</span>
                            <input type="checkbox" class="switch-input price-duration-toggler" />
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                            <span class="switch-label fs-6 text-body">سنوي</span>
                        </label>
                        @if ($plans->max('yearly_discount') > 0)
                            <div class="mt-n5 ms-n10 ml-2 mb-10 d-none d-sm-flex align-items-center gap-1">
                                <i class="ti ti-corner-left-down ti-lg text-muted scaleX-n1-rtl"></i>
                                <span class="badge badge-sm bg-primary  mb-3">وفر حتى
                                    {{ $plans->max('yearly_discount') }}%</span>
                            </div>
                        @endif
                    </div>

                    <div class="row g-4">
                        @foreach ($plans as $index => $plan)
                            <div class="col-12">
                                <div
                                    class="card pricing-card shadow-sm border-0 {{ $index === 1 ? 'border-primary' : '' }}">
                                    @if ($index === 1)
                                        <div class="position-absolute top-0 start-50 translate-middle">
                                            <span class="badge bg-primary px-4 py-2">الأكثر شعبية</span>
                                        </div>
                                    @endif
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="text-center mb-4 py-4 bg-secondary">
                                            <div class="d-flex justify-content-center align-items-start mb-2">
                                                <sup class="h5 text-muted mt-2"><span class="icon-saudi_riyal"></span></sup>
                                                <h1 class="price-toggle price-monthly text-primary mb-0 mx-2 display-4 fw-bold"
                                                    id="display-price-monthly-{{ $plan->id }}">
                                                    {{ number_format($plan->price_monthly, 0) }}
                                                </h1>
                                                <h1 class="price-toggle price-yearly text-primary mb-0 mx-2 display-4 fw-bold d-none"
                                                    id="display-price-yearly-{{ $plan->id }}">
                                                    {{ number_format($plan->price_monthly * 12 * (1 - $plan->yearly_discount / 100), 0) }}
                                                </h1>
                                                <sub class="h6 text-muted mt-auto mb-2">/شهر</sub>
                                            </div>
                                            @if ($plan->yearly_discount > 0)
                                                <small
                                                    class="price-yearly price-yearly-toggle text-price fw-semibold d-none"
                                                    id="yearly-total-{{ $plan->id }}">
                                                    {{ number_format($plan->price_monthly * 12 * (1 - $plan->yearly_discount / 100), 2) }}
                                                    <span class="icon-saudi_riyal"></span> / سنوياً
                                                </small>
                                            @endif
                                        </div>

                                        <!-- Additional Licenses -->
                                        <div class="mb-4 p-3 bg-secondary">
                                            <label class="form-label fw-bold mb-2">
                                                عدد التراخيص الإضافية
                                            </label>
                                            <small class="text-muted d-block mb-3">
                                                الباقة تشمل ترخيص واحد افتراضياً
                                            </small>
                                            <input type="number" min="0" max="99999999" value="0"
                                                step="1"
                                                class="form-control form-control-lg text-center user-count-input"
                                                data-plan-id="{{ $plan->id }}" id="user-count-{{ $plan->id }}"
                                                placeholder="0">

                                            <!-- Price Ranges Table -->
                                            @if ($plan->userRanges && $plan->userRanges->count() > 0)
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-primary w-100 mb-2" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#ranges-{{ $plan->id }}">
                                                        نطاقات الأسعار
                                                        <i class="ti ti-info-circle ms-1"></i>

                                                    </button>
                                                    <div class="collapse" id="ranges-{{ $plan->id }}">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-hover mb-0"
                                                                id="ranges-table-{{ $plan->id }}">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th class="text-center">النطاق</th>
                                                                        <th class="text-center">الاسم</th>
                                                                        <th class="text-center">السعر</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($plan->userRanges->sortBy('from') as $range)
                                                                        <tr data-range-id="{{ $range->id }}"
                                                                            data-from="{{ $range->from }}"
                                                                            data-to="{{ $range->to ?? 999999 }}"
                                                                            data-name="{{ $range->name }}">
                                                                            <td class="text-center">
                                                                                @if ($range->to)
                                                                                    {{ $range->from }} -
                                                                                    {{ $range->to }}
                                                                                @else
                                                                                    {{ $range->from }}+
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center small">
                                                                                {{ $range->name }}</td>
                                                                            <td class="text-center fw-semibold price-cell"
                                                                                data-price="{{ $range->price }}">
                                                                                {{ number_format($range->price, 0) }}
                                                                                <span class="icon-saudi_riyal"></span>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div id="range-info-{{ $plan->id }}" class="mt-2"></div>
                                        </div>

                                        <!-- Price Summary -->
                                        <div class="mb-4 p-4 bg-white  border">
                                            <div class="d-flex justify-content-between mb-2">
                                                <small class="text-muted">المجموع قبل الضريبة:</small>
                                                <span class="total-price fw-semibold"
                                                    id="total-price-{{ $plan->id }}">
                                                    {{ number_format($plan->price_monthly, 2) }}
                                                </span>
                                            </div>

                                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                                <small class="text-muted">ضريبة القيمة المضافة (15%):</small>
                                                <span class="vat-amount text-muted" id="vat-amount-{{ $plan->id }}">
                                                    {{ number_format($plan->price_monthly * 0.15, 2) }}
                                                </span>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-dark">الإجمالي شامل الضريبة:</strong>
                                                <div class="text-end">
                                                    <div class="h4 text-primary fw-bold mb-0"
                                                        id="total-with-vat-{{ $plan->id }}">
                                                        {{ number_format($plan->price_monthly * 1.15, 2) }}
                                                        <small class="text-muted fs-6"><span
                                                                class="icon-saudi_riyal"></span></small>
                                                    </div>
                                                    <span class="icon-saudi_riyal"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CTA Button -->
                                        <a href="{{ tenant_route_name('subscribe.checkout', $plan->id) }}"
                                            class="btn {{ $index === 1 ? 'btn-primary' : 'btn-outline-primary' }} btn-lg w-100 subscription-btn"
                                            id="subscription-btn-{{ $plan->id }}"
                                            data-plan-id="{{ $plan->id }}">

                                            اختر هذه الباقة
                                            <i class="ti ti-shopping-cart ms-2"></i>
                                        </a>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
