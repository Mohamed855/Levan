@extends('layouts.web')

@section('title', 'لوت LOT - إتمام الطلب')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.paymentData = {
            total: {{ $plan->price_after_discount }},
            appliedCoupon: @json($appliedCoupon ?? null),
            finalTotal: {{ $finalTotal ?? $plan->price_after_discount }},
        };
    </script>
    @vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/js/shared/form-validation.js', 'resources/assets/js/web/checkout.js', 'resources/assets/js/shared/payment/apply-coupon.js'])
@endsection

@section('content')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @foreach ($errors->all() as $error)
                    toastr.error(@json($error));
                @endforeach
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                toastr.error(@json(session('error')));
            });
        </script>
    @endif

    <main class="site-main">
        <!-- Sub Hero -->
        <section class="site-page-hero">
            <div class="container">
                <div class="scroll-revealed mx-auto text-center">
                    <span class="hero-eyebrow">
                        <i class="ti ti-receipt-2"></i>
                        إتمام الاشتراك
                    </span>
                    <h1 class="site-page-hero-title">
                        {{ $plan->name }}
                    </h1>

                    <p class="site-page-hero-desc mb-3">
                        {{ $plan->description }}
                    </p>

                    <div class="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill bg-white/10 my-2">
                        <span class="text-2xl font-bold text-white">{{ number_format($plan->price_after_discount) }}</span>
                        <span class="icon-saudi_riyal"></span>
                        <span class="text-sm text-white/80">| دفعة واحدة</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Checkout Main Content -->
        <section class="checkout-section">
            <div class="container">
                @if ($otpStep)
                    <div class="checkout-grid">
                        <div class="checkout-main-col">
                            <div class="checkout-card scroll-revealed otp-card">
                                <div class="checkout-card-header">
                                    <h3 class="checkout-card-title"><i class="ti ti-mail-check"></i> تأكيد البريد الإلكتروني
                                    </h3>
                                </div>
                                <div class="checkout-card-body">
                                    <div class="otp-intro">
                                        <div class="otp-intro-icon"><i class="ti ti-shield-check"></i></div>
                                        <div class="otp-intro-content flex-grow-1">
                                            <p class="mb-1">تم إرسال رمز التحقق إلى
                                                <strong
                                                    class="text-break">{{ $checkoutData['billing_email'] ?? '' }}</strong>.
                                            </p>
                                            <span class="otp-intro-hint">أدخل الرمز المكون من 6 أرقام خلال دقيقتين
                                                للمتابعة.</span>
                                        </div>
                                    </div>
                                    <form id="otp-form" method="POST"
                                        action="{{ route('site.plans.checkout.verify.store', $plan) }}" data-form-validate
                                        novalidate>
                                        @csrf
                                        <x-form.controls.input name="otp" label="رمز التحقق" inputmode="numeric"
                                            placeholder="000000" maxlength="6" autocomplete="one-time-code"
                                            class="otp-input" required />

                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3"
                                            id="otp-timer-actions">
                                            <div id="checkout-otp-timer" class="otp-timer fw-bold"
                                                data-expires-at="{{ $verification['expires_at'] ?? 0 }}"
                                                data-resend-count="{{ $resendCount ?? 0 }}"
                                                data-max-resends="{{ $maxResends ?? 3 }}"
                                                data-resend-url="{{ route('site.plans.checkout.resend', $plan) }}"></div>

                                            <div id="otp-resend-container">
                                                <button type="button" class="otp-action-btn" id="btn-resend-otp" disabled>
                                                    <i class="ti ti-rotate-clockwise"></i>
                                                    <span>إعادة إرسال الرمز</span>
                                                    <span class="text-xs text-muted"
                                                        id="otp-remaining-attempts">({{ max(0, ($maxResends ?? 3) - ($resendCount ?? 0)) }} محاولة متبقية)</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <form id="otp-resend-form" method="POST"
                                        action="{{ route('site.plans.checkout.resend', $plan) }}" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </div>
                        </div>
                        <aside class="checkout-summary-col">
                            @include('web.partials.checkout-summary', [
                                'showSubmitButton' => true,
                                'formId' => 'otp-form',
                                'submitId' => 'submit-otp-btn',
                                'submitLabel' => 'تأكيد والمتابعة',
                                'showEditLink' => true,
                            ])
                            @if (false)
                                <div class="checkout-card scroll-revealed">
                                    <div class="checkout-card-header">
                                        <h3 class="checkout-card-title"><i class="ti ti-receipt-2"></i> ملخص الطلب</h3>
                                    </div>
                                    <div class="checkout-card-body">
                                        <div class="summary-plan-badge">
                                            <span class="plan-name">{{ $plan->name }}</span>
                                            <span class="plan-price">{{ number_format($plan->price_after_discount, 2) }}
                                                <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span></span>
                                        </div>
                                        <div class="coupon-box">
                                            <div class="coupon-input-group">
                                                <input type="text" class="site-input" placeholder="كود الخصم" disabled>
                                                <button type="button" class="btn-apply-coupon" disabled>
                                                    <i class="ti ti-discount-2"></i> تطبيق
                                                </button>
                                            </div>
                                        </div>
                                        <div class="summary-details">
                                            <div class="summary-row">
                                                <span class="row-label">المبلغ قبل الضريبة</span>
                                                <span class="row-value">{{ number_format($priceWithoutTax, 2) }}
                                                    <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span></span>
                                            </div>
                                            <div class="summary-row">
                                                <span class="row-label">ضريبة القيمة المضافة (15%)</span>
                                                <span class="row-value">{{ number_format($taxAmount, 2) }}
                                                    <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span></span>
                                            </div>
                                            <div class="summary-row summary-row--total">
                                                <span class="row-label">المبلغ الإجمالي</span>
                                                <span class="row-value">{{ number_format($plan->price_after_discount, 2) }}
                                                    <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span></span>
                                            </div>
                                        </div>
                                        <div class="security-trust-box mt-4">
                                            <div class="trust-item"><i class="ti ti-lock"></i><span>بياناتك محمية
                                                    وآمنة</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </aside>
                    </div>
                @else
                    <form id="payment-form" method="POST" data-checkout-step="{{ $paymentStep ? 'payment' : 'details' }}"
                        action="{{ $paymentStep ? route('site.plans.checkout.payment', $plan->id) : route('site.plans.checkout.store', $plan->id) }}"
                        autocomplete="off" enctype="multipart/form-data"
                        @if (!$paymentStep) data-form-validate data-validation-url="{{ route('site.form-validation') }}" @endif
                        novalidate>
                        @csrf
                        <input type="hidden" name="coupon_code" id="coupon_code_hidden"
                            value="{{ old('coupon_code', $appliedCoupon['coupon_code'] ?? ($checkoutData['coupon_code'] ?? '')) }}"
                            data-validation-skip>
                        <input type="hidden" name="bank_id" id="bank_id" data-validation-skip>

                        <div class="checkout-grid">
                            <!-- Main Forms Column -->
                            <div class="checkout-main-col">

                                @if (!$paymentStep)
                                    <!-- 1. Company Information Card -->
                                    <div class="checkout-card scroll-revealed">
                                        <div class="checkout-card-header">
                                            <h3 class="checkout-card-title">
                                                <i class="ti ti-building"></i>
                                                معلومات الشركة والمنشأة
                                            </h3>
                                        </div>
                                        <div class="checkout-card-body">
                                            <div class="form-grid-2">
                                                @include('web.partials.registration-fields')
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="checkout-card scroll-revealed">
                                        <div
                                            class="checkout-card-header d-flex align-items-center justify-content-between">
                                            <h3 class="checkout-card-title">
                                                <i class="ti ti-wallet"></i>
                                                طريقة الدفع
                                            </h3>
                                        </div>
                                        <div class="checkout-card-body">
                                            <div class="payment-options-grid">
                                                <!-- Credit Card Option -->
                                                <label class="payment-option" data-method="card"
                                                    for="payment_method_card">
                                                    <input type="radio" name="payment_method" id="payment_method_card"
                                                        value="card" class="d-none" data-validation-skip>
                                                    <div class="payment-icon-wrap">
                                                        <i class="ti ti-credit-card"></i>
                                                    </div>
                                                    <div class="payment-option-text">
                                                        <h4 class="payment-label">بطاقة ائتمانية / مدى</h4>
                                                        <p class="payment-sub">Mada · Visa · Mastercard</p>
                                                    </div>
                                                    <div class="payment-check">
                                                        <div class="payment-dot"></div>
                                                    </div>
                                                </label>

                                                <label class="payment-option" data-method="bank_transfer"
                                                    for="payment_method_bank_transfer">
                                                    <input type="radio" name="payment_method"
                                                        id="payment_method_bank_transfer" value="bank_transfer"
                                                        class="d-none" data-validation-skip>
                                                    <div class="payment-icon-wrap">
                                                        <i class="ti ti-building-bank"></i>
                                                    </div>
                                                    <div class="payment-option-text">
                                                        <h4 class="payment-label">تحويل بنكي مباشر</h4>
                                                        <p class="payment-sub">إيداع مباشر عبر الحساب البنكي</p>
                                                    </div>
                                                    <div class="payment-check">
                                                        <div class="payment-dot"></div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="checkout-card hidden {{ $paymentStep ? '' : 'd-none' }}"
                                        id="card-payment-section">
                                        <div class="checkout-card-header">
                                            <h3 class="checkout-card-title">
                                                <i class="ti ti-shield-lock"></i>
                                                بوابة الدفع الآمن
                                            </h3>
                                        </div>
                                        <div class="checkout-card-body">
                                            <div class="card-security-box">
                                                <i class="ti ti-shield-check"></i>
                                                <p>
                                                    بعد الضغط على "إتمام الدفع"، سيتم نقلك مباشرة إلى بوابة الدفع الإلكتروني
                                                    المعتمدة (ميسر Moyasar) لإدخال بيانات البطاقة بأمان وتشفير بنكي كامل.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Bank Transfer Details Section -->
                                    <div class="checkout-card hidden {{ $paymentStep ? '' : 'd-none' }}"
                                        id="bank-transfer-section">
                                        <div class="checkout-card-header">
                                            <h3 class="checkout-card-title">
                                                <i class="ti ti-building-bank"></i>
                                                تفاصيل الحسابات البنكية المعتمدة
                                            </h3>
                                        </div>
                                        <div class="checkout-card-body">
                                            @if ($banks->isEmpty())
                                                <div class="site-alert site-alert-warning d-flex align-items-center gap-2"
                                                    role="alert">
                                                    <i class="ti ti-alert-triangle"></i>
                                                    لا توجد حسابات بنكية معتمدة حالياً، يرجى اختيار الدفع بالبطاقة.
                                                </div>
                                            @else
                                                <div class="mb-4">
                                                    <label class="site-label mb-2">
                                                        اختر البنك للتحويل <span class="required-star">*</span>
                                                    </label>
                                                    <div class="bank-cards-grid">
                                                        @foreach ($banks as $bank)
                                                            <div class="bank-card" data-bank-id="{{ $bank->id }}">
                                                                @if ($bank->icon)
                                                                    <img src="{{ asset('storage/' . $bank->icon) }}"
                                                                        alt="{{ $bank->bank_name }}" class="bank-logo">
                                                                @else
                                                                    <div
                                                                        class="bank-logo d-flex align-items-center justify-content-center text-primary">
                                                                        <i class="ti ti-building-bank text-2xl"></i>
                                                                    </div>
                                                                @endif

                                                                <div class="bank-info">
                                                                    <h4 class="bank-name">{{ $bank->bank_name }}</h4>
                                                                    <div class="bank-details">
                                                                        <span><i class="ti ti-user"></i>
                                                                            {{ $bank->account_name }}</span>
                                                                        <span dir="ltr" class="text-end"><i
                                                                                class="ti ti-credit-card"></i>
                                                                            {{ $bank->account_number }}</span>
                                                                        @if ($bank->iban)
                                                                            <span dir="ltr" class="text-end"><i
                                                                                    class="ti ti-id"></i>
                                                                                {{ $bank->iban }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="bank-radio">
                                                                    <div class="bank-dot"></div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <!-- Receipt Upload Section -->
                                                <div class="mb-4 hidden" id="receipt-upload-section">
                                                    <x-form.controls.file name="transfer_receipt" id="transfer_receipt"
                                                        label="إيصال التحويل البنكي" required
                                                        accept="image/jpeg,image/jpg,image/png,application/pdf"
                                                        title="انقر أو اسحب إيصال التحويل هنا"
                                                        desc="صيغ JPG · PNG · PDF — أقصى حجم 10 ميجابايت" :max-size="10"
                                                        col="col-12" data-validation-skip />
                                                </div>

                                                <!-- Instructions Box -->
                                                <div class="bank-instructions-box">
                                                    <h5 class="instructions-title">
                                                        <i class="ti ti-info-circle"></i>
                                                        تعليمات إتمام التحويل
                                                    </h5>
                                                    <ul class="instructions-list">
                                                        <li>قم بتحويل المبلغ بدقة إلى الحساب البنكي المختار أعلاه.</li>
                                                        <li>أرفق صورة واضحة لإيصال التحويل أو ملف PDF الصادر من مصرفك.</li>
                                                        <li>سيتم تفعيل حسابك مباشرة بعد مراجعة العملية وتأكيد الإيداع.</li>
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                            </div>

                            <!-- Order Summary Column -->
                            <div class="checkout-summary-col">
                                @include('web.partials.checkout-summary', [
                                    'showSubmitButton' => true,
                                    'showEditLink' => $paymentStep,
                                ])
                                @if (false)
                                    <div class="checkout-card scroll-revealed">
                                        <div class="checkout-card-header">
                                            <h3 class="checkout-card-title">
                                                <i class="ti ti-receipt-2"></i>
                                                ملخص الطلب
                                            </h3>
                                        </div>
                                        <div class="checkout-card-body">
                                            <!-- Plan Display Badge -->
                                            <div class="summary-plan-badge">
                                                <span class="plan-name">{{ $plan->name }}</span>
                                                <span class="plan-price" id="total-display">
                                                    {{ number_format($plan->price_after_discount, 2) }}
                                                    <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span>
                                                </span>
                                            </div>

                                            <!-- Coupon Code Box -->
                                            <div class="coupon-box">
                                                <div class="coupon-input-group">
                                                    <input type="text" id="coupon_code_input"
                                                        name="coupon_code_display" class="site-input"
                                                        placeholder="كود الخصم">
                                                    <button type="button" id="apply-coupon-btn"
                                                        class="btn-apply-coupon">
                                                        <i class="ti ti-discount-2"></i>
                                                        تطبيق
                                                    </button>
                                                </div>
                                                <div id="coupon-message" class="mt-2 text-sm"></div>
                                            </div>

                                            <!-- Pricing Breakdown -->
                                            <div class="summary-details">
                                                <div class="summary-row">
                                                    <span class="row-label">المبلغ قبل الضريبة</span>
                                                    <span class="row-value" id="subtotal-display">
                                                        {{ number_format($priceWithoutTax, 2) }}
                                                        <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span>
                                                    </span>
                                                </div>

                                                <div class="summary-row">
                                                    <span class="row-label">ضريبة القيمة المضافة (15%)</span>
                                                    <span class="row-value" id="vat-display">
                                                        {{ number_format($taxAmount, 2) }}
                                                        <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span>
                                                    </span>
                                                </div>

                                                <!-- Discount Row -->
                                                <div class="summary-row summary-row--discount hidden" id="discount-row">
                                                    <span class="row-label">
                                                        <i class="ti ti-discount-2"></i>
                                                        الخصم
                                                        <button type="button" class="btn-remove-coupon"
                                                            id="remove-coupon" title="إزالة كود الخصم"
                                                            aria-label="إزالة كود الخصم">
                                                            <i class="ti ti-x"></i>
                                                        </button>
                                                    </span>
                                                    <span class="row-value">
                                                        -<span id="discount-display">0.00</span>
                                                        <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span>
                                                    </span>
                                                </div>

                                                <!-- Final Total -->
                                                <div class="summary-row summary-row--total">
                                                    <span class="row-label">المبلغ الإجمالي</span>
                                                    <span class="row-value" id="total-final">
                                                        {{ number_format($plan->price_after_discount, 2) }}
                                                        <span class="icon-saudi_riyal icon-saudi_riyal--dark"></span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Submit Action Button -->
                                            <button type="submit"
                                                id="{{ $paymentStep ? 'submit-payment-btn' : 'submit-customer-details-btn' }}"
                                                class="site-btn-submit w-100 justify-content-center">
                                                <span class="spinner-border hidden" role="status"
                                                    aria-hidden="true"></span>
                                                <span
                                                    class="btn-text">{{ $paymentStep ? 'متابعة إلى الدفع' : 'التالي' }}</span>
                                                <i class="ti ti-arrow-left"></i>
                                            </button>

                                            <!-- Trust & Security Info -->
                                            <div class="security-trust-box">
                                                <div class="trust-item">
                                                    <i class="ti ti-lock"></i>
                                                    <span>حماية وأمان كامل للبيانات والخصوصية</span>
                                                </div>
                                                <div class="trust-item">
                                                    <i class="ti ti-shield-check"></i>
                                                    <span>معالجة مصرفية موثوقة ومعتمدة</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </section>

        <!-- Modal for Receipt Preview -->
        <div class="image-modal" id="image-modal">
            <button type="button" class="modal-close" id="modal-close">&times;</button>
            <img id="modal-image" src="" alt="معاينة إيصال التحويل">
        </div>
    </main>

@endsection
