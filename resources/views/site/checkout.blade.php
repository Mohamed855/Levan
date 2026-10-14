@extends('site.layouts.main')

@section('title', 'لوت LOT - إتمام الطلب')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.paymentData = {
            total: {{ $plan->price_after_discount }},
        };
    </script>
    @vite([
        'resources/assets/js/shared/form-validation.js',
        'resources/assets/js/site/checkout.js',
        'resources/assets/js/shared/payment/apply-coupon.js'
    ])
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
            <form id="payment-form" method="POST" action="{{ route('site.plans.checkout.store', $plan->id) }}"
                autocomplete="off" enctype="multipart/form-data" data-form-validate
                data-validation-url="{{ route('site.form-validation') }}" novalidate>
                @csrf
                <input type="hidden" name="coupon_code" id="coupon_code_hidden">
                <input type="hidden" name="bank_id" id="bank_id">

                <div class="checkout-grid">
                    <!-- Main Forms Column -->
                    <div class="checkout-main-col">

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
                                    <!-- Company Name -->
                                    <div class="col-span-2" data-form-field data-field-name="billing_company_name">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_company_name">
                                                اسم الشركة أو المؤسسة <span class="required-star">*</span>
                                            </label>
                                            <input type="text" id="billing_company_name" name="billing_company_name"
                                                class="site-input" value="{{ old('billing_company_name') }}"
                                                placeholder="مثال: شركة لوت للمزادات العقارية" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Commercial Number -->
                                    <div data-form-field data-field-name="billing_commercial_number">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_commercial_number">
                                                رقم السجل التجاري <span class="required-star">*</span>
                                            </label>
                                            <input type="text" id="billing_commercial_number" name="billing_commercial_number"
                                                class="site-input" value="{{ old('billing_commercial_number') }}"
                                                placeholder="1010XXXXXX" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Tax Number -->
                                    <div data-form-field data-field-name="billing_tax_number">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_tax_number">
                                                الرقم الضريبي (اختياري)
                                            </label>
                                            <input type="text" id="billing_tax_number" name="billing_tax_number"
                                                class="site-input" value="{{ old('billing_tax_number') }}"
                                                placeholder="300XXXXXXXXXXXX" />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- City -->
                                    <div data-form-field data-field-name="billing_city_id">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_city_id">
                                                المدينة
                                            </label>
                                            <select id="billing_city_id" name="billing_city_id" class="site-select">
                                                <option value="">اختر المدينة (اختياري)</option>
                                                @if (isset($cities))
                                                    @foreach ($cities as $city)
                                                        <option value="{{ $city->id }}" @selected(old('billing_city_id') == $city->id)>
                                                            {{ $city->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div data-form-field data-field-name="billing_address">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_address">
                                                العنوان أو الحي
                                            </label>
                                            <input type="text" id="billing_address" name="billing_address"
                                                class="site-input" value="{{ old('billing_address') }}"
                                                placeholder="الرياض، حي السليمانية" />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Administrator Name -->
                                    <div data-form-field data-field-name="billing_name">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_name">
                                                اسم المدير المسؤول <span class="required-star">*</span>
                                            </label>
                                            <input type="text" id="billing_name" name="billing_name"
                                                value="{{ old('billing_name') }}" class="site-input"
                                                placeholder="الاسم الثلاثي" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div data-form-field data-field-name="billing_email">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_email">
                                                البريد الإلكتروني للإدارة <span class="required-star">*</span>
                                            </label>
                                            <input type="email" id="billing_email" name="billing_email"
                                                value="{{ old('billing_email') }}" autocomplete="off"
                                                class="site-input" placeholder="admin@company.com" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-span-2" data-form-field data-field-name="billing_phone">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_phone">
                                                رقم الجوال <span class="required-star">*</span>
                                            </label>
                                            <input type="tel" id="billing_phone" name="billing_phone" data-phone-format="international"
                                                value="{{ old('billing_phone') }}" dir="ltr" class="site-input text-end"
                                                placeholder="05XXXXXXXX" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div data-form-field data-field-name="billing_password">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_password">
                                                كلمة المرور <span class="required-star">*</span>
                                            </label>
                                            <input type="password" id="billing_password" name="billing_password"
                                                autocomplete="new-password" class="site-input"
                                                placeholder="••••••••" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>

                                    <!-- Password Confirmation -->
                                    <div data-form-field data-field-name="billing_password_confirmation">
                                        <div class="site-form-group">
                                            <label class="site-label" for="billing_password_confirmation">
                                                تأكيد كلمة المرور <span class="required-star">*</span>
                                            </label>
                                            <input type="password" id="billing_password_confirmation"
                                                name="billing_password_confirmation" autocomplete="new-password"
                                                class="site-input" placeholder="••••••••" required />
                                            <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Payment Method Selector -->
                        <div class="checkout-card scroll-revealed">
                            <div class="checkout-card-header">
                                <h3 class="checkout-card-title">
                                    <i class="ti ti-wallet"></i>
                                    طريقة الدفع
                                </h3>
                            </div>
                            <div class="checkout-card-body">
                                <div class="payment-options-grid">
                                    <!-- Credit Card Option -->
                                    <label class="payment-option" data-method="card" for="payment_method_card">
                                        <input type="radio" name="payment_method" id="payment_method_card" value="card" class="d-none">
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

                                    <!-- Bank Transfer Option -->
                                    <label class="payment-option" data-method="bank_transfer" for="payment_method_bank_transfer">
                                        <input type="radio" name="payment_method" id="payment_method_bank_transfer" value="bank_transfer" class="d-none">
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

                        <!-- 3. Card Payment Notice (Moyasar) -->
                        <div class="checkout-card hidden" id="card-payment-section">
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
                                        بعد الضغط على "إتمام الدفع"، سيتم نقلك مباشرة إلى بوابة الدفع الإلكتروني المعتمدة (ميسر Moyasar) لإدخال بيانات البطاقة بأمان وتشفير بنكي كامل.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Bank Transfer Details Section -->
                        <div class="checkout-card hidden" id="bank-transfer-section">
                            <div class="checkout-card-header">
                                <h3 class="checkout-card-title">
                                    <i class="ti ti-building-bank"></i>
                                    تفاصيل الحسابات البنكية المعتمدة
                                </h3>
                            </div>
                            <div class="checkout-card-body">
                                @if ($banks->isEmpty())
                                    <div class="alert alert-warning d-flex align-items-center gap-2">
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
                                                        <div class="bank-logo d-flex align-items-center justify-content-center text-primary">
                                                            <i class="ti ti-building-bank text-2xl"></i>
                                                        </div>
                                                    @endif

                                                    <div class="bank-info">
                                                        <h4 class="bank-name">{{ $bank->bank_name }}</h4>
                                                        <div class="bank-details">
                                                            <span><i class="ti ti-user"></i> {{ $bank->account_name }}</span>
                                                            <span dir="ltr" class="text-end"><i class="ti ti-credit-card"></i> {{ $bank->account_number }}</span>
                                                            @if ($bank->iban)
                                                                <span dir="ltr" class="text-end"><i class="ti ti-id"></i> {{ $bank->iban }}</span>
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
                                        <label class="site-label mb-2">
                                            إيصال التحويل البنكي <span class="required-star">*</span>
                                        </label>

                                        <div class="upload-area" id="upload-area">
                                            <input type="file" name="transfer_receipt" id="transfer_receipt"
                                                accept="image/jpeg,image/jpg,image/png,application/pdf" class="d-none">
                                            <div class="upload-icon-wrap">
                                                <i class="ti ti-cloud-upload"></i>
                                            </div>
                                            <h4 class="upload-title">انقر أو اسحب إيصال التحويل هنا</h4>
                                            <p class="upload-desc">صيغ JPG · PNG · PDF — أقصى حجم 5 ميجابايت</p>
                                        </div>

                                        <div id="files-preview"></div>
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

                    </div>

                    <!-- Order Summary Column -->
                    <div class="checkout-summary-col">
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
                                        <span class="icon-saudi_riyal"></span>
                                    </span>
                                </div>

                                <!-- Coupon Code Box -->
                                <div class="coupon-box">
                                    <div class="coupon-input-group">
                                        <input type="text" id="coupon_code_input" name="coupon_code_display"
                                            class="site-input" placeholder="كود الخصم">
                                        <button type="button" id="apply-coupon-btn" class="btn-apply-coupon">
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
                                            <span class="icon-saudi_riyal"></span>
                                        </span>
                                    </div>

                                    <div class="summary-row">
                                        <span class="row-label">ضريبة القيمة المضافة (15%)</span>
                                        <span class="row-value" id="vat-display">
                                            {{ number_format($taxAmount, 2) }}
                                            <span class="icon-saudi_riyal"></span>
                                        </span>
                                    </div>

                                    <!-- Discount Row -->
                                    <div class="summary-row summary-row--discount hidden" id="discount-row">
                                        <span class="row-label">
                                            <i class="ti ti-discount-2"></i>
                                            الخصم
                                            <button type="button" class="btn-remove-file p-0 ms-1" id="remove-coupon" title="إلغاء الكود">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </span>
                                        <span class="row-value">
                                            -<span id="discount-display">0.00</span>
                                            <span class="icon-saudi_riyal"></span>
                                        </span>
                                    </div>

                                    <!-- Final Total -->
                                    <div class="summary-row summary-row--total">
                                        <span class="row-label">المبلغ الإجمالي</span>
                                        <span class="row-value" id="total-final">
                                            {{ number_format($plan->price_after_discount, 2) }}
                                            <span class="icon-saudi_riyal"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Submit Action Button -->
                                <button type="submit" id="submit-payment-btn" class="site-btn-submit w-100 justify-content-center">
                                    <span class="spinner-border hidden" role="status" aria-hidden="true"></span>
                                    <span class="btn-text">إتمام الدفع الآن</span>
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
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Modal for Receipt Preview -->
    <div class="image-modal" id="image-modal">
        <button type="button" class="modal-close" id="modal-close">&times;</button>
        <img id="modal-image" src="" alt="معاينة إيصال التحويل">
    </div>
</main>

@endsection
