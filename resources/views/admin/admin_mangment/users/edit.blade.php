@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل بيانات المستخدم')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/admin_mangment/users/form-validation.js'])

    <script>
        $(document).ready(function() {
            let emailCheckTimeout;
            let ajaxRequest = null;
            const originalEmail = "{{ $user->email }}";
            const $nextButton = $('#basic-info-validation .btn-next');

            // دالة لتحديث حالة الزر
            function updateButtonState() {
                const $email = $('#email');
                const email = $email.val().trim();

                // لو الإيميل فاضي أو invalid، disable الزر
                if (email === '' || $email.hasClass('is-invalid')) {
                    $nextButton.prop('disabled', true).addClass('disabled');
                } else if ($email.hasClass('is-valid') || email === originalEmail) {
                    $nextButton.prop('disabled', false).removeClass('disabled');
                }
            }

            $('#email').on('keyup change paste', function() {
                clearTimeout(emailCheckTimeout);

                if (ajaxRequest && ajaxRequest.abort) {
                    ajaxRequest.abort();
                }

                const email = $(this).val().trim();
                const $emailFeedback = $('#email-feedback');

                $emailFeedback.remove();
                $(this).removeClass('is-valid is-invalid');

                // تحديث حالة الزر
                updateButtonState();

                if (email === '') {
                    return;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    $(this).after(
                        '<div id="email-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>صيغة البريد الإلكتروني غير صحيحة</div>'
                    );
                    $(this).addClass('is-invalid');
                    updateButtonState();
                    return;
                }

                if (email === originalEmail) {
                    $(this).after(
                        '<div id="email-feedback" class="valid-feedback d-block"><i class="ti ti-check me-1"></i>البريد الإلكتروني الحالي</div>'
                    );
                    $(this).addClass('is-valid');
                    updateButtonState();
                    return;
                }

                emailCheckTimeout = setTimeout(function() {
                    $('#email').after(
                        '<div id="email-feedback" class="form-text text-muted"><i class="ti ti-loader ti-spin me-1"></i>جاري التحقق...</div>'
                    );

                    // disable الزر أثناء التحقق
                    $nextButton.prop('disabled', true).addClass('disabled');

                    ajaxRequest = $.ajax({
                        url: "{{ route('admin.users.check-email-unique') }}",
                        method: 'POST',
                        data: {
                            email: email,
                            user_id: "{{ $user->id }}",
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            $('#email-feedback').remove();

                            if (response.available) {
                                $('#email').addClass('is-valid').removeClass(
                                    'is-invalid');
                                $('#email').after(
                                    '<div id="email-feedback" class="valid-feedback d-block"><i class="ti ti-check me-1"></i>' +
                                    response.message + '</div>');
                            } else {
                                $('#email').addClass('is-invalid').removeClass(
                                    'is-valid');
                                $('#email').after(
                                    '<div id="email-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>' +
                                    response.message + '</div>');
                            }

                            // تحديث حالة الزر بعد النتيجة
                            updateButtonState();
                        },
                        error: function(xhr) {
                            if (xhr.statusText === 'abort') {
                                return;
                            }

                            $('#email-feedback').remove();
                            $('#email').addClass('is-invalid').removeClass('is-valid');
                            $('#email').after(
                                '<div id="email-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>حدث خطأ أثناء التحقق</div>'
                            );

                            updateButtonState();
                        },
                        complete: function() {
                            ajaxRequest = null;
                        }
                    });
                }, 1000);
            });

            // منع الانتقال للخطوة التالية لو الإيميل invalid
            $nextButton.on('click', function(e) {
                const $email = $('#email');

                if ($email.hasClass('is-invalid')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    toastr.error('يرجى إدخال بريد إلكتروني صحيح ومتاح');
                    return false;
                }
            });

            // منع الإرسال لو الإيميل مش متاح
            $('#wizard-validation-form').on('submit', function(e) {
                if ($('#email').hasClass('is-invalid')) {
                    e.preventDefault();
                    toastr.error('يرجى إدخال بريد إلكتروني صحيح ومتاح');
                    return false;
                }
            });

            // التحقق الأولي عند تحميل الصفحة
            updateButtonState();
        });
    </script>

@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">البيانات الشخصية والاتصال</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#security-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">كلمة المرور والصورة</span>
                                <span class="bs-stepper-subtitle">الأمان والصورة الشخصية</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ route('admin.users.update', $user) }}" method="POST"
                        onSubmit="return false" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- المعلومات الأساسية -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">الاسم الكامل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="ادخل الاسم الكامل" value="{{ old('name', $user->name) }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="email">البريد الإلكتروني <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="ادخل البريد الإلكتروني" value="{{ old('email', $user->email) }}" />
                                    {{-- رسالة التحقق ستظهر هنا --}}
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="phone">رقم الهاتف <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                        placeholder="ادخل رقم الهاتف" value="{{ old('phone', $user->phone) }}" />
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-right ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- كلمة المرور والصورة -->
                        <div id="security-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="password">كلمة المرور</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="اتركه فارغاً لعدم التغيير" />
                                        <span class="input-group-text cursor-pointer">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="form-control" placeholder="أعد إدخال كلمة المرور" />
                                        <span class="input-group-text cursor-pointer">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">الصورة الشخصية</label>
                                        <div
                                            class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
                                            <img src="{{ $user->image ? asset('storage/' . $user->image) : asset('img/logo.svg') }}"
                                                alt="user-avatar" class="d-block w-px-100 h-px-100 rounded"
                                                id="uploadedAvatar" />
                                            <div class="button-wrapper">
                                                <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                                                    <span class="d-none d-sm-block">تحديد صورة جديدة</span>
                                                    <i class="ti ti-upload d-block d-sm-none"></i>
                                                    <input type="file" id="upload" name="image"
                                                        class="account-file-input" hidden
                                                        accept="image/png, image/jpeg, image/jpg, image/gif" />
                                                </label>
                                                <div class="text-muted small">JPG، PNG أو GIF. الحد الأقصى 2MB</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-right ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next btn-submit">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">تحديث</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
