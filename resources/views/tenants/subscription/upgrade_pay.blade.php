{{-- @extends('tenants.layouts.layoutMaster')
@section('title', 'دفع ترقية الباقة')



@section('vendor-style')
    @vite(['resources/assets/vendor/scss/pages/front-page-payment.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection


@section('content')
    <section class="section-py bg-body">
        <div class="container">
            <div class="card px-3">
                <form id="upgrade-payment-form" method="POST" action="{{ tenant_route_name('subscriptions.processUpgrade') }}">
                    @csrf
                    <input type="hidden" name="amount" value="{{ $total }}">
                    <input type="hidden" name="currency" value="SAR">
                    <input type="hidden" name="cardType" value="creditcard">
                    <input type="hidden" name="subscription_id" value="{{ $subscription_id }}">
                    <input type="hidden" name="upgrade" value="1">
                    <input type="hidden" id="exp_month_hidden" name="exp_month">
                    <input type="hidden" id="exp_year_hidden" name="exp_year">
                    <input type="hidden" name="added_users" value="{{ $added_users }}">

                    <div class="row">
                        <div class="col-lg-7 card-body border-end p-md-8">
                            <h4 class="mb-2">بيانات الدفع</h4>
                            <p class="text-muted mb-6">الرجاء إدخال بيانات البطاقة لإتمام عملية الترقية.</p>

                            <div id="form-credit-card">
                                <div class="row g-6">
                                    <div class="col-12">
                                        <label class="form-label" for="card_number">
                                            <i class="ti ti-credit-card me-1"></i>
                                            رقم البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-merge">
                                            <input type="text" id="card_number" name="card_number"
                                                value="4111111111111111" class="form-control"
                                                placeholder="4111 1111 1111 1111" required maxlength="16" />

                                            <span class="input-group-text cursor-pointer p-1" id="paymentCard">
                                                <span class="card-type"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="card_name_display">
                                            <i class="ti ti-writing me-1"></i>
                                            اسم حامل البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" placeholder="TEST USER"
                                            style="text-transform: uppercase;" required />
                                        <small class="text-muted">الاسم كما هو مكتوب على البطاقة</small>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="exp_date_display">
                                            <i class="ti ti-calendar me-1"></i>
                                            تاريخ الانتهاء
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="exp_date_display" id="exp_date_display"
                                            class="form-control" placeholder="MM/YY" required />
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="cvc">
                                            <i class="ti ti-lock me-1"></i>
                                            CVV
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="cvc" value="123" name="cvc"
                                            class="form-control" maxlength="3" placeholder="123" required />
                                        <small class="text-muted">الرقم خلف البطاقة</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 card-body p-md-8">


                            <!-- ملخص الدفع -->
                            <div class="payment-summary">
                                <h5 class="mb-3">ملخص الدفع</h5>

                                <div class="summary-item">
                                    <span>تكلفة المستخدمين الجدد:</span>
                                    <span>{{ number_format($subtotal, 2) }} ريال</span>
                                </div>

                                <div class="summary-item">
                                    <span>الضريبة (15%):</span>
                                    <span>{{ number_format($vat, 2) }} ريال</span>
                                </div>

                                <div class="summary-item total">
                                    <span>المبلغ الإجمالي:</span>
                                    <span class="text-primary h5 mb-0">{{ number_format($total, 2) }} ريال</span>
                                </div>
                            </div>

                            <div class="d-grid mt-6">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <span class="me-2">إتمام الدفع</span>
                                    <i class="ti ti-arrow-left scaleX-n1-rtl"></i>
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="ti ti-lock me-1"></i>
                                    عملية الدفع آمنة ومشفرة
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // معالجة تاريخ الانتهاء
            const expDateInput = document.getElementById('exp_date_display');
            const expMonthHidden = document.getElementById('exp_month_hidden');
            const expYearHidden = document.getElementById('exp_year_hidden');

            expDateInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');

                if (value.length >= 2) {
                    expMonthHidden.value = value.substring(0, 2);
                    expYearHidden.value = '20' + value.substring(2, 4);

                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                }

                e.target.value = value;
            });

            // معالجة رقم البطاقة
            const cardNumberInput = document.getElementById('card_number');
            const cardTypeSpan = document.querySelector('.card-type');

            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                let formattedValue = '';

                // إضافة مسافات كل 4 أرقام
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }

                e.target.value = formattedValue;

                // تحديد نوع البطاقة
                if (value.startsWith('4')) {
                    cardTypeSpan.innerHTML = '<i class="ti ti-brand-visa"></i>';
                } else if (value.startsWith('5')) {
                    cardTypeSpan.innerHTML = '<i class="ti ti-brand-mastercard"></i>';
                } else if (value.startsWith('3')) {
                    cardTypeSpan.innerHTML = '<i class="ti ti-brand-amex"></i>';
                } else {
                    cardTypeSpan.innerHTML = '';
                }
            });

            // معالجة حقل CVV
            const cvcInput = document.getElementById('cvc');
            cvcInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            // معالجة اسم حامل البطاقة
            const cardNameInput = document.querySelector('input[placeholder="TEST USER"]');
            cardNameInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
            });
        });
    </script>
@endsection --}}
