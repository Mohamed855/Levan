@extends('tenant.layouts.layoutMaster')

@section('title', 'إتمام الدفع')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    <section class="section-py bg-body">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <a href="{{ $backUrl }}" class="btn btn-secondary mb-4">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة
                    </a>

                    <div class="card">
                        <div class="card-header border-bottom">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div>
                                    <p class="text-muted mb-1">{{ $description }}</p>
                                    <h5 class="mb-0">الدفع الآمن</h5>
                                </div>
                                <span class="badge bg-label-success">
                                    <i class="ti ti-lock me-1"></i>
                                    Moyasar
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center bg-lighter rounded p-3 mb-5">
                                <span class="text-muted">إجمالي الفاتورة</span>
                                <span class="h5 mb-0">{{ number_format($amount ?? $bill->amount, 2) }} <span
                                        class="icon-saudi_riyal fs-6"></span></span>
                            </div>

                            <x-payment.moyasar-form :amount-in-halala="$amountInHalala" :callback-url="$callbackUrl" :description="$description"
                                :publishable-key="$publishableKey" />

                            @if ($mode === 'test')
                                <p class="text-muted text-center small mt-4 mb-0">
                                    أنت تستخدم بيئة الاختبار؛ استخدم بيانات البطاقة التجريبية المعتمدة من Moyasar فقط.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
