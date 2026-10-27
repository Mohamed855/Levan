@extends('emails.layouts.master')

@section('title', 'تم تفعيل حسابك التجريبي بنجاح — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">🎉 تم تفعيل الحساب بنجاح</span>
    </div>

    <h2 class="greeting">تهانينا {{ $customerName }}،</h2>

    <p class="lead-text">
        يسرنا إبلاغك بأنه تمت الموافقة على طلبك وتجهيز مساحة العمل المستقلة الخاصة بك على منصة <strong>{{ $brandName }}</strong> لفترة تجريبية مجانية لمدة <strong>{{ $trialDays ?? 7 }} أيام</strong>!
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات الدخول إلى المنصة</div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني:</span>
            <span class="info-value" dir="ltr">{{ $customerEmail }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">كلمة المرور:</span>
            <span class="info-value">كلمة المرور التي حددتها أثناء التسجيل</span>
        </div>
        <div class="info-row">
            <span class="info-label">فترة التجربة:</span>
            <span class="info-value">{{ $trialDays ?? 7 }} أيام مجاناً</span>
        </div>
        @if(!empty($endDate))
            <div class="info-row">
                <span class="info-label">تاريخ نهاية التجربة:</span>
                <span class="info-value">{{ $endDate }}</span>
            </div>
        @endif
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            الدخول إلى لوحة التحكم الخاصة بك
        </a>
    </div>

    <div class="alert-box info">
        🚀 <strong>نصيحة لبداية سريعة:</strong> يمكنك الآن استكشاف وحدات المزادات، إدارة العقارات، والعقود، وإضافة فريق عملك. فريق الدعم الفني جاهز لمساعدتك في أي استفسار خلال رحلتك التجريبية.
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px;">
        رابط الدخول المباشر: <br />
        <a href="{{ $loginUrl }}" target="_blank" style="word-break: break-all; color: #4f46e5; font-size: 13px;">{{ $loginUrl }}</a>
    </p>
@endsection
