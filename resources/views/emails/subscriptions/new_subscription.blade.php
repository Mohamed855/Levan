@extends('emails.layouts.master')

@section('title', 'تم تفعيل اشتراكك بنجاح — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">✅ تم تفعيل الاشتراك بنجاح</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        يسعدنا انضمامك إلى عائلة <strong>{{ $brandName }}</strong>! تم تفعيل اشتراك منشأتك بنجاح، وجميع ميزات باقتك أصبحت جاهزة للاستخدام الآن.
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات وتفاصيل الاشتراك</div>
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
        @if(!empty($billNumber))
            <div class="info-row">
                <span class="info-label">رقم الفاتورة:</span>
                <span class="info-value" dir="ltr">#{{ $billNumber }}</span>
            </div>
        @endif
        @if(!empty($startDate))
            <div class="info-row">
                <span class="info-label">تاريخ البداية:</span>
                <span class="info-value">{{ $startDate }}</span>
            </div>
        @endif
        @if(!empty($endDate))
            <div class="info-row">
                <span class="info-label">تاريخ التجديد القادم:</span>
                <span class="info-value">{{ $endDate }}</span>
            </div>
        @endif
        @if(!empty($totalUsers))
            <div class="info-row">
                <span class="info-label">عدد المستخدمين المتاحين:</span>
                <span class="info-value">{{ $totalUsers }} مستخدم</span>
            </div>
        @endif
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            الانتقال إلى لوحة التحكم
        </a>
    </div>

    <div class="alert-box info">
        📄 يمكنك دائماً مراجعة فواتيرك وتفاصيل دورة الفوترة الخاصة بك من خلال شاشة «إدارة الاشتراكات» داخل لوحة تحكم حسابك.
    </div>
@endsection
