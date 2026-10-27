@extends('tenant.layouts.layoutMaster')

@section('title', 'المصادقة الثنائية')


@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 ">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <span class="avatar-initial rounded bg-primary">
                                <i class="ti ti-shield-lock ti-md"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0">المصادقة الثنائية</h5>
                            <small class="text-muted">حماية إضافية لحسابك عبر تطبيق مصادقة</small>
                        </div>
                    </div>
                    <span id="statusBadge" class="badge {{ $enabled ? 'bg-success' : 'bg-secondary' }}">
                        {{ $enabled ? 'مفعّلة' : 'غير مفعّلة' }}
                    </span>
                </div>

                <div class="card-body">

                    {{-- حالة: غير مفعّلة --}}
                    <div id="disabledActions" class="{{ $enabled ? 'd-none' : '' }}">
                        <div class="alert alert-primary d-flex align-items-start mb-4" role="alert">
                            <i class="ti ti-info-circle ti-md me-2 mt-1"></i>
                            <div>
                                فعّل المصادقة الثنائية لإضافة خطوة تحقق عبر تطبيق مثل
                                <strong>Microsoft Authenticator</strong> أو <strong>Google Authenticator</strong>
                                بجانب كلمة المرور، لحماية أقوى لحسابك.
                            </div>
                        </div>
                        <button type="button" id="enable2faBtn" class="btn btn-primary">
                            <i class="ti ti-shield-plus me-1"></i>
                            تفعيل المصادقة الثنائية
                        </button>
                    </div>

                    {{-- حالة: مفعّلة --}}
                    <div id="enabledActions" class="{{ $enabled ? '' : 'd-none' }}">
                        <div class="alert alert-success d-flex align-items-start mb-4" role="alert">
                            <i class="ti ti-shield-check ti-md me-2 mt-1"></i>
                            <div>
                                حسابك محمي حاليًا بالمصادقة الثنائية. عند تسجيل الدخول سيُطلب منك
                                إدخال كود من تطبيق المصادقة الخاص بك.
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti ti-key text-warning me-2"></i>
                                        <span class="fw-medium">أكواد الاسترجاع</span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        استخدمها للدخول في حال فقدت الوصول لتطبيق المصادقة. كل كود يُستخدم مرة واحدة فقط.
                                    </p>
                                    <button type="button" id="showRecoveryBtn" class="btn btn-secondary btn-sm">
                                        <i class="ti ti-refresh me-1"></i>
                                        توليد أكواد جديدة
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ti ti-shield-x text-danger me-2"></i>
                                        <span class="fw-medium">إلغاء التفعيل</span>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        سيتم تعطيل خطوة التحقق الإضافية، وسيصبح الدخول بكلمة المرور فقط.
                                    </p>
                                    <button type="button" id="disable2faBtn" class="btn btn-danger btn-sm">
                                        إلغاء التفعيل
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="recoveryCodesWrapper" class="d-none mt-4">
                            <div class="alert alert-warning d-flex align-items-start mb-3">
                                <i class="ti ti-alert-triangle ti-md me-2 mt-1"></i>
                                <div>احفظ هذه الأكواد في مكان آمن الآن - لن تظهر مرة أخرى بعد مغادرة الصفحة.</div>
                            </div>
                            <div class="row row-cols-2 row-cols-sm-3 g-2" id="recoveryCodesList"></div>
                        </div>
                    </div>

                    {{-- لوحة إعداد المصادقة (QR) --}}
                    <div id="setupPanel" class="d-none mt-4">
                        <hr class="my-4">

                        <div class="row g-4 align-items-center">
                            <div class="col-12 col-md-5 text-center">
                                <div id="qrContainer" class="d-inline-block p-3 border rounded bg-white"></div>
                            </div>

                            <div class="col-12 col-md-7">
                                <div class="d-flex mb-3">
                                    <span class="badge bg-primary rounded-circle me-2"
                                        style="width:24px;height:24px;">1</span>
                                    <div>افتح تطبيق المصادقة على هاتفك (مثل Microsoft Authenticator)</div>
                                </div>
                                <div class="d-flex mb-3">
                                    <span class="badge bg-primary rounded-circle me-2"
                                        style="width:24px;height:24px;">2</span>
                                    <div>امسح رمز QR الظاهر بجانب هذا النص</div>
                                </div>
                                <div class="d-flex mb-4">
                                    <span class="badge bg-primary rounded-circle me-2"
                                        style="width:24px;height:24px;">3</span>
                                    <div>أدخل الكود المكوّن من 6 أرقام الظاهر في التطبيق</div>
                                </div>

                                <form id="twoFactorConfirmForm" class="row g-2 align-items-end"
                                    action="{{ tenant_route_name('tenants.two-factor.confirm') }}" method="POST" novalidate
                                    data-form-validate data-validation-submit="ajax"
                                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                                    @csrf
                                    <x-form.controls.input name="code" id="twoFactorCode" label="كود التحقق"
                                        type="text" placeholder="000000" required autocomplete="one-time-code"
                                        col="col-12 col-sm-7" maxlength="6" inputmode="numeric" />
                                    <div class="col-12 col-sm-5">
                                        <button type="submit" class="btn btn-success w-100">
                                            تأكيد التفعيل
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const $ = window.jQuery;

            const routes = {
                enable: "{{ tenant_route_name('tenants.two-factor.enable') }}",
                confirm: "{{ tenant_route_name('tenants.two-factor.confirm') }}",
                disable: "{{ tenant_route_name('tenants.two-factor.disable') }}",
                qrCode: "{{ tenant_route_name('tenants.two-factor.qr-code') }}",
                recoveryCodes: "{{ tenant_route_name('tenants.two-factor.recovery-codes') }}",
            };

            const statusBadge = $('#statusBadge');
            const enabledActions = $('#enabledActions');
            const disabledActions = $('#disabledActions');
            const setupPanel = $('#setupPanel');
            const recoveryCodesWrapper = $('#recoveryCodesWrapper');
            const recoveryCodesList = $('#recoveryCodesList');

            function markEnabled() {
                statusBadge.removeClass('bg-secondary').addClass('bg-success').text('مفعّلة');
                enabledActions.removeClass('d-none');
                disabledActions.addClass('d-none');
                setupPanel.addClass('d-none');
            }

            function markDisabled() {
                statusBadge.removeClass('bg-success').addClass('bg-secondary').text('غير مفعّلة');
                enabledActions.addClass('d-none');
                disabledActions.removeClass('d-none');
                setupPanel.addClass('d-none');
                recoveryCodesWrapper.addClass('d-none');
                recoveryCodesList.empty();
            }

            $('#enable2faBtn').on('click', function() {
                $.ajax({
                    url: routes.enable,
                    method: 'POST',
                    data: {
                        _token: csrfToken
                    },
                    success: function() {
                        setupPanel.removeClass('d-none');
                        $.get(routes.qrCode, function(res) {
                            $('#qrContainer').html(res.svg);
                        });
                    },
                    error: function() {
                        toastr.error('حدث خطأ أثناء تفعيل المصادقة الثنائية');
                    }
                });
            });

            $('#twoFactorConfirmForm').on('formValidationPassed', function() {
                const form = this;

                $.ajax({
                    url: routes.confirm,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function() {
                        toastr.success('تم تفعيل المصادقة الثنائية بنجاح');
                        markEnabled();

                        // عرض أكواد الاسترجاع تلقائيًا أول مرة بعد التفعيل مباشرة
                        $.ajax({
                            url: routes.recoveryCodes,
                            method: 'POST',
                            data: {
                                _token: csrfToken
                            },
                            success: function(codes) {
                                renderRecoveryCodes(codes);
                            }
                        });
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.errors?.code?.[0] ??
                            xhr.responseJSON?.message ?? 'الكود غير صحيح';
                        window.SharedFormValidation?.setFieldError(form, 'code', message);
                        toastr.error(message);
                    }
                });
            });

            $('#disable2faBtn').on('click', function() {
                window.confirmAction({
                    title: 'هل أنت متأكد؟',
                    text: 'سيتم إلغاء تفعيل المصادقة الثنائية لحسابك.',
                    variant: 'danger',
                    icon: 'ti-shield-off',
                    confirmText: 'إلغاء التفعيل',
                    confirmClass: 'btn btn-danger px-4',
                    cancelText: 'تراجع',
                    onConfirm: function() {
                        $.ajax({
                            url: routes.disable,
                            method: 'DELETE',
                            data: {
                                _token: csrfToken
                            },
                            success: function() {
                                toastr.success('تم إلغاء تفعيل المصادقة الثنائية');
                                markDisabled();
                            },
                            error: function() {
                                toastr.error('حدث خطأ أثناء إلغاء التفعيل');
                            }
                        });
                    }
                });
            });

            $('#showRecoveryBtn').on('click', function() {
                window.confirmAction({
                    title: 'توليد أكواد استرجاع جديدة؟',
                    text: 'سيتم إلغاء أي أكواد سابقة نهائيًا، واستبدالها بأكواد جديدة. احفظها فور ظهورها.',
                    variant: 'warning',
                    icon: 'ti-refresh',
                    confirmText: 'توليد الآن',
                    confirmClass: 'btn btn-primary px-4',
                    cancelText: 'تراجع',
                    onConfirm: function() {
                        $.ajax({
                            url: routes.recoveryCodes,
                            method: 'POST',
                            data: {
                                _token: csrfToken
                            },
                            success: function(codes) {
                                renderRecoveryCodes(codes);
                                toastr.warning(
                                    'احفظ هذه الأكواد الآن - لن تظهر مرة أخرى');
                            },
                            error: function() {
                                toastr.error('حدث خطأ أثناء توليد الأكواد');
                            }
                        });
                    }
                });
            });

            function renderRecoveryCodes(codes) {
                recoveryCodesList.empty();
                (codes || []).forEach(function(code) {
                    recoveryCodesList.append(
                        '<div class="col">' +
                        '<div class="border rounded px-2 py-1 text-center small">' +
                        code + '</div>' +
                        '</div>'
                    );
                });
                recoveryCodesWrapper.removeClass('d-none');
            }
        });
    </script>
@endsection
