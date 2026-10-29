@extends('emails.layouts.master')

@section('title', 'رمز التحقق — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge primary">رمز التحقق</span>
    </div>

    <h2 class="greeting" style="text-align: center;">مرحباً بك</h2>

    <p class="lead-text" style="text-align: center;">
        @if(!empty($purpose))
            استخدم رمز التحقق التالي لإتمام عملية <strong>{{ $purpose }}</strong>:
        @else
            استخدم رمز التحقق التالي لتأكيد بريدك الإلكتروني في منصة <strong>{{ $brandName }}</strong>:
        @endif
    </p>

    <div class="highlight-box">
        <div style="font-size: 13px; color: #475569; font-weight: 600;">رمز التحقق (OTP) الخاص بك</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-expiry">هذا الرمز صالح لمدة 10 دقائق فقط. لا تشاركه مع أي شخص لحماية حسابك.</div>
    </div>

    <div class="alert-box warning">
        <strong>تنبيه أمني:</strong> فريق {{ $brandName }} لن يطلب منك هذا الرمز أبداً عبر الهاتف أو وسائل التواصل الاجتماعي.
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px;">
        إذا لم تكن أنت من قام بهذا الطلب، يمكنك تجاهل هذا البريد الإلكتروني بأمان.
    </p>
@endsection
