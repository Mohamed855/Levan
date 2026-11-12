<x-auth.split-layout
    :logo-url="$logoUrl ?? null"
    :form-title="$formTitle ?? 'إعادة تعيين كلمة المرور'"
    :form-subtitle="$formSubtitle ?? 'الرجاء إدخال كلمة مرور جديدة قوية وتأكيدها لإتمام الاستعادة'"
    :bg-image="$bgImage ?? asset('static/auth/backgrounds/auth-security-bg.jpg')"
    :media-tagline="$mediaTagline ?? 'حماية الحساب | لوت LOT'"
    :media-title="$mediaTitle ?? 'تحديث وتأمين | كلمة المرور الخاصة بك'"
    :media-desc="$mediaDesc ?? 'اختر كلمة مرور قوية ومعقدة لحماية حسابك وبياناتك وفق أعلى معايير الأمان.'"
    :features="$features ?? [
        ['icon' => 'ti ti-key', 'text' => 'تشفير متقدم لكلمات المرور'],
        ['icon' => 'ti ti-shield-check', 'text' => 'حماية الحساب والبيانات'],
        ['icon' => 'ti ti-circle-check', 'text' => 'توافق مع معايير الأمان العالمية'],
    ]"
    :back-url="$backUrl"
    :back-text="$backText ?? 'العودة لتسجيل الدخول'">

    <form method="POST" action="{{ $resetAction }}" novalidate data-form-validate
        data-validation-url="{{ $validationUrl }}">
        @csrf
        @if (tenant())
            <input type="hidden" name="tenant_id" value="{{ tenant('id') }}">
        @endif

        <input type="hidden" name="email" value="{{ $resolvedEmail }}" autocomplete="username">

        <x-form.controls.password name="password" id="password" label="كلمة المرور الجديدة"
            placeholder="أدخل كلمة المرور الجديدة" required autocomplete="new-password" col="col-12 mb-2"
            icon="ti ti-lock" />

        <x-form.controls.password name="password_confirmation" id="password_confirmation"
            label="تأكيد كلمة المرور الجديدة" placeholder="أعد إدخال كلمة المرور للتأكيد" required
            autocomplete="new-password" col="col-12 mb-3" icon="ti ti-lock-check" />

        <button type="submit" class="btn-auth-primary mb-3">
            <span>حفظ كلمة المرور الجديدة</span>
            <i class="ti ti-arrow-left" aria-hidden="true"></i>
        </button>
    </form>
</x-auth.split-layout>
