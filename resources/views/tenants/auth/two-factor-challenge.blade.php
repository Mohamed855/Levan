@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'التحقق بخطوتين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!--  Two Steps Verification -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
                                <span
                                    class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">التحقق بخطوتين 💬</h4>
                        <p class="text-start mb-6">
                            أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل الدخول.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <p class="mb-0" id="codeLabel">أدخل الرمز الأمني المكون من 6 أرقام</p>

                        <form id="twoStepsForm" action="{{ tenant_route_name('tenants.two-factor.store') }}" method="POST">
                            @csrf

                            <div class="mb-6" id="codeInputsWrapper">
                                <div class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper"
                                    dir="ltr">
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1" autofocus>
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1">
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1">
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1">
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1">
                                    <input type="tel"
                                        class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2"
                                        maxlength="1">
                                </div>
                                <!-- الحقل المخفي اللي بيتجمع فيه الأرقام الستة -->
                                <input type="hidden" name="code" id="otpHidden" />
                            </div>

                            <div class="mb-6 d-none" id="recoveryWrapper">
                                <label for="recovery_code" class="form-label">كود الاسترجاع</label>
                                <input type="text" class="form-control" name="recovery_code" id="recovery_code">
                            </div>

                            <button class="btn btn-primary d-grid w-100 mb-6" type="submit">
                                تأكيد
                            </button>

                            <div class="text-center">
                                <a href="javascript:void(0);" id="toggleRecoveryLink">
                                    استخدام كود استرجاع بدلاً من ذلك
                                </a>
                            </div>

                            <div class="text-center mt-3">
                                <a href="{{ tenant_route_name('tenants.two-factor.cancel') }}">
                                    <i class="ti ti-arrow-right me-1"></i>
                                    الرجوع لتسجيل الدخول بحساب آخر
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- / Two Steps Verification -->
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth/pages-auth.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('#codeInputsWrapper .auth-input');
            const hiddenField = document.getElementById('otpHidden');
            const form = document.getElementById('twoStepsForm');

            function updateHiddenField() {
                let value = '';
                inputs.forEach(input => value += input.value);
                hiddenField.value = value;
            }

            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    if (e.target.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    updateHiddenField();
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
                    if (/^\d+$/.test(pasted)) {
                        pasted.split('').forEach((char, i) => {
                            if (inputs[i]) inputs[i].value = char;
                        });
                        updateHiddenField();
                        inputs[Math.min(pasted.length, inputs.length) - 1]?.focus();
                    }
                });
            });

            const toggleLink = document.getElementById('toggleRecoveryLink');
            const codeWrapper = document.getElementById('codeInputsWrapper');
            const recoveryWrapper = document.getElementById('recoveryWrapper');
            const codeLabel = document.getElementById('codeLabel');
            let usingRecovery = false;

            toggleLink.addEventListener('click', function() {
                usingRecovery = !usingRecovery;
                codeWrapper.classList.toggle('d-none', usingRecovery);
                codeLabel.classList.toggle('d-none', usingRecovery);
                recoveryWrapper.classList.toggle('d-none', !usingRecovery);
                toggleLink.textContent = usingRecovery ?
                    'استخدام رمز المصادقة بدلاً من ذلك' :
                    'استخدام كود استرجاع بدلاً من ذلك';

                if (usingRecovery) {
                    inputs.forEach(input => input.value = '');
                    hiddenField.value = '';
                } else {
                    document.getElementById('recovery_code').value = '';
                }
            });

            form.addEventListener('submit', function() {
                updateHiddenField();
            });
        });
    </script>
@endsection
