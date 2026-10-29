@extends('emails.layouts.master')

@section('title', 'مرحباً بك في منصة ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">مرحباً بك معنا</span>
    </div>

    <h2 class="greeting">مرحباً {{ $userName }}،</h2>

    <p class="lead-text">
        تم إنشاء حساب جديد لك في منصة <strong>{{ $brandName }}</strong>
        @if(!empty($companyName))
            ضمن فريق عمل <strong>{{ $companyName }}</strong>
        @endif
        . يمكنك الآن تسجيل الدخول والبدء في استخدام النظام.
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات تسجيل الدخول الخاصة بك</div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني:</span>
            <span class="info-value" dir="ltr">{{ $email }}</span>
        </div>
        @if(!empty($temporaryPassword))
            <div class="info-row">
                <span class="info-label">كلمة المرور المؤقتة:</span>
                <span class="info-value" dir="ltr" style="border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-family: monospace;">{{ $temporaryPassword }}</span>
            </div>
        @endif
        @if(!empty($roleName))
            <div class="info-row">
                <span class="info-label">الدور / الصلاحية:</span>
                <span class="info-value">{{ $roleName }}</span>
            </div>
        @endif
    </div>

    @if(!empty($temporaryPassword))
        <div class="alert-box warning">
            <strong>تنبيه أمان:</strong> يُرجى تغيير كلمة المرور المؤقتة فور تسجيل دخولك للمرة الأولى للحفاظ على أمان حسابك.
        </div>
    @endif

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            تسجيل الدخول إلى المنصة
        </a>
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center;">
        إذا واجهتك أي صعوبة في الدخول، يمكنك التواصل مباشرة مع مسؤول النظام في منشأتك أو مع الدعم الفني.
    </p>
@endsection
