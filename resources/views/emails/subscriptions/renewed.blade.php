@extends('emails.layouts.master')

@section('title', 'تم تجديد الاشتراك بنجاح — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">تم تجديد الاشتراك بنجاح</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        تم بنجاح تجديد اشتراك منشأتك في باقة <strong>{{ $planName }}</strong> على منصة <strong>{{ $brandName }}</strong>. شكراً لثقتكم المستمرة بنا.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل التجديد</div>
        <div class="info-row">
            <span class="info-label">اسم الباقة:</span>
            <span class="info-value">{{ $planName }}</span>
        </div>
        @if(!empty($subscriptionNumber))
            <div class="info-row">
                <span class="info-label">رقم الاشتراك:</span>
                <span class="info-value" dir="ltr">#{{ $subscriptionNumber }}</span>
            </div>
        @endif
        @if(!empty($amount))
            <div class="info-row">
                <span class="info-label">المبلغ المدفوع:</span>
                <span class="info-value">{{ number_format((float)$amount, 2) }} ر.س</span>
            </div>
        @endif
        @if(!empty($endDate))
            <div class="info-row">
                <span class="info-label">تاريخ نهاية الفترة الجديدة:</span>
                <span class="info-value">{{ $endDate }}</span>
            </div>
        @endif
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            الدخول إلى لوحة التحكم
        </a>
    </div>
@endsection
