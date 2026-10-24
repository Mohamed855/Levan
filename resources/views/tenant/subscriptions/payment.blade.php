@extends('layouts.dashboard')

@section('title', 'إتمام الدفع')
@section('dashboard-scope', 'tenant')

@section('content')
<div class="container-fluid" dir="rtl"><div class="card"><div class="card-body">
    <h1 class="h4 mb-3">إتمام الدفع الآمن</h1>
    <p>إجمالي الفاتورة: <strong>{{ number_format($attempt->amount, 2) }} ر.س</strong></p>
    <x-payment.moyasar-form :amount-in-halala="$amountInHalala" :callback-url="$callbackUrl" description="عملية اشتراك للمنشأة" :publishable-key="$publishableKey" :metadata="['checkout_token' => $attempt->checkout_token, 'bill_number' => $attempt->bill->bill_number]" />
    @if ($mode === 'test')<p class="text-muted mt-3">وضع اختبار Moyasar مفعّل.</p>@endif
</div></div></div>
@endsection
