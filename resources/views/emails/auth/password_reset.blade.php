@extends('emails.layouts.master')

@section('title', 'إعادة تعيين كلمة المرور — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">🔑 استعادة الحساب</span>
    </div>

    <h2 class="greeting">مرحباً {{ $userName ?? 'عزيزنا المستخدم' }}،</h2>

    <p class="lead-text">
        لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في منصة <strong>{{ $brandName }}</strong>.
        يمكنك تعيين كلمة مرور جديدة من خلال الضغط على الزر التالي:
    </p>

    <div class="cta-button-wrapper">
        <a href="{{ $resetUrl }}" target="_blank" class="cta-button">
            إعادة تعيين كلمة المرور
        </a>
    </div>

    <div class="alert-box info">
        ⏳ ينتهي هذا الرابط خلال <strong>{{ config('auth.passwords.users.expire', 60) }} دقيقة</strong>. إذا لم تتمكن من النقر على الزر، يمكنك نسخ الرابط التالي ولصقه في متصفحك:
        <br />
        <a href="{{ $resetUrl }}" target="_blank" style="word-break: break-all; color: #2563eb; font-size: 12px; margin-top: 6px; display: inline-block;">{{ $resetUrl }}</a>
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        إذا لم تطلب إعادة تعيين كلمة المرور، فلا داعي للقلق؛ حسابك آمن تماماً ولن يتم تغيير أي شيء.
    </p>
@endsection
