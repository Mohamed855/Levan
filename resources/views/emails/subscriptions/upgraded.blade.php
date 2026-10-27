@extends('emails.layouts.master')

@section('title', 'تمت ترقية الاشتراك بنجاح — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">⚡ ترقية وتراخيص جديدة</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        تم بنجاح شراء تراخيص إضافية وتحديث سعة اشتراك منشأتك على منصة <strong>{{ $brandName }}</strong>.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل التراخيص الجديدة</div>
        <div class="info-row">
            <span class="info-label">اسم الباقة:</span>
            <span class="info-value">{{ $planName }}</span>
        </div>
        @if(!empty($newLicensesCount))
            <div class="info-row">
                <span class="info-label">التراخيص المضافة:</span>
                <span class="info-value">+{{ $newLicensesCount }} مستخدم</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">إجمالي المقاعد المتاحة الآن:</span>
            <span class="info-value">{{ $totalUsers }} مستخدم</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            إضافة مستخدمين الآن
        </a>
    </div>
@endsection
