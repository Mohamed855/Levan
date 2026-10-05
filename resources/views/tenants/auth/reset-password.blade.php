@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $resetAction = tenant()
        ? tenant_route_name('password.store')
        : (Route::has('admin.password.store')
            ? route('admin.password.store')
            : route('password.store'));
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'إعادة تعيين كلمة المرور')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/cdn/zxcvbn.js'])

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const strengthDisplay = document.getElementById('password-strength');
            const submitButton = document.getElementById('submit-button');
            const confirmPasswordFeedback = document.getElementById('confirm-password-feedback');

            const criteria = {
                length: {
                    regex: /.{8,}/,
                    element: document.getElementById('criteria-length')
                },
                uppercase: {
                    regex: /[A-Z]/,
                    element: document.getElementById('criteria-uppercase')
                },
                lowercase: {
                    regex: /[a-z]/,
                    element: document.getElementById('criteria-lowercase')
                },
                number: {
                    regex: /[0-9]/,
                    element: document.getElementById('criteria-number')
                },
                specialChar: {
                    regex: /[@$!%*#?&]/,
                    element: document.getElementById('criteria-specialChar')
                }
            };

            function updateSubmitButton(strengthIndex, passwordsMatch, allFieldsFilled) {
                if (!submitButton) return;
                submitButton.disabled = !(strengthIndex >= 2 && passwordsMatch && allFieldsFilled);
            }

            function checkPasswordsMatch() {
                if (!confirmPasswordInput || !newPasswordInput || !confirmPasswordFeedback) return false;
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword.length === 0) {
                    confirmPasswordFeedback.textContent = '';
                    return false;
                }

                if (newPassword === confirmPassword) {
                    confirmPasswordFeedback.textContent = 'كلمتا المرور متطابقتان';
                    confirmPasswordFeedback.style.color = '#198754';
                    return true;
                } else {
                    confirmPasswordFeedback.textContent = 'كلمتا المرور غير متطابقتين';
                    confirmPasswordFeedback.style.color = '#dc3545';
                    return false;
                }
            }

            function areAllFieldsFilled() {
                return newPasswordInput?.value.trim() !== '' && confirmPasswordInput?.value.trim() !== '';
            }

            function handleFormValidation() {
                if (!newPasswordInput) return;
                const password = newPasswordInput.value;
                let criteriaMet = 0;

                for (let key in criteria) {
                    const isValid = criteria[key].regex.test(password);
                    if (criteria[key].element) {
                        criteria[key].element.className = isValid ? 'valid' : 'invalid';
                    }
                    if (isValid) criteriaMet++;
                }

                let strengthIndex = 0;
                if (password.length >= 8) {
                    if (criteriaMet <= 2) strengthIndex = 1;
                    else if (criteriaMet <= 4) strengthIndex = 2;
                    else strengthIndex = 3;
                }

                const strengthLabels = ['ضعيفة', 'متوسطة', 'قوية', 'قوية جدًا'];
                const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754'];
                const percentage = ((strengthIndex + 1) / strengthLabels.length) * 100;

                if (strengthDisplay) {
                    if (password.length === 0) {
                        strengthDisplay.innerHTML = '';
                    } else {
                        strengthDisplay.innerHTML = `
                            <div class="progress my-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: ${percentage}%; background-color: ${colors[strengthIndex]};" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small style="color: ${colors[strengthIndex]}; font-weight: 600;">${strengthLabels[strengthIndex]}</small>
                        `;
                    }
                }

                updateSubmitButton(strengthIndex, checkPasswordsMatch(), areAllFieldsFilled());
            }

            newPasswordInput?.addEventListener('input', handleFormValidation);
            confirmPasswordInput?.addEventListener('input', handleFormValidation);
            if (submitButton) submitButton.disabled = true;
        });
    </script>
@endsection

@section('content')
    <x-auth.split-layout form-title="إعادة تعيين كلمة المرور"
        form-subtitle="الرجاء إدخال بريدك الإلكتروني واختيار كلمة مرور جديدة قوية" :bg-image="asset('img/auth-security-bg.jpg')"
        media-tagline="حماية الحساب | ليفان العقارية" media-title="تحديث وتأمين | كلمة المرور الخاصة بك"
        media-desc="اختر كلمة مرور قوية ومعقدة لحماية حساب شركتك وبيانات عملائك ومزاداتك العقارية وفق أعلى معايير الأمان."
        :features="[
            ['icon' => 'ti ti-key', 'text' => 'تشفير متقدم لكلمات المرور'],
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية الحساب والبيانات'],
            ['icon' => 'ti ti-circle-check', 'text' => 'توافق مع معايير الأمان العالمية'],
        ]" :back-url="tenant_route_name('login')" back-text="العودة لتسجيل الدخول">
        <form method="POST" action="{{ $resetAction }}" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $request->token ?? request('token') }}">
            @if (isset($request->tenant_id) || request('tenant_id'))
                <input type="hidden" name="tenant_id" value="{{ $request->tenant_id ?? request('tenant_id') }}">
            @endif

            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email', $request->email ?? request('email'))" required autofocus autocomplete="username" col="col-12 mb-3"
                icon="ti ti-mail" />

            <x-form.controls.password name="password" id="password" label="كلمة المرور الجديدة"
                placeholder="أدخل كلمة المرور الجديدة" required autocomplete="new-password" col="col-12 mb-2"
                icon="ti ti-lock" />

            <div id="password-strength"></div>
            <ul id="password-criteria" class="mb-3">
                <li id="criteria-length" class="invalid">على الأقل 8 أحرف</li>
                <li id="criteria-uppercase" class="invalid">حرف كبير واحد على الأقل</li>
                <li id="criteria-lowercase" class="invalid">حرف صغير واحد على الأقل</li>
                <li id="criteria-number" class="invalid">رقم واحد على الأقل</li>
                <li id="criteria-specialChar" class="invalid">رمز خاص واحد على الأقل (@, $, !, %, *, #, ?, &)</li>
            </ul>

            <x-form.controls.password name="password_confirmation" id="password_confirmation"
                label="تأكيد كلمة المرور الجديدة" placeholder="أعد إدخال كلمة المرور للتأكيد" required
                autocomplete="new-password" col="col-12 mb-2" icon="ti ti-lock-check" />
            <div id="confirm-password-feedback" class="small mb-3"></div>

            <button type="submit" id="submit-button" class="btn-auth-primary mb-3" disabled>
                <span>إعادة تعيين كلمة المرور</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
