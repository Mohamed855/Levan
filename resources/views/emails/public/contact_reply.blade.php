@extends('emails.layouts.master')

@section('title', 'رد على استفسارك — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">رد فريق الدعم</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        بخصوص استفسارك ورسالتك الموجهة إلى إدارة <strong>{{ $brandName }}</strong>، يسرنا تزويدك بالرد التالي:
    </p>

    <div class="info-card" style="border-right: 4px solid #10b981;">
        <div class="info-card-title" style="color: #047857;">رد الإدارة:</div>
        <div style="font-size: 14.5px; color: #1e293b; line-height: 1.8; white-space: pre-wrap;">{{ $replyMessage }}</div>
    </div>

    <div class="info-card" style="background-color: #f8fafc; opacity: 0.85;">
        <div class="info-card-title" style="color: #64748b;">رسالتك السابقة:</div>
        <div style="font-size: 13px; color: #64748b; line-height: 1.7; white-space: pre-wrap;">{{ $originalMessage }}</div>
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        إذا كان لديك أي سؤال إضافي، يسعدنا تواصلك الدائم معنا.
        <br />
        مع أطيب التحيات،<br />
        فريق دعم {{ $brandName }}
    </p>
@endsection
