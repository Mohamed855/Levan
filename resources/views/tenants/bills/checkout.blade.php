@extends('tenants.layouts.layoutMaster')

@section('title', 'دفع الفاتورة')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <section class="section-py bg-body">
        <div class="">
            <form id="payment-form" method="POST" action="{{ tenant_route_name('bills.checkout.store') }}" autocomplete="off"
                novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                <input type="hidden" name="coupon_code" id="coupon_code_hidden" data-validation-skip>
                <input type="hidden" name="discount_amount" id="discount_amount_hidden" data-validation-skip>

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
                                <div class="alert alert-primary mb-0 d-flex align-items-start gap-3" role="status">
                                    <i class="ti ti-shield-lock fs-4"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">الدفع الآمن بالبطاقة</h6>
                                        <p class="mb-0">بعد مراجعة الفاتورة ستنتقل إلى نموذج Moyasar الآمن لإدخال بيانات البطاقة مباشرة.</p>
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
