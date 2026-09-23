{{-- resources/views/auth/reset-password.blade.php --}}

@php
    $customizerHidden = 'customizer-hide';

    // التحقق من إعدادات الصفحة الممررة من المتحكم
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'إعادة تعيين كلمة المرور')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth/pages-auth.js', 'resources/assets/js/shared/cdn/zxcvbn.js'])

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                toastr.success("{{ session('status') }}", "نجاح");
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @foreach ($errors->all() as $error)
                    toastr.error("{{ $error }}", "خطأ");
                @endforeach
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const strengthDisplay = document.getElementById('password-strength');
            const submitButton = document.getElementById('submit-button'); // معرف زر الإرسال

            // عناصر للتحقق من تطابق كلمات المرور
            const confirmPasswordFeedback = document.getElementById('confirm-password-feedback');

            // عناصر لتلميحات المعايير
            const criteriaList = document.getElementById('password-criteria');
            const criteria = {
                length: {
                    regex: /.{8,}/,
                    message: 'على الأقل 8 أحرف',
                    element: document.getElementById('criteria-length')
                },
                uppercase: {
                    regex: /[A-Z]/,
                    message: 'حرف كبير واحد على الأقل',
                    element: document.getElementById('criteria-uppercase')
                },
                lowercase: {
                    regex: /[a-z]/,
                    message: 'حرف صغير واحد على الأقل',
                    element: document.getElementById('criteria-lowercase')
                },
                number: {
                    regex: /[0-9]/,
                    message: 'رقم واحد على الأقل',
                    element: document.getElementById('criteria-number')
                },
                specialChar: {
                    regex: /[@$!%*#?&]/,
                    message: 'رمز خاص واحد على الأقل (@, $, !, %, *, #, ?, &)',
                    element: document.getElementById('criteria-specialChar')
                }
            };

            // تعيين حالة الزر بناءً على الشروط
            function updateSubmitButton(strengthIndex, passwordsMatch, allFieldsFilled) {
                if (strengthIndex !== 3 || !passwordsMatch || !
                    allFieldsFilled) { // يجب أن تكون قوية جدًا، متطابقة، ومكتملة
                    submitButton.disabled = true;
                } else {
                    submitButton.disabled = false;
                }
            }

            // التحقق من تطابق كلمات المرور
            function checkPasswordsMatch() {
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword.length === 0) {
                    confirmPasswordFeedback.textContent = '';
                    return false;
                }

                if (newPassword === confirmPassword) {
                    confirmPasswordFeedback.textContent = 'كلمتا المرور متطابقتان';
                    confirmPasswordFeedback.style.color = '#28a745'; // أخضر
                    return true;
                } else {
                    confirmPasswordFeedback.textContent = 'كلمتا المرور غير متطابقتان';
                    confirmPasswordFeedback.style.color = '#dc3545'; // أحمر
                    return false;
                }
            }

            // التحقق من جميع الحقول
            function areAllFieldsFilled() {
                return newPasswordInput.value.trim() !== '' &&
                    confirmPasswordInput.value.trim() !== '';
            }

            // إعادة تعيين حالة المعايير إلى غير محقق
            function resetCriteria() {
                for (let key in criteria) {
                    if (criteria.hasOwnProperty(key)) {
                        criteria[key].element.classList.remove('valid');
                        criteria[key].element.classList.add('invalid');
                    }
                }
            }

            // تحديث تلميحات المعايير
            function updateCriteriaFeedback(password) {
                for (let key in criteria) {
                    if (criteria.hasOwnProperty(key)) {
                        if (criteria[key].regex.test(password)) {
                            criteria[key].element.classList.remove('invalid');
                            criteria[key].element.classList.add('valid');
                        } else {
                            criteria[key].element.classList.remove('valid');
                            criteria[key].element.classList.add('invalid');
                        }
                    }
                }
            }

            // تحديث حالة زر الإرسال عند تغيير أي من الحقول
            function handleFormValidation() {
                const password = newPasswordInput.value;

                if (password.length >= 8) {
                    // إظهار قائمة المعايير
                    criteriaList.style.display = 'block';
                } else {
                    // إخفاء قائمة المعايير وإعادة تعيين المعايير
                    criteriaList.style.display = 'none';
                    resetCriteria();
                }

                // تحديث تلميحات المعايير إذا كان الطول >=8
                if (password.length >= 8) {
                    updateCriteriaFeedback(password);
                }

                // حساب عدد المعايير المحققة فقط إذا الطول >=8
                let criteriaMet = 0;
                if (password.length >= 8) {
                    for (let key in criteria) {
                        if (criteria.hasOwnProperty(key)) {
                            if (criteria[key].regex.test(password)) {
                                criteriaMet++;
                            }
                        }
                    }
                }

                // تصنيف قوة كلمة المرور بناءً على عدد المعايير المحققة
                let strengthIndex;
                if (password.length < 8) {
                    strengthIndex = 0; // ضعيفة
                } else {
                    if (criteriaMet <= 1) {
                        strengthIndex = 0; // ضعيفة
                    } else if (criteriaMet <= 3) {
                        strengthIndex = 1; // متوسطة
                    } else if (criteriaMet === 4) {
                        strengthIndex = 2; // قوية
                    } else if (criteriaMet === 5) {
                        strengthIndex = 3; // قوية جدًا
                    }
                }

                // تعريف تسميات القوة والألوان
                const strengthLabels = [
                    'ضعيفة', // 0-1
                    'متوسطة', // 2-3
                    'قوية', // 4
                    'قوية جدًا' // 5
                ];

                const colors = [
                    '#dc3545', // أحمر (ضعيفة)
                    '#fd7e14', // برتقالي (متوسطة)
                    '#ffc107', // أصفر (قوية)
                    '#28a745' // أخضر (قوية جدًا)
                ];

                // حساب نسبة القوة
                const percentage = ((strengthIndex + 1) / strengthLabels.length) * 100;

                // تحديث مؤشر القوة
                if (password.length === 0) {
                    strengthDisplay.innerHTML = '';
                    updateSubmitButton(0, false, false); // تعطيل الزر
                } else {
                    strengthDisplay.innerHTML = `
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" style="width: ${percentage}%; background-color: ${colors[strengthIndex]};" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small style="color: ${colors[strengthIndex]};">${strengthLabels[strengthIndex]}</small>
                    `;
                }

                // التحقق من تطابق كلمات المرور
                const passwordsMatch = checkPasswordsMatch();

                // التحقق من ملء جميع الحقول
                const allFieldsFilled = areAllFieldsFilled();

                // تحديث حالة زر الإرسال بناءً على الشروط
                updateSubmitButton(strengthIndex, passwordsMatch, allFieldsFilled);
            }

            // إضافة مستمعات للأحداث لجميع الحقول
            newPasswordInput.addEventListener('input', handleFormValidation);
            confirmPasswordInput.addEventListener('input', handleFormValidation);

            // تعيين حالة الزر عند تحميل الصفحة
            submitButton.disabled = true;
        });
    </script>
    <style>
        /* تنسيق قائمة المعايير */
        #password-criteria {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
            display: none;
            /* إخفاء القائمة بشكل افتراضي */
            background-color: rgba(255, 255, 255, 0.8);
            /* خلفية شفافة بيضاء */
            padding: 10px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
            /* تأثير التمويه */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #password-criteria li {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        #password-criteria li.valid::before {
            content: '✔️';
            color: #28a745;
            /* أخضر */
            margin-right: 8px;
        }

        #password-criteria li.invalid::before {
            content: '❌';
            color: #dc3545;
            /* أحمر */
            margin-right: 8px;
        }

        /* تحسين رؤية الخلفية الشفافة */
        #password-criteria {
            backdrop-filter: blur(5px);
            /* تأثير التمويه */
        }

        /* تنسيق مؤشر القوة */
        #password-strength {
            margin-top: 10px;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Reset Password -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">
                                    @include('_partials.macros', [
                                        'height' => 20,
                                        'withbg' => 'fill: #fff;',
                                    ])
                                </span>
                                <span
                                    class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">إعادة تعيين كلمة المرور</h4>
                        <p class="mb-6">الرجاء إدخال بريدك الإلكتروني وكلمة مرور جديدة.</p>

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.password.store') }}">
                            @csrf

                            <!-- Password Reset Token -->
                            <input type="hidden" name="token" value="{{ $request->token }}">
                            <input type="hidden" name="tenant_id" value="{{ $request->tenant_id }}">

                            <!-- Email Address -->
                            <div class="mb-4">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input id="email" class="form-control" type="email" name="email"
                                    value="{{ old('email', $request->email) }}" required autofocus
                                    autocomplete="username" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- New Password -->
                            <div class="mb-4 form-password-toggle">
                                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                                <div class="input-group input-group-merge">
                                    <input id="password" class="form-control" type="password" name="password" required
                                        autocomplete="new-password" />
                                    <span class="input-group-text cursor-pointer">
                                        <i class="ti ti-eye-off"></i>
                                    </span>
                                </div>
                                <!-- مؤشر قوة كلمة المرور -->
                                <div id="password-strength" class="mt-2"></div>
                                <!-- قائمة المعايير -->
                                <ul id="password-criteria">
                                    <li id="criteria-length" class="invalid">على الأقل 8 أحرف</li>
                                    <li id="criteria-uppercase" class="invalid">حرف كبير واحد على الأقل</li>
                                    <li id="criteria-lowercase" class="invalid">حرف صغير واحد على الأقل</li>
                                    <li id="criteria-number" class="invalid">رقم واحد على الأقل</li>
                                    <li id="criteria-specialChar" class="invalid">رمز خاص واحد على الأقل (@, $, !, %, *, #,
                                        ?, &)</li>
                                </ul>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4 form-password-toggle">
                                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <div class="input-group input-group-merge">
                                    <input id="password_confirmation" class="form-control" type="password"
                                        name="password_confirmation" required autocomplete="new-password" />
                                    <span class="input-group-text cursor-pointer">
                                        <i class="ti ti-eye-off"></i>
                                    </span>
                                </div>
                                <!-- مؤشر تطابق كلمة المرور -->
                                <small id="confirm-password-feedback" class="form-text"></small>
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" id="submit-button" class="btn btn-primary" disabled>إعادة تعيين كلمة
                                    المرور</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Reset Password -->
            </div>
        </div>
    </div>
@endsection
