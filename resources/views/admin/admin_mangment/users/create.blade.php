@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة مستخدم جديد')

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
            let nameCheckTimeout, emailCheckTimeout, phoneCheckTimeout;
            let nameAjax = null,
                emailAjax = null,
                phoneAjax = null;
            const $nextButton = $('#basic-info-validation .btn-next');

            // متغيرات لحالة كل حقل
            let validationState = {
                name: false,
                email: false,
                phone: false
            };

            /**
             * دالة لتحديث حالة زر التالي
             */
            function updateButtonState() {
                const allValid = validationState.name && validationState.email && validationState.phone;

                if (allValid) {
                    $nextButton.prop('disabled', false).removeClass('disabled');
                } else {
                    $nextButton.prop('disabled', true).addClass('disabled');
                }
            }

            /**
             * دالة عامة للتحقق من الحقول
             */
            function validateField(fieldName, value, route, regex = null, errorMsg = null) {
                const $field = $(`#${fieldName}`);
                const $feedback = $(`#${fieldName}-feedback`);

                $feedback.remove();
                $field.removeClass('is-valid is-invalid');
                validationState[fieldName] = false;
                updateButtonState();

                if (value.trim() === '') {
                    return;
                }

                if (regex && !regex.test(value)) {
                    $field.after(
                        `<div id="${fieldName}-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>${errorMsg}</div>`
                    );
                    $field.addClass('is-invalid');
                    updateButtonState();
                    return;
                }

                $field.after(
                    `<div id="${fieldName}-feedback" class="form-text text-muted"><i class="ti ti-loader ti-spin me-1"></i>جاري التحقق...</div>`
                );

                if (fieldName === 'name' && nameAjax) nameAjax.abort();
                if (fieldName === 'email' && emailAjax) emailAjax.abort();
                if (fieldName === 'phone' && phoneAjax) phoneAjax.abort();

                const ajaxRequest = $.ajax({
                    url: route,
                    method: 'POST',
                    data: {
                        [fieldName]: value,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $(`#${fieldName}-feedback`).remove();

                        if (response.available) {
                            $field.addClass('is-valid').removeClass('is-invalid');
                            $field.after(
                                `<div id="${fieldName}-feedback" class="valid-feedback d-block"><i class="ti ti-check me-1"></i>${response.message}</div>`
                            );
                            validationState[fieldName] = true;
                        } else {
                            $field.addClass('is-invalid').removeClass('is-valid');
                            $field.after(
                                `<div id="${fieldName}-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>${response.message}</div>`
                            );
                            validationState[fieldName] = false;
                        }

                        updateButtonState();
                    },
                    error: function(xhr) {
                        if (xhr.statusText === 'abort') return;

                        $(`#${fieldName}-feedback`).remove();
                        $field.addClass('is-invalid').removeClass('is-valid');
                        $field.after(
                            `<div id="${fieldName}-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>حدث خطأ أثناء التحقق</div>`
                        );
                        validationState[fieldName] = false;
                        updateButtonState();
                    },
                    complete: function() {
                        if (fieldName === 'name') nameAjax = null;
                        if (fieldName === 'email') emailAjax = null;
                        if (fieldName === 'phone') phoneAjax = null;
                    }
                });

                if (fieldName === 'name') nameAjax = ajaxRequest;
                if (fieldName === 'email') emailAjax = ajaxRequest;
                if (fieldName === 'phone') phoneAjax = ajaxRequest;
            }

            // التحقق من الاسم
            $('#name').on('change keyup paste', function() {
                clearTimeout(nameCheckTimeout);
                const name = $(this).val().trim();

                nameCheckTimeout = setTimeout(function() {
                    if (name.length < 3) {
                        $('#name-feedback').remove();
                        $('#name').removeClass('is-valid is-invalid');
                        if (name.length > 0 && name.length < 3) {
                            $('#name').after(
                                '<div id="name-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>الاسم يجب أن يكون 3 أحرف على الأقل</div>'
                            );
                            $('#name').addClass('is-invalid');
                        }
                        validationState.name = false;
                        updateButtonState();
                        return;
                    }

                    validateField('name', name, "{{ route('admin.users.check-name-unique') }}");
                }, 1000);
            });

            // التحقق من البريد الإلكتروني
            $('#email').on('change keyup paste', function() {
                clearTimeout(emailCheckTimeout);
                const email = $(this).val().trim();

                emailCheckTimeout = setTimeout(function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    validateField(
                        'email',
                        email,
                        "{{ route('admin.users.check-email-unique') }}",
                        emailRegex,
                        'صيغة البريد الإلكتروني غير صحيحة'
                    );
                }, 1000);
            });

            // التحقق من رقم الهاتف
            $('#phone').on('change keyup paste', function() {
                clearTimeout(phoneCheckTimeout);
                const phone = $(this).val().trim();

                phoneCheckTimeout = setTimeout(function() {
                    const phoneRegex = /^[0-9]{10,15}$/;
                    validateField(
                        'phone',
                        phone,
                        "{{ route('admin.users.check-phone-unique') }}",
                        phoneRegex,
                        'رقم الهاتف غير صحيح (يجب أن يكون 10-15 رقم)'
                    );
                }, 1000);
            });

            // ==========================================
            // التحقق من الصورة (الحجم والأبعاد)
            // ==========================================
            $('#upload').on('change', function(e) {
                const file = e.target.files[0];
                const $uploadFeedback = $('#upload-feedback');

                // مسح أي رسائل سابقة
                $uploadFeedback.remove();

                if (!file) {
                    return;
                }

                // التحقق من نوع الملف
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    $(this).val('');
                    $('.button-wrapper').append(
                        '<div id="upload-feedback" class="invalid-feedback d-block mt-2"><i class="ti ti-x me-1"></i>نوع الملف غير مدعوم. يرجى اختيار صورة JPG أو PNG أو GIF</div>'
                    );
                    toastr.error('نوع الملف غير مدعوم');
                    return;
                }

                // التحقق من حجم الملف (2MB = 2 * 1024 * 1024 bytes)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    $(this).val('');
                    $('.button-wrapper').append(
                        '<div id="upload-feedback" class="invalid-feedback d-block mt-2"><i class="ti ti-x me-1"></i>حجم الصورة كبير جداً. الحد الأقصى 2MB</div>'
                    );
                    toastr.error('حجم الصورة يتجاوز 2MB');
                    return;
                }

                // التحقق من أبعاد الصورة
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        const width = this.width;
                        const height = this.height;

                        // التحقق من الأبعاد: يجب أن تكون 2000x2000 أو 100x100 أو أصغر
                        const maxDimension = 2000;

                        if (width > maxDimension || height > maxDimension) {
                            $('#upload').val('');
                            $('#upload-feedback').remove();
                            $('.button-wrapper').append(
                                '<div id="upload-feedback" class="invalid-feedback d-block mt-2"><i class="ti ti-x me-1"></i>أبعاد الصورة كبيرة جداً. الحد الأقصى 2000×2000 بكسل</div>'
                            );
                            toastr.error(
                                `أبعاد الصورة ${width}×${height} تتجاوز الحد المسموح (2000×2000)`
                            );
                            return;
                        }

                        // إذا كانت الصورة مطابقة للمواصفات، عرضها
                        $('#uploadedAvatar').attr('src', event.target.result);
                        $('#upload-feedback').remove();
                        $('.button-wrapper').append(
                            '<div id="upload-feedback" class="valid-feedback d-block mt-2"><i class="ti ti-check me-1"></i>الصورة مقبولة (' +
                            (file.size / 1024).toFixed(2) + ' KB، ' + width + '×' + height +
                            ' بكسل)</div>');
                        toastr.success('تم تحميل الصورة بنجاح');
                    };

                    img.onerror = function() {
                        $('#upload').val('');
                        $('#upload-feedback').remove();
                        $('.button-wrapper').append(
                            '<div id="upload-feedback" class="invalid-feedback d-block mt-2"><i class="ti ti-x me-1"></i>فشل تحميل الصورة. يرجى المحاولة مرة أخرى</div>'
                        );
                        toastr.error('فشل تحميل الصورة');
                    };

                    img.src = event.target.result;
                };

                reader.onerror = function() {
                    $('#upload').val('');
                    toastr.error('حدث خطأ أثناء قراءة الملف');
                };

                reader.readAsDataURL(file);
            });

            // منع الانتقال للخطوة التالية
            $nextButton.on('click', function(e) {
                if (!validationState.name || !validationState.email || !validationState.phone) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    let errorMsg = 'يرجى التأكد من:';
                    if (!validationState.name) errorMsg += '\n- الاسم صحيح ومتاح';
                    if (!validationState.email) errorMsg += '\n- البريد الإلكتروني صحيح ومتاح';
                    if (!validationState.phone) errorMsg += '\n- رقم الهاتف صحيح ومتاح';

                    toastr.error(errorMsg);
                    return false;
                }
            });

            // منع إرسال النموذج
            $('#wizard-validation-form').on('submit', function(e) {
                if (!validationState.name || !validationState.email || !validationState.phone) {
                    e.preventDefault();
                    toastr.error('يرجى التأكد من صحة جميع البيانات');
                    return false;
                }
            });

            // التحقق الأولي
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

                    <form id="wizard-validation-form" action="{{ route('admin.users.store') }}" method="POST"
                        onSubmit="return false" enctype="multipart/form-data">
                        @csrf

                        <!-- المعلومات الأساسية -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">الاسم الكامل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="ادخل الاسم الكامل" value="{{ old('name') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="email">البريد الإلكتروني <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="ادخل البريد الإلكتروني" value="{{ old('email') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="phone">رقم الهاتف <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                        placeholder="ادخل رقم الهاتف" value="{{ old('phone') }}" />
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light"
                                        disabled>
                                        <i class="ti ti-arrow-left me-1"></i> السابق
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                            class="btn btn-secondary btn-save-draft waves-effect waves-light"
                                            data-step="1">
                                            <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                        </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next waves-effect waves-light"
                                            disabled>
                                            التالي <i class="ti ti-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- كلمة المرور والصورة -->
                        <div id="security-info-validation" class="content">
                            <div class="row g-3">

                                <div class="col-sm-6">
                                    <label class="form-label" for="password">كلمة المرور <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="ادخل كلمة المرور" />
                                        <span class="input-group-text cursor-pointer">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="password_confirmation">تأكيد كلمة المرور <span
                                            class="text-danger">*</span></label>
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
                                            <img src="{{ asset('img/logo.svg') }}" alt="user-avatar"
                                                class="d-block w-px-100 h-px-100 rounded" id="uploadedAvatar" />
                                            <div class="button-wrapper">
                                                <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                                                    <span class="d-none d-sm-block">تحديد صورة</span>
                                                    <i class="ti ti-upload d-block d-sm-none"></i>
                                                    <input type="file" id="upload" name="image"
                                                        class="account-file-input" hidden
                                                        accept="image/png, image/jpeg, image/jpg, image/gif" />
                                                </label>
                                                <div class="text-muted small">JPG، PNG أو GIF. الحد الأقصى 2MB (أبعاد:
                                                    2000×2000 بكسل)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev waves-effect waves-light">
                                        <i class="ti ti-arrow-left me-1"></i> السابق
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                            class="btn btn-secondary btn-save-draft waves-effect waves-light"
                                            data-step="2">
                                            <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                        </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit"
                                            class="btn btn-primary btn-next btn-submit waves-effect waves-light">
                                            <i class="ti ti-check me-1"></i> حفظ
                                        </button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
