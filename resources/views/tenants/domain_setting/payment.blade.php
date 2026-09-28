@php
$price = old('amount', $price ?? 0);
$subtotal = $price;
$vatValue = $price;
$total = $price;
@endphp

@extends('tenants.layouts.layoutMaster')
@section('title', 'إتمام الدفع - ' . $domainname)

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
    @if(session()->has('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="container mb-4">
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="container">
        <div class="card px-3">
            <form id="payment-form" method="POST" action="{{ url(tenant('id').'/domains/payment/proccess') }}">
                @csrf
                <input type="hidden" name="domainname" value="{{ old('domainname', $domainname) }}">
                <input type="hidden" name="amount" value="{{ old('amount', $price) }}">
                <input type="hidden" name="cardType" id="card_type_hidden" value="{{ old('cardType') }}">

                <div class="row">
                    <!-- بيانات البطاقة الائتمانية -->
                    <div class="col-lg-7 card-body border-end p-md-8">
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
                                        <input type="text" 
                                               id="billings-card-num" 
                                               name="card_number" 
                                               class="form-control billing-card-mask text-start @error('card_number') is-invalid @enderror" 
                                               value="{{ old('card_number') }}" 
                                               placeholder="7465 8374 5837 5067" 
                                               dir="ltr" 
                                               inputmode="numeric" 
                                               autocomplete="cc-number" 
                                               maxlength="19" 
                                               required />
                                        <span class="input-group-text cursor-pointer p-2" id="paymentCard">
                                            <span class="card-type-display"></span>
                                        </span>
                                    </div>
                                    @error('card_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">أدخل رقم البطاقة كما هو ظاهر على البطاقة الائتمانية.</small>
                                </div>

                                <!-- اسم حامل البطاقة -->
                                <div class="col-md-12">
                                    <label class="form-label" for="card_holder_name">
                                        <i class="ti ti-user me-1"></i>
                                        اسم حامل البطاقة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           id="card_holder_name" 
                                           name="card_holder_name" 
                                           value="{{ old('card_holder_name') }}" 
                                           class="form-control text-uppercase @error('card_holder_name') is-invalid @enderror" 
                                           placeholder="ALI AHMED" 
                                           autocomplete="cc-name"
                                           required />
                                    @error('card_holder_name')
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
                                    <input type="text" 
                                           name="exp_date_display" 
                                           id="exp_date" 
                                           value="{{ old('exp_date_display') }}" 
                                           class="form-control text-start @error('exp_date_display') is-invalid @enderror" 
                                           inputmode="numeric" 
                                           autocomplete="cc-exp" 
                                           maxlength="5" 
                                           minlength="5"
                                           placeholder="MM/YY" 
                                           pattern="\d{2}/\d{2}"
                                           required />
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
                                    <input type="text" 
                                           id="cvv" 
                                           name="cvc" 
                                           value="{{ old('cvc') }}"
                                           class="form-control text-start @error('cvc') is-invalid @enderror" 
                                           inputmode="numeric" 
                                           autocomplete="cc-csc" 
                                           maxlength="3" 
                                           minlength="3"
                                           placeholder="123" 
                                           pattern="\d{3,4}"
                                           required />
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
                            <h3 class="mb-0 text-primary">
                                {{ number_format($total, 2) }}
                            </h3>
                            <span class="text-muted">ر.س</span>
                        </div>

                        <!-- تفاصيل الطلب -->
                        <div class="mt-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="mb-0 text-body">الدومين:</p>
                                <h6 class="mb-0 text-primary">{{ old('domainname' , $domainname) }}</h6>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 fw-bold">المبلغ الإجمالي شامل الضريبة</h5>
                                <h5 class="mb-0 fw-bold text-primary">
                                    {{ number_format($total, 2) }}
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

        // دالة تحديد نوع البطاقة
        function getCardType(number) {
            const cleanNumber = number.replace(/\s/g, '');

            // مدى (Mada) - البطاقات السعودية
            if (/^(4(0(0861|1757|7(197|395)|9201)|1(0685|7633|9593)|2(281(7|8|9)|8(331|67(1|2|3)))|3(1361|2328|4107|9954)|4(0(533|647|795)|5564|6(393|404|672))|5(5(036|708)|7865|9(182|751))|6(2220|854(0|1|2|3))|8(301(0|1|2)|4783|609(4|5|6)|931(7|8|9))|93428)|(588845|636120|588848|588850|968203|968204|968205|968206|968209))/.test(cleanNumber)) {
                return 'mada';
            }

            // Visa
            if (/^4/.test(cleanNumber)) {
                return 'visa';
            }

            // Mastercard
            if (/^5[1-5]/.test(cleanNumber) || /^2(2[2-9][0-9]|[3-6][0-9]{2}|7[0-1][0-9]|720)/.test(cleanNumber)) {
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

            if (cardType === 'visa') {
                cardTypeElement.textContent = 'VISA';
                cardTypeElement.classList.add('visa');
                cardTypeHidden.value = 'visa';
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

        // استعادة نوع البطاقة عند تحميل الصفحة
        restoreCardType();
    });
</script>
@endsection
