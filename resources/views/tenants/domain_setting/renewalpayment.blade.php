@extends('tenants.layouts.layoutMaster')
@section('title', 'تجديد الدومين')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/scss/pages/front-page-payment.scss'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('content')
    <section class="section-py bg-body">
        @if (session()->has('success'))
            <div class="container mb-4">
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="container mb-4">
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="container">
            <div class="card px-3">
                <form id="payment-form" method="POST" action="{{ tenant_route_name('domains.moyasar.renewal') }}">
                    @csrf
                    <input type="hidden" name="domain_name" value="{{ old('domain_name', $domain_name) }}">
                    <input type="hidden" name="domain_id" value="{{ old('domain_id', $domain_id) }}">
                    <input type="hidden" name="amount" id="final_amount" value="{{ old('amount', $price) }}">
                    <input type="hidden" name="years" id="selected_years" value="{{ old('years', 1) }}">
                    <input type="hidden" name="cardType" id="card_type_hidden" value="{{ old('cardType') }}">

                    <div class="row">
                        <!-- بيانات البطاقة الائتمانية -->
                        <div class="col-lg-7 card-body border-end p-md-8">
                            <!-- اختيار عدد السنوات -->
                            <div class="mb-6">
                                <h4 class="mb-2">
                                    <i class="ti ti-calendar-time me-1"></i>
                                    مدة التجديد
                                </h4>
                                <p class="text-muted mb-4">اختر عدد السنوات التي تريد تجديد الدومين لها</p>

                                <div class="row g-4">
                                    @for ($year = 1; $year <= 5; $year++)
                                        <div class="col-md-4 col-6">
                                            <div class="card years-option {{ old('years', 1) == $year ? 'active' : '' }}"
                                                data-years="{{ $year }}" data-base-price="{{ $price }}"
                                                data-base-price-without-tax="{{ $priceWithoutTax }}"
                                                data-base-tax="{{ $taxPrice }}"
                                                style="border: 2px solid #e0e0e0; cursor: pointer;">
                                                <div class="card-body text-center py-3">
                                                    <h5 class="mb-1">{{ $year }}
                                                        {{ $year == 1 ? 'سنة' : 'سنوات' }}</h5>
                                                    <p class="mb-0 text-muted small">
                                                        {{ number_format($price * $year, 2) }} ر.س
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <hr class="my-6">

                            <!-- معلومات البطاقة -->
                            <div class="mb-6">
                                <h4 class="mb-2">معلومات البطاقة الائتمانية</h4>
                                <p class="text-muted mb-6">جميع البيانات يتم تشفيرها وفق أعلى معايير الأمان.</p>

                                <div class="row g-4">
                                    <!-- رقم البطاقة -->
                                    <div class="col-12">
                                        <label class="form-label" for="billings-card-num">
                                            <i class="ti ti-credit-card me-1"></i>
                                            رقم البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" id="billings-card-num" name="card_number"
                                                class="form-control billing-card-mask text-start @error('card_number') is-invalid @enderror"
                                                value="{{ old('card_number') }}" placeholder="7465 8374 5837 5067"
                                                dir="ltr" inputmode="numeric" autocomplete="cc-number" maxlength="19"
                                                required />
                                            <span class="input-group-text cursor-pointer p-2" id="paymentCard">
                                                <span class="card-type-display"></span>
                                            </span>
                                        </div>
                                        @error('card_number')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted d-block mt-1">أدخل رقم البطاقة كما هو ظاهر على البطاقة
                                            الائتمانية.</small>
                                    </div>

                                    <!-- اسم حامل البطاقة -->
                                    <div class="col-md-12">
                                        <label class="form-label" for="card_name_display">
                                            <i class="ti ti-user me-1"></i>
                                            اسم حامل البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="card_name_display" name="card_name_display"
                                            value="{{ old('card_name_display') }}"
                                            class="form-control text-uppercase @error('card_name_display') is-invalid @enderror"
                                            placeholder="ALI AHMED" autocomplete="cc-name" required />
                                        @error('card_name_display')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">الاسم كما هو مكتوب على البطاقة.</small>
                                    </div>

                                    <!-- تاريخ الانتهاء -->
                                    <div class="col-md-6">
                                        <label class="form-label" for="exp_date">
                                            <i class="ti ti-calendar me-1"></i>
                                            تاريخ الانتهاء
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="exp_date_display" id="exp_date"
                                            value="{{ old('exp_date_display') }}"
                                            class="form-control text-start @error('exp_date_display') is-invalid @enderror"
                                            inputmode="numeric" autocomplete="cc-exp" maxlength="5" minlength="5"
                                            placeholder="MM/YY" pattern="\d{2}/\d{2}" required />
                                        @error('exp_date_display')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">مثال: 08/27</small>
                                    </div>

                                    <!-- رمز التحقق CVC -->
                                    <div class="col-md-6">
                                        <label class="form-label" for="cvv">
                                            <i class="ti ti-lock me-1"></i>
                                            رمز الأمان (CVV)
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="cvv" name="cvc" value="{{ old('cvc') }}"
                                            class="form-control text-start @error('cvc') is-invalid @enderror"
                                            inputmode="numeric" autocomplete="cc-csc" maxlength="3" minlength="3"
                                            placeholder="123" pattern="\d{3,4}" required />
                                        @error('cvc')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">الرقم المكون من 3 أو 4 أرقام خلف البطاقة.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ملخص الطلب -->
                        <div class="col-lg-5 card-body p-md-8">
                            <div class="bg-lighter p-6 rounded text-center mb-6">
                                <h3 class="mb-0 text-primary" id="total_price_display">
                                    {{ number_format($price, 2) }}
                                </h3>
                                <span class="text-muted">ر.س</span>
                            </div>

                            <!-- تفاصيل الطلب -->
                            <div class="mt-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 text-body">الدومين:</p>
                                    <h6 class="mb-0 text-primary">{{ old('domain_name', $domain_name) }}</h6>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 text-body">مدة التجديد:</p>
                                    <h6 class="mb-0" id="years_display">سنة واحدة</h6>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 text-body">السعر الأساسي:</p>
                                    <h6 class="mb-0" id="price_without_tax_display">
                                        {{ number_format($priceWithoutTax, 2) }} ر.س</h6>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <p class="mb-0 text-body">الضريبة (15%):</p>
                                    <h6 class="mb-0" id="tax_display">{{ number_format($taxPrice, 2) }} ر.س</h6>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0 fw-bold">المبلغ الإجمالي شامل الضريبة</h5>
                                    <h5 class="mb-0 fw-bold text-primary" id="final_price_display">
                                        {{ number_format($price, 2) }}
                                        <span class="text-muted">ر.س</span>
                                    </h5>
                                </div>

                                <div class="d-grid mt-6">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <span class="me-2">إتمام الدفع</span>
                                        <i class="ti ti-arrow-left scaleX-n1-rtl"></i>
                                    </button>
                                </div>

                                <p class="mt-6 text-center text-muted small">
                                    <i class="ti ti-info-circle me-1"></i>
                                    بالمتابعة، أنت توافق على
                                    <a href="#" class="text-primary">شروط الخدمة</a> و
                                    <a href="#" class="text-primary">سياسة الخصوصية</a>.
                                    <br>
                                    يرجى ملاحظة أن المدفوعات غير قابلة للاسترداد.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumberInput = document.getElementById('billings-card-num');
            const expDateInput = document.getElementById('exp_date');
            const cvvInput = document.getElementById('cvv');
            const form = document.getElementById('payment-form');
            const cardTypeElement = document.querySelector('.card-type-display');
            const cardTypeHidden = document.getElementById('card_type_hidden');
            const yearsOptions = document.querySelectorAll('.years-option');

            // دالة تنسيق الأرقام
            function formatNumber(num) {
                return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            // دالة تحديث الأسعار
            function updatePrices(years, basePrice, basePriceWithoutTax, baseTax) {
                const newPriceWithoutTax = basePriceWithoutTax * years;
                const newTax = baseTax * years;
                const newTotalPrice = basePrice * years;

                // تحديث القيم المخفية
                document.getElementById('selected_years').value = years;
                document.getElementById('final_amount').value = newTotalPrice.toFixed(2);

                // تحديث العرض
                document.getElementById('years_display').textContent = years === 1 ? 'سنة واحدة' : years + ' سنوات';
                document.getElementById('price_without_tax_display').textContent = formatNumber(
                    newPriceWithoutTax) + ' ر.س';
                document.getElementById('tax_display').textContent = formatNumber(newTax) + ' ر.س';
                document.getElementById('final_price_display').innerHTML = formatNumber(newTotalPrice) +
                    ' <span class="text-muted">ر.س</span>';
                document.getElementById('total_price_display').textContent = formatNumber(newTotalPrice);
            }

            // استعادة حالة عدد السنوات عند تحميل الصفحة (في حالة حدوث validation error)
            function restoreYearsState() {
                const savedYears = parseInt(document.getElementById('selected_years').value) || 1;
                const activeOption = document.querySelector(`.years-option[data-years="${savedYears}"]`);

                if (activeOption && savedYears > 1) {
                    // إزالة التحديد من جميع الخيارات
                    yearsOptions.forEach(opt => opt.classList.remove('active'));

                    // تحديد الخيار المحفوظ
                    activeOption.classList.add('active');

                    // تحديث الأسعار بناءً على القيمة المحفوظة
                    const basePrice = parseFloat(activeOption.dataset.basePrice);
                    const basePriceWithoutTax = parseFloat(activeOption.dataset.basePriceWithoutTax);
                    const baseTax = parseFloat(activeOption.dataset.baseTax);

                    updatePrices(savedYears, basePrice, basePriceWithoutTax, baseTax);
                }
            }

            // معالجة اختيار عدد السنوات
            yearsOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // إزالة التحديد من جميع الخيارات
                    yearsOptions.forEach(opt => opt.classList.remove('active'));

                    // تحديد الخيار المختار
                    this.classList.add('active');

                    const years = parseInt(this.dataset.years);
                    const basePrice = parseFloat(this.dataset.basePrice);
                    const basePriceWithoutTax = parseFloat(this.dataset.basePriceWithoutTax);
                    const baseTax = parseFloat(this.dataset.baseTax);

                    updatePrices(years, basePrice, basePriceWithoutTax, baseTax);
                });
            });

            // دالة تحديد نوع البطاقة
            function getCardType(number) {
                const cleanNumber = number.replace(/\s/g, '');

                // مدى (Mada) - البطاقات السعودية
                if (/^(4(0(0861|1757|7(197|395)|9201)|1(0685|7633|9593)|2(281(7|8|9)|8(331|67(1|2|3)))|3(1361|2328|4107|9954)|4(0(533|647|795)|5564|6(393|404|672))|5(5(036|708)|7865|9(182|751))|6(2220|854(0|1|2|3))|8(301(0|1|2)|4783|609(4|5|6)|931(7|8|9))|93428)|(588845|636120|588848|588850|968203|968204|968205|968206|968209))/
                    .test(cleanNumber)) {
                    return 'mada';
                }

                // Visa
                if (/^4/.test(cleanNumber)) {
                    return 'creditcard';
                }

                // Mastercard
                if (/^5[1-5]/.test(cleanNumber) || /^2(2[2-9][0-9]|[3-6][0-9]{2}|7[0-1][0-9]|720)/.test(
                    cleanNumber)) {
                    return 'mastercard';
                }

                // American Express
                if (/^3[47]/.test(cleanNumber)) {
                    return 'amex';
                }

                // Discover
                if (/^6(?:011|5|4[4-9]|22(1(2[6-9]|[3-9])|[2-8]|9([01]|2[0-5])))/.test(cleanNumber)) {
                    return 'discover';
                }

                return '';
            }

            // استعادة نوع البطاقة عند تحميل الصفحة
            function restoreCardType() {
                if (cardNumberInput.value) {
                    const cardType = getCardType(cardNumberInput.value);
                    updateCardTypeDisplay(cardType);
                }
            }

            // تحديث عرض نوع البطاقة
            function updateCardTypeDisplay(cardType) {
                cardTypeElement.className = 'card-type-display';

                if (cardType === 'creditcard') {
                    cardTypeElement.textContent = 'VISA';
                    cardTypeElement.classList.add('visa');
                    cardTypeHidden.value = 'creditcard';
                    cvvInput.maxLength = 3;
                    cvvInput.minLength = 3;
                    cvvInput.placeholder = '123';
                } else if (cardType === 'mastercard') {
                    cardTypeElement.textContent = 'Mastercard';
                    cardTypeElement.classList.add('mastercard');
                    cardTypeHidden.value = 'mastercard';
                    cvvInput.maxLength = 3;
                    cvvInput.minLength = 3;
                    cvvInput.placeholder = '123';
                } else if (cardType === 'amex') {
                    cardTypeElement.textContent = 'AMEX';
                    cardTypeElement.classList.add('amex');
                    cardTypeHidden.value = 'amex';
                    cvvInput.maxLength = 4;
                    cvvInput.minLength = 4;
                    cvvInput.placeholder = '1234';
                } else if (cardType === 'discover') {
                    cardTypeElement.textContent = 'Discover';
                    cardTypeElement.classList.add('discover');
                    cardTypeHidden.value = 'discover';
                    cvvInput.maxLength = 3;
                    cvvInput.minLength = 3;
                    cvvInput.placeholder = '123';
                } else if (cardType === 'mada') {
                    cardTypeElement.textContent = 'مدى';
                    cardTypeElement.classList.add('mada');
                    cardTypeHidden.value = 'mada';
                    cvvInput.maxLength = 3;
                    cvvInput.minLength = 3;
                    cvvInput.placeholder = '123';
                } else {
                    cardTypeElement.textContent = '';
                    cardTypeHidden.value = '';
                    cvvInput.maxLength = 3;
                    cvvInput.minLength = 3;
                    cvvInput.placeholder = '123';
                }
            }

            // تحديث نوع البطاقة عند الإدخال
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');

                // تنسيق الرقم
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;

                // تحديد نوع البطاقة
                const cardType = getCardType(value);
                updateCardTypeDisplay(cardType);
            });

            // تنسيق تاريخ الانتهاء
            expDateInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            });

            // السماح بالأرقام فقط في CVV
            cvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            // تنظيف رقم البطاقة قبل الإرسال
            form.addEventListener('submit', function(e) {
                const cleanCardNumber = cardNumberInput.value.replace(/\s/g, '');

                // التحقق من صحة البيانات
                if (cleanCardNumber.length < 13) {
                    e.preventDefault();
                    alert('رقم البطاقة غير صحيح');
                    return false;
                }

                if (cvvInput.value.length < 3) {
                    e.preventDefault();
                    alert('رمز CVV يجب أن يكون 3 أو 4 أرقام');
                    return false;
                }

                // تنظيف رقم البطاقة
                cardNumberInput.value = cleanCardNumber;
            });

            // استعادة جميع الحالات عند تحميل الصفحة
            restoreYearsState();
            restoreCardType();
        });
    </script>
@endsection
