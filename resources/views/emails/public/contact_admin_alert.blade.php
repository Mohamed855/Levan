@extends('emails.layouts.master')

@section('title', 'رسالة تواصل جديدة — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">💬 رسالة جديدة في «تواصل معنا»</span>
    </div>

    <h2 class="greeting">تنبيه: تم استلام رسالة تواصل جديدة</h2>

    <p class="lead-text">
        قام أحد زوار المنصة بإرسال رسالة جديدة عبر نموذج التواصل:
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات المرسل والرسالة</div>
        <div class="info-row">
            <span class="info-label">اسم المرسل:</span>
            <span class="info-value">{{ $customerName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني:</span>
            <span class="info-value" dir="ltr"><a href="mailto:{{ $customerEmail }}">{{ $customerEmail }}</a></span>
        </div>
        @if(!empty($customerPhone))
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value" dir="ltr">{{ $customerPhone }}</span>
            </div>
        @endif
        @if(!empty($subjectText))
            <div class="info-row">
                <span class="info-label">الموضوع:</span>
                <span class="info-value">{{ $subjectText }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">تاريخ الإرسال:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="info-card" style="background-color: #ffffff; border-right: 4px solid #4f46e5;">
        <div class="info-card-title" style="color: #4338ca;">نص الرسالة:</div>
        <div style="font-size: 14px; color: #334155; line-height: 1.8; white-space: pre-wrap;">{{ $messageContent }}</div>
    </div>

    @if(!empty($adminViewUrl))
        <div class="cta-button-wrapper">
            <a href="{{ $adminViewUrl }}" target="_blank" class="cta-button">
                عرض والرد من لوحة التحكم
            </a>
        </div>
    @endif
@endsection
