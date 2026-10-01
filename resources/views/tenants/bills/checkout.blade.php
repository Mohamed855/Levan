@extends('tenants.layouts.layoutMaster')

@section('title', 'دفع الفاتورة')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/payment/validation.js', 'resources/assets/js/site/front-page-payment.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <section class="section-py bg-body">
        <div class="">
            <form id="payment-form" method="POST" action="{{ tenant_route_name('bills.checkout.store') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="coupon_code" id="coupon_code_hidden">
                <input type="hidden" name="discount_amount" id="discount_amount_hidden">

                <div class="row g-2">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-credit-card text-primary me-2"></i>
                                    معلومات الحساب
                                </h6>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body">
                                <div id="form-credit-card" class="mt-5">
                                    <div class="row g-4">
                                        <!-- رقم البطاقة -->
                                        <div class="col-12">
                                            <label class="form-label" for="card_number">
                                                <i class="ti ti-credit-card me-1"></i>
                                                رقم البطاقة
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group input-group-merge">
                                                <input type="text" id="billings-card-num" name="card_number"
                                                    class="form-control billing-card-mask text-start"
                                                    placeholder="7465 8374 5837 5067" dir="ltr" inputmode="numeric"
                                                    dir="ltr" autocomplete="cc-number" maxlength="19" required />
                                                <span class="input-group-text cursor-pointer p-1" id="paymentCard">
                                                    <span class="card-type"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- اسم حامل البطاقة -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="card_name_display">
                                                <i class="ti ti-user me-1"></i>
                                                اسم حامل البطاقة
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="card_name_display" name="card_name_display"
                                                class="form-control text-uppercase" placeholder="ALI AHMED" required />
                                        </div>

                                        <!-- تاريخ الانتهاء -->
                                        <div class="col-md-3">
                                            <label class="form-label" for="exp_date_display">
                                                <i class="ti ti-calendar me-1"></i>
                                                تاريخ الانتهاء
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="exp_date_display" id="exp_date_display"
                                                class="form-control text-start" inputmode="numeric" autocomplete="cc-exp"
                                                maxlength="5" placeholder="MM/YY" required />
                                        </div>

                                        <!-- رمز التحقق CVC -->
                                        <div class="col-md-3">
                                            <label class="form-label" for="cvc">
                                                <i class="ti ti-lock me-1"></i>
                                                رمز الأمان (CVV)
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="cvc" name="cvc"
                                                class="form-control text-start" inputmode="numeric" autocomplete="cc-csc"
                                                maxlength="4" placeholder="123" required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!---- ملخص الطلب  --->
                    <div class=" col-lg-4">
                        <div class="card">
                            <div class="card-header py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-invoice text-primary me-2"></i>
                                    ملخص عملية الدفع
                                </h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body">
                                <div class="p-3 rounded text-center mb-6 text-price">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-bold">
                                            نوع العملية
                                        </div>
                                        <h6 class="mb-0 text-primary" id="total-display">
                                            {{ $bill->type->label() }}
                                        </h6>
                                    </div>
                                </div>

                                <!-- تفاصيل الأسعار -->
                                <div class="mt-5">
                                    <hr class="my-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <p class="mb-0 text-body">
                                            الاجمالي قبل الضريبة
                                        </p>
                                        <h6 class="mb-0" id="subtotal-display">
                                            {{ number_format($priceWithoutTax, 2) }}
                                            <span class="icon-saudi_riyal mx-2"></span>
                                        </h6>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>الضريبة ({{ (int) ($vatRate * 100) }}%)</span>
                                        <h6 class="mb-0" id="vat-display">
                                            {{ number_format($vatAmount, 2) }}
                                            <span class="icon-saudi_riyal mx-2"></span>
                                        </h6>
                                    </div>

                                    <hr class="my-3">

                                    @if ($bill->settlement_amount > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-3 "
                                            id="discount-row">
                                            <p class="mb-0 text-body text-success">
                                                الخصم
                                                <button type="button" class="btn btn-sm btn-label-danger p-0 ms-2"
                                                    id="remove-coupon">
                                                </button>
                                            </p>
                                            <h6 class="mb-0 text-danger">
                                                <span class="text-danger">
                                                    {{ number_format($bill->settlement_amount, 2) }}
                                                </span>
                                                <span class="icon-saudi_riyal mx-2"></span>
                                            </h6>
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0 fw-bold">المبلغ الإجمالي</h5>
                                        <h5 class="mb-0 fw-bold text-primary" id="total-final">
                                            {{ number_format($bill->amount, 2) }}
                                            <span class="icon-saudi_riyal mx-2"></span>
                                        </h5>
                                    </div>

                                    <div class="d-grid mt-6">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <span class="me-2">إتمام الدفع</span>
                                            <i class="ti ti-arrow-left scaleX-n1-rtl"></i>
                                        </button>
                                    </div>

                                    <div class="mt-6 p-3 bg-light rounded">
                                        <p class="mb-2 text-muted small">
                                            <i class="ti ti-lock text-success me-2"></i>
                                            حماية وأمان البيانات والخصوصية
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            <i class="ti ti-shield-check text-success me-2"></i>
                                            معالجة آمنة عبر بوابات دفع موثوقة ومعتمدة
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

@endsection
