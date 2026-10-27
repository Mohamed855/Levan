@extends('emails.layouts.master')

@section('title', 'تم استلام طلب التجربة المجانية — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge info">📋 طلب تجربة قيد المراجعة</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        سعداء باهتمامك بمنصة <strong>{{ $brandName }}</strong>! لقد استلمنا طلبك للحصول على تجربة مجانية للنظام، ويقوم فريق العمل حالياً بمراجعة بيانات منشأتك لتجهيز بيئة العمل المستقلة الخاصة بك.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل الطلب</div>
        <div class="info-row">
            <span class="info-label">اسم العميل:</span>
            <span class="info-value">{{ $customerName }}</span>
        </div>
        @if(!empty($companyName))
            <div class="info-row">
                <span class="info-label">المنشأة:</span>
                <span class="info-value">{{ $companyName }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">تاريخ الطلب:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">مدة التجربة:</span>
            <span class="info-value">7 أيام تجريبية مجانية</span>
        </div>
    </div>

    <div class="alert-box info">
        💡 <strong>ما هي الخطوة القادمة؟</strong> بمجرد اعتماد الطلب من قبل الإدارة، ستصلك رسالة عبر هذا البريد الإلكتروني تحتوي على رابط الدخول المباشر إلى المنصة وبيانات حسابك لتبدأ تجربتك فوراً.
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        إذا كان لديك أي استفسار عاجل، لا تتردد في مراسلتنا على: <a href="mailto:{{ $officeEmail }}">{{ $officeEmail }}</a>
    </p>
@endsection
