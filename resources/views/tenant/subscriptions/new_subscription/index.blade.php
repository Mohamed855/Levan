@extends('tenant.layouts.layoutMaster')

@section('title', ' الاشتراك')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/scss/tenant/pages/subscription-plans.scss'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <section class="section-py bg-body-secondary">
        <div class="">
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

    <div class="">
        <div class="row g-2 mt-3">
            @foreach ($plans as $plan)
                <div class="col-xxl-3 col-md-6">
                    <div class="card plan-card h-100 d-flex flex-column">
                        <div class="card-header text-center">
                            <h4 class="mb-1 fw-bold">{{ $plan->name }}</h4>
                            <div class="text-muted small mb-3">
                                {{ $plan->description }}
                            </div>
                            <div class="d-flex flex-column align-items-center">
                                @if ($plan->discount_percentage > 0)
                                    <span class="plan-old-price text-decoration-line-through mb-1">
                                        {{ number_format($plan->price) }}
                                    </span>

                                    <span class="badge bg-danger mb-2">
                                        وفر حتي {{ $plan->discount_percentage }}%
                                    </span>

                                    <div class="plan-price">
                                        {{ number_format($plan->price_after_discount) }}
                                        <span class="icon-saudi_riyal"></span>
                                    </div>

                                    <div class="price-note">
                                        المبلغ يُدفع مرة واحدة فقط
                                    </div>
                                @else
                                    <div class="plan-price">
                                        {{ number_format($plan->price) }}
                                        <span class="icon-saudi_riyal"></span>
                                    </div>

                                    <div class="price-note">
                                        المبلغ يُدفع مرة واحدة فقط
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3">
                                <div class="text-muted plan-section-title">
                                    التجديد السنوي
                                </div>
                                <div class="fw-bold">
                                    {{ number_format($plan->yearly_renewal_price) }}
                                    <span class="icon-saudi_riyal"></span>
                                </div>

                                <div class="text-muted plan-section-title mt-1">
                                    سعر المستخدم الإضافي
                                </div>
                                <div class="fw-bold">
                                    {{ number_format($plan->extra_user_license_price) }} <span
                                        class="icon-saudi_riyal"></span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body px-4 d-flex flex-column">
                            <ul class="list-unstyled pricing-list flex-grow-1 mt-3">
                                @foreach ($plan->features as $feature)
                                    <li class="mb-2">
                                        <span class="me-2">
                                            <i class="ti ti-progress-check text-success"></i>
                                        </span>
                                        <span class="plan-feature">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="d-grid mt-3">
                                <a href="{{ tenant_route_name('subscribe.checkout', $plan->id) }}"
                                    class="btn btn-primary py-2 fw-bold">
                                    اشترك الآن
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
