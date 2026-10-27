@extends('layouts.web')

@section('title', 'ليفان — تجربة مجانية')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/shared/form-validation.js', 'resources/assets/js/web/checkout.js'])
@endsection

@section('content')
    @php
        $trialSummary = [
            'summaryPlanName' => 'تجربة مجانية',
            'summaryPrice' => 0,
            'priceWithoutTax' => 0,
            'taxAmount' => 0,
            'finalTotal' => 0,
            'showCoupon' => false,
            'isTrial' => true,
            'paymentStep' => false,
            'showSubmitButton' => true,
        ];
    @endphp
    <main class="site-main">
        <section class="site-page-hero">
            <div class="container">
                <div class="scroll-revealed mx-auto text-center">
                    <span class="hero-eyebrow"><i class="ti ti-receipt-2"></i> طلب تجربة النظام</span>
                    <h1 class="site-page-hero-title">تجربة مجانية</h1>
                    <p class="site-page-hero-desc mb-3">جرّب ليفان لمدة 7 أيام بعد موافقة الإدارة، بدون رسوم أو بطاقة بنكية.
                    </p>
                    <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill bg-white/10 my-2">
                        <span class="text-2xl font-bold text-white">0</span>
                        <span class="icon-saudi_riyal"></span>
                        <span class="text-sm text-white/80">| تجربة مجانية لمدة 7 أيام</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="checkout-section">
            <div class="container">
                <x-ui.validation-errors class="mb-4" />
                @if ($otpStep)
                    <div class="checkout-grid">
                        <div class="checkout-main-col">
                            <div class="checkout-card scroll-revealed otp-card">
                                <div class="checkout-card-header d-flex align-items-center justify-content-between">
                                    <h3 class="checkout-card-title"><i class="ti ti-mail-check"></i> تأكيد البريد الإلكتروني
                                    </h3>
                                    <a href="{{ route('site.trial.edit') }}" class="checkout-back-link text-sm">
                                        <i class="ti ti-edit"></i>
                                        تعديل البيانات
                                    </a>
                                </div>
                                <div class="checkout-card-body">
                                    <div class="otp-intro">
                                        <div class="otp-intro-icon"><i class="ti ti-shield-check"></i></div>
                                        <div class="otp-intro-content flex-grow-1">
                                            <p class="mb-1">تم إرسال رمز التحقق إلى <strong
                                                    class="text-break">{{ $checkoutData['billing_email'] ?? '' }}</strong>.
                                            </p>
                                            <span class="otp-intro-hint">أدخل الرمز المكون من 6 أرقام خلال دقيقتين لإرسال
                                                طلب التجربة.</span>
                                        </div>
                                    </div>
                                    <form id="otp-form" method="POST" action="{{ route('site.trial.verify') }}"
                                        data-form-validate novalidate>
                                        @csrf
                                        <x-form.controls.input name="otp" label="رمز التحقق" inputmode="numeric"
                                            maxlength="6" autocomplete="one-time-code" class="otp-input" required />
                                        <div class="d-flex align-items-center justify-content-between mt-3">
                                            <div id="checkout-otp-timer" class="otp-timer fw-bold"
                                                data-expires-at="{{ $otpExpiresAt }}"></div>
                                            <a href="{{ route('site.trial.edit') }}"
                                                class="text-sm font-semibold text-primary text-decoration-none">
                                                <i class="ti ti-edit"></i> تعديل البيانات
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <aside class="checkout-summary-col">
                            @include(
                                'web.partials.checkout-summary',
                                array_merge($trialSummary, [
                                    'formId' => 'otp-form',
                                    'submitId' => 'submit-otp-btn',
                                    'submitLabel' => 'التأكيد والإرسال',
                                    'showEditLink' => true,
                                    'editUrl' => route('site.trial.edit'),
                                ]))
                        </aside>
                    </div>
                @else
                    <form id="payment-form" method="POST" data-checkout-step="details"
                        action="{{ route('site.trial.store') }}" autocomplete="off" data-form-validate
                        data-validation-url="{{ route('site.form-validation') }}" novalidate>
                        @csrf
                        <div class="checkout-grid">
                            <div class="checkout-main-col">
                                <div class="checkout-card scroll-revealed">
                                    <div class="checkout-card-header">
                                        <h3 class="checkout-card-title"><i class="ti ti-building"></i> معلومات الشركة
                                            والمنشأة</h3>
                                    </div>
                                    <div class="checkout-card-body">
                                        <div class="form-grid-2">@include('web.partials.registration-fields')</div>
                                    </div>
                                </div>
                            </div>
                            <aside class="checkout-summary-col">
                                @include(
                                    'web.partials.checkout-summary',
                                    array_merge($trialSummary, [
                                        'submitId' => 'submit-customer-details-btn',
                                        'submitLabel' => 'التالي',
                                    ]))
                            </aside>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </main>
@endsection
