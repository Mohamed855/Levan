@extends('emails.layouts.master')

@section('title', 'طلب تجربة مجانية جديد — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">🔔 إشعار إداري</span>
    </div>

    <h2 class="greeting">تنبيه: تم تقديم طلب تجربة مجانية جديد</h2>

    <p class="lead-text">
        قام عميل جديد بتقديم طلب تجربة مجانية لمنصة <strong>{{ $brandName }}</strong> ويتطلب مراجعة واعتماد الإدارة:
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات العميل والطلب</div>
        <div class="info-row">
            <span class="info-label">اسم العميل:</span>
            <span class="info-value">{{ $trialRequest->customer_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">اسم المنشأة:</span>
            <span class="info-value">{{ $trialRequest->company_name ?? 'غير محدد' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني:</span>
            <span class="info-value" dir="ltr">{{ $trialRequest->customer_email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الهاتف:</span>
            <span class="info-value" dir="ltr">{{ $trialRequest->customer_phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">السجل التجاري:</span>
            <span class="info-value">{{ $trialRequest->commercial_number ?? 'غير مدخل' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التقديم:</span>
            <span class="info-value">{{ $trialRequest->created_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ route('admin.trial-requests.show', $trialRequest->id) }}" target="_blank" class="cta-button">
            معاينة الطلب واعتماده
        </a>
    </div>
@endsection
