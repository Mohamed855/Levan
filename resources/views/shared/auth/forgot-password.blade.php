@php
    $isOtp = !empty($otpStep);
@endphp

<x-auth.split-layout
    :logo-url="$logoUrl ?? null"
    :form-title="$formTitle ?? ($isOtp ? 'التحقق من رمز الأمان' : 'هل نسيت كلمة المرور؟')"
    :form-subtitle="$formSubtitle ?? ($isOtp
        ? 'أدخل رمز التحقق (OTP) المرسل إلى بريدك الإلكتروني لإعادة تعيين كلمة المرور'
        : 'أدخل بريدك الإلكتروني وسنرسل لك رمز تحقق (OTP) لاستعادة حسابك فوراً')"
    :bg-image="$bgImage ?? asset('static/auth/backgrounds/auth-security-bg.jpg')"
    :media-tagline="$mediaTagline ?? 'استعادة الحساب | لوت LOT'"
    :media-title="$mediaTitle ?? 'استعد وصولك الآمن | إلى حسابك'"
    :media-desc="$mediaDesc ?? 'نظام أمان متطور يتيح لك استعادة حسابك بسرعة وأمان عبر رمز تحقق مباشر دون الحاجة إلى روابط خارجية.'"
    :features="$features ?? [
        ['icon' => 'ti ti-shield-check', 'text' => 'تحقق آمن وسريع عبر OTP'],
        ['icon' => 'ti ti-lock-check', 'text' => 'حماية تامة وسرية للحساب'],
        ['icon' => 'ti ti-device-mobile', 'text' => 'إكمال الاستعادة فوراً في نفس الصفحة'],
    ]"
    :back-url="$backUrl"
    :back-text="$backText ?? 'العودة لتسجيل الدخول'">

    @if ($isOtp)
        <form id="formAuthentication" method="POST" action="{{ $verifyAction }}" novalidate
            data-form-validate data-validation-url="{{ $validationUrl }}">
            @csrf
            @if (tenant())
                <input type="hidden" name="tenant_id" value="{{ tenant('id') }}">
            @endif

            <x-form.controls.input name="otp" label="رمز التحقق (OTP)" placeholder="XXXXXX" inputmode="numeric"
                maxlength="6" autocomplete="one-time-code" class="otp-input text-center fw-bold"
                style="font-size: 24px; letter-spacing: 6px;" required col="col-12 mb-3" icon="ti ti-shield-check" />

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" id="otp-timer-actions">
                <div id="checkout-otp-timer" class="otp-timer fw-bold" data-expires-at="{{ $otpExpiresAt ?? 0 }}"
                    data-resend-count="{{ $resendCount ?? 0 }}" data-max-resends="{{ $maxResends ?? 3 }}"
                    data-resend-url="{{ $resendAction }}"></div>

                <div id="otp-resend-container">
                    <button type="button" class="otp-action-btn" id="btn-resend-otp" disabled>
                        <i class="ti ti-rotate-clockwise"></i>
                        <span>إعادة إرسال الرمز</span>
                        <span class="text-xs text-muted"
                            id="otp-remaining-attempts">({{ max(0, ($maxResends ?? 3) - ($resendCount ?? 0)) }} محاولة
                            متبقية)</span>
                    </button>
                </div>
            </div>

            <button class="btn-auth-primary mb-3" type="submit">
                <span>التحقق ومتابعة التعيين</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>

        <form id="otp-resend-form" method="POST" action="{{ $resendAction }}" class="d-none">
            @csrf
            @if (tenant())
                <input type="hidden" name="tenant_id" value="{{ tenant('id') }}">
            @endif
        </form>
    @else
        <form id="formAuthentication" method="POST" action="{{ $emailAction }}" novalidate
            data-form-validate data-validation-url="{{ $validationUrl }}">
            @csrf
            @if (tenant())
                <input type="hidden" name="tenant_id" value="{{ tenant('id') }}">
            @endif

            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني المسجل"
                type="email" :value="old('email', $email ?? '')" required autocomplete="email" col="col-12 mb-4" icon="ti ti-mail" />

            <button class="btn-auth-primary mb-3" type="submit">
                <span>إرسال رمز التحقق</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    @endif
</x-auth.split-layout>
