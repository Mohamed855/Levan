@extends('site.layouts.main')

@section('title', 'إتمام الدفع | لوت LOT')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    <main class="main">
        <section class="site-page-hero">
            <div class="container">
                <div class="scroll-revealed mx-auto text-center">
                    <span class="hero-eyebrow">
                        <i class="ti ti-receipt-2"></i>
                        إتمام الاشتراك
                    </span>
                    <h1 class="site-page-hero-title">{{ $plan->name }}</h1>
                    <p class="site-page-hero-desc mb-3">{{ $plan->description }}</p>

                    <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill bg-white/10 my-2">
                        <span class="text-2xl font-bold text-white">{{ number_format($plan->price_after_discount) }}</span>
                        <span class="icon-saudi_riyal"></span>
                        <span class="text-sm text-white/80">| دفعة واحدة</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="checkout-section">
            <div class="container">
                <div class="checkout-grid checkout-grid--full">
                    <div class="checkout-main-col">
                        <div class="checkout-card scroll-revealed moyasar-checkout-card">
                            <div class="checkout-card-header">
                                <h3 class="checkout-card-title mt-3"><i class="ti ti-lock"></i> الدفع الآمن</h3>
                                <div class="checkout-card-left">
                                    <a href="{{ route('site.plans.checkout', $plan) }}" class="checkout-back-link">
                                        <i class="ti ti-arrow-right"></i>
                                        العودة إلى الطلب
                                    </a>
                                    <span class="checkout-payment-provider"><i class="ti ti-credit-card"></i> Moyasar</span>
                                </div>
                            </div>
                            <div class="checkout-card-body">
                                <div class="site-alert site-alert-info d-flex align-items-center mb-4" role="status">
                                    <i class="ti ti-clock-hour-4"></i>
                                    <span>برجاء إتمام الدفع خلال 60 دقيقة كحد أقصى حتى لا يتم فقدان بيانات الاشتراك و العودة
                                        من جديد لإدخالها مرة أخرى.</span>
                                </div>

                                <div class="checkout-payment-total">
                                    <span>إجمالي الفاتورة</span>
                                    <strong>{{ number_format($bill->amount, 2) }} <span
                                            class="icon-saudi_riyal"></span></strong>
                                </div>

                                <x-payment.moyasar-form :amount-in-halala="$amountInHalala" :callback-url="$callbackUrl" :description="$description"
                                    :publishable-key="$publishableKey" />

                                @if ($mode === 'test')
                                    <p class="moyasar-test-notice">أنت تستخدم بيئة الاختبار؛ استخدم بيانات البطاقة التجريبية
                                        المعتمدة من Moyasar فقط.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
