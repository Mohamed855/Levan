@extends('emails.layouts.master')

@section('title', 'تم استلام رسالتك بنجاح — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">تم استلام الرسالة</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        نشكرك على تواصلك مع منصة <strong>{{ $brandName }}</strong>. نود إعلامك بأن رسالتك قد وصلت بنجاح إلى فريق الدعم وخدمة العملاء.
    </p>

    <div class="info-card">
        <div class="info-card-title">ملخص رسالتك</div>
        @if(!empty($subject))
            <div class="info-row">
                <span class="info-label">الموضوع:</span>
                <span class="info-value">{{ $subject }}</span>
            </div>
        @endif
        @if(!empty($messageContent))
            <div class="info-row">
                <span class="info-label">نص الرسالة:</span>
                <span class="info-value">{{ Str::limit($messageContent, 200) }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">وقت الاستلام:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="alert-box info">
        سيعمل فريقنا المختص على مراجعة استفسارك والرد عليك في أقرب وقت ممكن عبر هذا البريد الإلكتروني.
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        مع تحيات فريق عمل {{ $brandName }}
    </p>
@endsection
