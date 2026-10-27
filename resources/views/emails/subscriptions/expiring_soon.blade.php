@extends('emails.layouts.master')

@section('title', 'تذكير: اقتراب موعد انتهاء الاشتراك — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">⏳ تذكير بانتهاء الاشتراك</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        نود تذكيرك بأن اشتراك منشأتك في باقة <strong>{{ $planName }}</strong> على منصة <strong>{{ $brandName }}</strong> سينتهي خلال <strong>{{ $daysRemaining }} أيام</strong> (بتاريخ {{ $expiryDate }}).
    </p>

    <div class="alert-box warning">
        ⚠️ لتجنب انقطاع الخدمة أو توقف وصول فريق العمل إلى بيانات المزادات والعقارات، يُرجى تجديد الاشتراك قبل حلول تاريخ الانتهاء.
    </div>

    <div class="info-card">
        <div class="info-card-title">تفاصيل الاشتراك الحالي</div>
        <div class="info-row">
            <span class="info-label">اسم الباقة:</span>
            <span class="info-value">{{ $planName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الانتهاء:</span>
            <span class="info-value" style="color: #b45309; font-weight: 700;">{{ $expiryDate }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الأيام المتبقية:</span>
            <span class="info-value">{{ $daysRemaining }} يوم</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $renewUrl }}" target="_blank" class="cta-button">
            تجديد الاشتراك الآن
        </a>
    </div>
@endsection
