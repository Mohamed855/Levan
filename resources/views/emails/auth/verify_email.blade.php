@extends('emails.layouts.master')

@section('title', 'تأكيد بريدك الإلكتروني — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge info">✉️ التحقق من البريد</span>
    </div>

    <h2 class="greeting">مرحباً {{ $userName ?? 'عزيزنا العميل' }}،</h2>

    <p class="lead-text">
        شكراً لانضمامك إلى منصة <strong>{{ $brandName }}</strong>. لتفعيل حسابك والبدء في الاستفادة من جميع المزايا، يرجى تأكيد بريدك الإلكتروني بالنقر على الزر أدناه:
    </p>

    <div class="cta-button-wrapper">
        <a href="{{ $verifyUrl }}" target="_blank" class="cta-button">
            تأكيد البريد الإلكتروني
        </a>
    </div>

    <div class="alert-box info">
        إذا لم تتمكن من الضغط على الزر، يمكنك نسخ هذا الرابط وفتحه مباشرة في متصفحك:
        <br />
        <a href="{{ $verifyUrl }}" target="_blank" style="word-break: break-all; color: #2563eb; font-size: 12px; margin-top: 6px; display: inline-block;">{{ $verifyUrl }}</a>
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        إذا لم تكن قد أنشأت هذا الحساب، فلا داعي لاتخاذ أي إجراء.
    </p>
@endsection
