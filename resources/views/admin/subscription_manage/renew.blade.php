@extends('admin.layouts.layoutMaster')
@section('title', 'تجديد الاشتراك')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])

@endsection
@dd($plan)
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection
@section('page-script')
    <script>
        window.paymentData = {
            subtotal: {{ $subtotal }},
            vatValue: {{ $vat_value }},
            total: {{ $total }},

            basePrice: {{ $base_price }},
            userTotal: {{ $user_total }},
        };
    </script>

    @vite(['resources/assets/js/shared/payment/validation.js', 'resources/assets/js/shared/payment/apply-coupon.js', 'resources/assets/js/site/front-page-payment.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection
 @section('content')
    <div class="">
        <div class="card px-3 ">
            <div class="card-header py-4">
                <h6 class="card-title mb-0">
                    <i class="ti ti-repeat text-primary me-2"></i>
                    تجديد الاشتراك
                </h6>
            </div>

            <div class="border-1 border-light border-dashed mb-2"></div>
            <form id="payment-form" method="POST" action="#">
                @csrf
                <input type="hidden" name="coupon_code" id="coupon_code_hidden">
                <input type="hidden" name="discount_amount" id="discount_amount_hidden">

                <div class="row">
                    <!-- بيانات البطاقة -->
                    <div id="form-credit-card" class="col-lg-7 card-body border-end p-md-8 m-auto">

                        <div id="form-credit-card" class="mt-5">
                            <div class="row g-3">
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
                                    <input type="text" id="cvc" name="cvc" class="form-control text-start"
                                        inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123"
                                        required />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ملخص الدفع -->
                    <div class="col-lg-5 card-body p-md-8 m-auto">


                        <!-- قسم الكوبون -->
                        <div class="mb-4">
                            <div class="d-flex gap-2">
                                <input type="text" id="coupon_code_input" name="coupon_code_display" class="form-control"
                                    placeholder="أدخل كود الخصم">

                                <button type="button" id="apply-coupon-btn" class="btn btn-outline-primary">
                                    <i class="ti ti-discount-2 me-1"></i>
                                    تطبيق
                                </button>
                            </div>
                            <div id="coupon-message" class="mt-2"></div>
                        </div>


                        <!-- تفاصيل الأسعار -->
                        <div class="mt-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                {{-- <p class="mb-0 text-body">سعر الباقة ({{ $billing_label }})</p> --}}
                                <p class="mb-0 text-body">سعر الباقة </p>
                                <h6 class="mb-0">
                                    {{ number_format($base_price, 2) }}
                                    <span class="icon-saudi_riyal mx-2"></span>
                                </h6>
                            </div>

                            @if ($users > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="mb-0 text-body">
                                        عدد التراخيص الاضافية
                                        ({{ $users }} * {{ number_format($price_per_user, 2) }})
                                    </p>
                                    <h6 class="mb-0">
                                        {{ number_format($user_total, 2) }}
                                        <span class="icon-saudi_riyal mx-2"></span>
                                    </h6>
                                </div>
                            @endif

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="mb-0 text-body">المجموع قبل الضريبة</p>
                                <h6 class="mb-0" id="subtotal-display">
                                    {{ number_format($subtotal, 2) }}
                                    <span class="icon-saudi_riyal mx-2"></span>
                                </h6>
                            </div>

                            <!-- سطر الخصم (يظهر بعد تطبيق الكوبون) -->
                            <div class="d-flex justify-content-between align-items-center mb-3 d-none" id="discount-row">
                                <p class="mb-0 text-body text-success">
                                    <i class="ti ti-discount-2 me-1"></i>
                                    الخصم
                                    <button type="button" class="btn btn-sm btn-text-danger p-0 ms-2"
                                        id="remove-coupon">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </p>
                                <h6 class="mb-0 text-danger">
                                    -<span id="discount-display">0.00</span>
                                    <span class="icon-saudi_riyal mx-2"></span>
                                </h6>
                            </div>

                            <!-- المجموع بعد الخصم -->
                            <div class="d-flex justify-content-between align-items-center mb-3 d-none"
                                id="after-discount-row">
                                <p class="mb-0 text-body fw-bold">المجموع بعد الخصم</p>
                                <h6 class="mb-0 fw-bold text-primary" id="after-discount-display">
                                    0.00 </span>
                                </h6>
                            </div>


                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="mb-0 text-body">ضريبة القيمة المضافة (15%)</p>
                                <h6 class="mb-0 text-success" id="vat-display">
                                    {{ number_format($vat_value, 2) }}
                                    <span class="icon-saudi_riyal mx-2"></span>
                                </h6>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 fw-bold">المبلغ الإجمالي</h5>
                                <h5 class="mb-0 fw-bold text-primary" id="total-final">
                                    {{ number_format($total, 2) }}
                                    <span class="icon-saudi_riyal mx-2"></span>
                                </h5>
                            </div>

                            {{-- @if ($billing == 'yearly' && $yearlyDiscount > 0)
                                    <div class="alert alert-success mt-3 py-2 px-3">
                                        <small>
                                            <i class="ti ti-discount-check me-1"></i>
                                            <strong>وفرت {{ $yearlyDiscount }}%</strong> على سعر الباقة الأساسية بالاشتراك
                                            السنوي
                                        </small>
                                    </div>
                                @endif --}}

                            <div class="d-grid mt-6">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <span class="me-2">إتمام الدفع</span>

                                    <i class="ti ti-credit-card-pay scaleX-n1-rtl"></i>

                                </button>
                            </div>


                        </div>
                    </div>
                </div> <!-- /row -->
            </form>
        </div>
    </div>
@endsection
