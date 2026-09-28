@extends('tenants.layouts.layoutMaster')

@section('title', 'نقل دومين')
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@php
    $extensions = tenancy()->central(function() {
        return App\Models\Admin\ControlDomain\ControlDomain::where('is_active', 1)->get();
    });

    $domainPrice = 100;
    
    if (isset($domain) && !empty($domain)) {
        $domainParts = explode('.', trim($domain));
        $extension = strtolower(end($domainParts));
        
        $priceData = tenancy()->central(function() use ($extension) {
            return App\Models\Admin\ControlDomain\ControlDomain::where(function($query) use ($extension) {
                $query->where('domain_extension', $extension)
                      ->orWhere('domain_extension', '.' . $extension);
            })
            ->where('is_active', 1)
            ->first();
        });
        
        if ($priceData && isset($priceData->price) && $priceData->price > 0) {
            $domainPrice = $priceData->price;
        }
    }
    
    $amountInHalalas = $domainPrice * 100;
@endphp

@section('page-style')
    @vite(['resources/scss/tenants/pages/domain-transfer.scss'])
@endsection

@section('content')
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text" id="loadingText">جاري المعالجة...</div>
            <div class="loading-subtext" id="loadingSubtext">يرجى الانتظار</div>
        </div>
    </div>

    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Progress Steps -->
        <div class="steps-wrapper fade-in">
            <div class="progress-steps">
                <!-- Step 1 -->
                <div class="progress-step">
                    <div class="step-circle {{ $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' }}">
                        {{ $step > 1 ? '' : '1' }}
                    </div>
                    <small class="step-text">التحقق من الدومين</small>
                </div>

                <div class="step-line {{ $step >= 2 ? 'active' : '' }}"></div>

                <!-- Step 2 -->
                <div class="progress-step">
                    <div class="step-circle {{ $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' }}">
                        {{ $step > 2 ? '' : '2' }}
                    </div>
                    <small class="step-text">كود EPP</small>
                </div>

                <div class="step-line {{ $step >= 3 ? 'active' : '' }}"></div>

                <!-- Step 3 -->
                <div class="progress-step">
                    <div class="step-circle {{ $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' }}">
                        {{ $step > 3 ? '' : '3' }}
                    </div>
                    <small class="step-text">عملية الدفع</small>
                </div>

                <div class="step-line {{ $step >= 4 ? 'active' : '' }}"></div>

                <!-- Step 4 -->
                <div class="progress-step">
                    <div class="step-circle {{ $step >= 4 ? 'active' : '' }}">
                        4
                    </div>
                    <small class="step-text">تأكيد النقل</small>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="main-card fade-in">
            
            <div class="card-body" style="padding:16px">

                <!-- Step 1: Check Domain -->
                @if ($step == 1)
                    <div class="row">
                        <div class="col-lg-8 col-md-10 mx-auto">
                            <div class="section-header">
                                <i class="ti ti-search"></i>
                                الخطوة الأولى: التحقق من الدومين
                            </div>
                            <p class="section-description">
                                أدخل اسم الدومين الذي تريد نقله للتأكد من أنه مسجل ومؤهل للنقل
                            </p>

                            <div id="alertContainer"></div>

                            <form id="step1Form">
                                @csrf
                                <div class="content-card">
                                    <div class="mb-3">
                                        <label for="domain" class="form-label">
                                            <i class="ti ti-world"></i>
                                            اسم الدومين
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="domain" name="domain" class="form-control"
                                            placeholder="example.com" dir="ltr" value="{{ old('domain') }}"
                                            required autofocus>
                                        <small class="form-text">مثال: example.com أو mywebsite.net</small>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="ti ti-info-circle" style="font-size: 18px; color: #0284c7;"></i>
                                        <div>
                                            <strong style="color: #0c4a6e;">متطلبات نقل الدومين:</strong>
                                            <ul class="mb-0">
                                                <li>الدومين مسجل ونشط حالياً</li>
                                                <li>مر أكثر من 60 يوم على تسجيله الأولي</li>
                                                <li>الدومين مفتوح للنقل (غير مقفول)</li>
                                                <li>لديك صلاحية الوصول لكود EPP/Auth</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="step1Btn">
                                        <i class="ti ti-search"></i>
                                        التحقق من الدومين
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Step 2: EPP Code -->
                @if ($step == 2)
                    <div class="row">
                        <div class="col-lg-8 col-md-10 mx-auto">
                            <div class="section-header">
                                <i class="ti ti-key"></i>
                                الخطوة الثانية: إدخال كود EPP
                            </div>
                            <p class="section-description">
                                أدخل كود النقل (EPP/Auth Code) الخاص بالدومين لإتمام عملية التحقق
                            </p>

                            <div class="domain-display-card">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <div class="domain-info">
                                            <div class="domain-icon">
                                                <i class="ti ti-world"></i>
                                            </div>
                                            <div class="domain-details">
                                                <h6>الدومين المراد نقله</h6>
                                                <div class="domain-name">{{ $domain }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="price-display">
                                            <div class="price-label">رسوم النقل المتوقعة</div>
                                            <div class="price-amount">{{ number_format($domainPrice, 2) }} ر.س</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ url('/' . tenant('id') . '/domains/transfer-dom-blade') }}" id="step2Form">
                                @csrf
                                <input type="hidden" name="step" value="3">
                                <input type="hidden" name="domain" value="{{ $domain }}">

                                <div class="content-card">
                                    <div class="mb-0">
                                        <label for="eppCode" class="form-label">
                                            <i class="ti ti-key"></i>
                                            كود EPP/Auth Code
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" id="eppCode" name="eppCode" class="form-control"
                                            placeholder="أدخل كود النقل هنا" dir="ltr" minlength="6"
                                            value="{{ old('eppCode', request('eppCode')) }}" required autofocus>
                                        <small class="form-text">
                                            كود النقل عادة يتكون من 8-16 حرف وأرقام
                                        </small>
                                    </div>
                                </div>

                                <div class="info-card warning">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="ti ti-alert-triangle" style="font-size: 18px; color: #b45309;"></i>
                                        <div>
                                            <strong style="color: #78350f;">كيف أحصل على كود EPP؟</strong>
                                            <ol style="margin: 8px 0 0 0; padding-right: 20px; font-size: 12px;">
                                                <li>سجل دخول إلى حساب المزود الحالي</li>
                                                <li>اذهب إلى قسم إدارة الدومين</li>
                                                <li>ابحث عن "Transfer" أو "Auth Code" أو "EPP Code"</li>
                                                <li>انسخ الكود وأدخله هنا</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary" id="step2Btn">
                                        <i class="ti ti-arrow-left"></i>
                                        متابعة للدفع
                                    </button>
                                    <a href="{{ url('/' . tenant('id') . '/domains/transfer-dom-blade') }}"
                                        class="btn btn-label-secondary">
                                        <i class="ti ti-arrow-right"></i>
                                        رجوع
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Step 3: Payment -->
                @if ($step == 3)
                    <div class="row">
                        <div class="col-lg-8 col-md-10 mx-auto">
                            <div class="section-header">
                                <i class="ti ti-credit-card"></i>
                                الخطوة الثالثة: عملية الدفع
                            </div>
                            <p class="section-description">
                                أدخل بيانات بطاقتك بشكل آمن لإتمام عملية نقل الدومين
                            </p>

                            <div class="domain-display-card">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <div class="domain-info">
                                            <div class="domain-icon">
                                                <i class="ti ti-world"></i>
                                            </div>
                                            <div class="domain-details">
                                                <h6>الدومين</h6>
                                                <div class="domain-name">{{ $domain }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="price-display">
                                            <div class="price-label">رسوم النقل</div>
                                            <div class="price-amount">{{ number_format($domainPrice, 2) }} ر.س</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-info-circle" style="font-size: 18px; color: #0284c7;"></i>
                                    <div>
                                        <strong style="color: #0c4a6e;">ملاحظات هامة:</strong>
                                        <ul class="mb-0">
                                            <li>رسوم النقل تشمل تجديد الدومين لمدة سنة إضافية</li>
                                            <li>عملية النقل قد تستغرق من 5-7 أيام عمل</li>
                                            <li>السعر النهائي: <strong>{{ number_format($domainPrice, 2) }} ريال</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ tenant_route_name('domains.process-transfer-payment') }}" id="paymentForm">
                                @csrf
                                <input type="hidden" name="domain" value="{{ $domain }}">
                                <input type="hidden" name="eppCode" value="{{ request('eppCode') }}">
                                <input type="hidden" name="amount" value="{{ $amountInHalalas }}">

                                <div class="content-card">
                                    <div class="section-header" style="font-size: 14px; margin-bottom: 20px;">
                                        <i class="ti ti-credit-card"></i>
                                        معلومات البطاقة
                                    </div>

                                    <!-- Card Number -->
                                    <div class="mb-3">
                                        <label for="cardNumber" class="form-label">
                                            <i class="ti ti-credit-card"></i>
                                            رقم البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="cardNumber"
                                                name="card_number" placeholder="1234 5678 9012 3456"
                                                dir="ltr" maxlength="19" required autocomplete="cc-number">
                                            <span class="input-group-text" id="cardTypeIcon">
                                                <i class="ti ti-credit-card"></i>
                                            </span>
                                        </div>
                                        <small class="form-text" id="cardTypeText">أدخل رقم البطاقة</small>
                                    </div>

                                    <!-- Card Holder Name -->
                                    <div class="mb-3">
                                        <label for="cardHolder" class="form-label">
                                            <i class="ti ti-user"></i>
                                            اسم حامل البطاقة
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="cardHolder"
                                            name="card_holder" placeholder="كما هو مكتوب على البطاقة"
                                            dir="ltr" required autocomplete="cc-name">
                                        <small class="form-text">الاسم باللغة الإنجليزية كما هو على البطاقة</small>
                                    </div>

                                    <div class="row">
                                        <!-- Expiry Date -->
                                        <div class="col-md-6 mb-3">
                                            <label for="expiryDate" class="form-label">
                                                <i class="ti ti-calendar"></i>
                                                تاريخ الانتهاء
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="expiryDate"
                                                name="expiry_date" placeholder="MM/YY" dir="ltr"
                                                maxlength="5" required autocomplete="cc-exp">
                                            <small class="form-text">مثال: 12/25</small>
                                        </div>

                                        <!-- CVC -->
                                        <div class="col-md-6 mb-3">
                                            <label for="cvc" class="form-label">
                                                <i class="ti ti-lock"></i>
                                                CVC/CVV
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="cvc"
                                                name="cvc" placeholder="123" dir="ltr"
                                                maxlength="4" required autocomplete="cc-csc">
                                            <small class="form-text">الرقم خلف البطاقة (3-4 أرقام)</small>
                                        </div>
                                    </div>

                                    <!-- Security Info -->
                                    <div class="d-flex align-items-start gap-2 p-3 rounded" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                        <i class="ti ti-shield-check" style="font-size: 18px; color: #15803d;"></i>
                                        <div style="font-size: 12px; color: #14532d;">
                                            <strong>دفع آمن ومشفر بنسبة 100%</strong><br>
                                            جميع معلومات الدفع محمية بتقنية التشفير SSL/TLS
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary" id="paymentBtn">
                                        <i class="ti ti-credit-card"></i>
                                        إتمام الدفع ({{ number_format($domainPrice, 2) }} ر.س)
                                    </button>
                                    <a href="{{ url('/' . tenant('id') . '/domains/transfer-dom-blade?step=2&domain=' . $domain . '&eppCode=' . request('eppCode')) }}"
                                        class="btn btn-label-secondary">
                                        <i class="ti ti-arrow-right"></i>
                                        رجوع
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <!-- Step 4: Success -->
                @if ($step == 4)
                    <div class="row">
                        <div class="col-lg-8 col-md-10 mx-auto text-center">
                            <div class="success-icon">
                                <i class="ti ti-circle-check"></i>
                            </div>

                            <h4 style="font-size: 20px; font-weight: 700; color: #372632; margin-bottom: 12px;">
                                تم إرسال طلب النقل بنجاح!
                            </h4>

                            <p style="font-size: 13px; color: #6b7280; margin-bottom: 24px;">
                                سيتم معالجة طلبك في أقرب وقت ممكن
                            </p>

                            <div class="info-card d-inline-flex align-items-center justify-content-center mb-4" style="max-width: 400px;">
                                <i class="ti ti-clock" style="font-size: 20px; color: #0284c7;"></i>
                                <span style="margin-right: 8px; font-weight: 600; color: #0c4a6e;">
                                    عملية النقل قد تستغرق من 5 إلى 7 أيام عمل
                                </span>
                            </div>

                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <a href="{{ url('/' . tenant('id') . '/dashboard') }}" class="btn btn-primary">
                                    <i class="ti ti-home"></i>
                                    العودة للوحة التحكم
                                </a>
                                <a href="{{ url('/' . tenant('id') . '/domains/transfer-dom-blade') }}" class="btn btn-label-secondary">
                                    <i class="ti ti-plus"></i>
                                    نقل دومين آخر
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

@section('page-script')
    <script>
        (function() {
            'use strict';

            const tenantId = '{{ tenant('id') }}';
            const csrfToken = '{{ csrf_token() }}';
            const domainPrice = {{ $domainPrice }};

            function showLoading(text, subtext) {
                const overlay = document.getElementById('loadingOverlay');
                const loadingText = document.getElementById('loadingText');
                const loadingSubtext = document.getElementById('loadingSubtext');

                if (overlay && loadingText && loadingSubtext) {
                    loadingText.textContent = text || 'جاري المعالجة...';
                    loadingSubtext.textContent = subtext || 'يرجى الانتظار';
                    overlay.classList.add('active');
                }
            }

            function hideLoading() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            }

            function showAlert(message, type = 'danger') {
                const alertContainer = document.getElementById('alertContainer');
                if (alertContainer) {
                    const icons = {
                        success: 'ti-circle-check',
                        danger: 'ti-alert-circle',
                        warning: 'ti-alert-triangle',
                        info: 'ti-info-circle'
                    };
                    
                    const alertHtml = `
                        <div class="alert alert-${type} alert-dismissible fade-in" style="margin-bottom: 20px;">
                            <i class="ti ${icons[type] || 'ti-info-circle'}" style="font-size: 18px;"></i>
                            <div style="flex: 1;">${message}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" style="padding: 0; margin-right: 8px;"></button>
                        </div>
                    `;
                    alertContainer.innerHTML = alertHtml;
                    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            // Step 1 Form
            const step1Form = document.getElementById('step1Form');
            if (step1Form) {
                step1Form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const domainInput = document.getElementById('domain');
                    const fullDomain = domainInput ? domainInput.value.trim() : '';

                    if (!fullDomain) {
                        showAlert('<strong>تنبيه:</strong> الرجاء إدخال اسم الدومين', 'warning');
                        return;
                    }

                    const domainRegex = /^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (!domainRegex.test(fullDomain)) {
                        showAlert('<strong>خطأ:</strong> صيغة الدومين غير صحيحة. يرجى التحقق من الاسم المدخل', 'danger');
                        return;
                    }

                    showLoading('جاري التحقق من الدومين...', 'يرجى الانتظار بضع ثوان');

                    const baseUrl = "{{ tenant_route_name('domains.checkExistTransferDomain', ['domain' => ':domain']) }}";
                    const finalUrl = baseUrl.replace(':domain', fullDomain);

                    $.ajax({
                        url: finalUrl,
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            domain: fullDomain
                        },
                        success: function(response) {
                            hideLoading();

                            if (response.available === true) {
                                showAlert(
                                    '<strong>❌ الدومين غير مسجل</strong><br><small>الدومين متاح للحجز ولا يمكن نقله. يمكنك حجزه كدومين جديد.</small>',
                                    'danger'
                                );
                            } else if (response.available === false) {
                                showAlert(
                                    '<strong>✅ الدومين مسجل ومتاح للنقل</strong><br><small>جاري التحويل للخطوة التالية...</small>',
                                    'success'
                                );

                                setTimeout(() => {
                                    window.location.href = `/${tenantId}/domains/transfer-dom-blade?step=2&domain=${encodeURIComponent(fullDomain)}`;
                                }, 1500);
                            } else if(response.status == 'exists'){
                                showAlert('<strong>تنبيه:</strong> هذا الدومين منقول بالفعل إلى حسابك', 'warning');
                                return;
                            } else {
                                showAlert('<strong>خطأ:</strong> لا يمكن التحقق من هذا الدومين. يرجى المحاولة لاحقاً', 'warning');
                                return;
                            }
                        },
                        error: function(xhr) {
                            hideLoading();
                            const errorMsg = xhr.responseJSON?.message || 'حدث خطأ أثناء البحث. يرجى المحاولة مرة أخرى';
                            showAlert('<strong>خطأ:</strong> ' + errorMsg, 'danger');
                        }
                    });
                });
            }

            // Step 2 Form
            const step2Form = document.getElementById('step2Form');
            if (step2Form) {
                step2Form.addEventListener('submit', function(e) {
                    const eppCode = document.getElementById('eppCode');
                    if (eppCode && eppCode.value.trim().length < 6) {
                        e.preventDefault();
                        showAlert('<strong>تنبيه:</strong> كود EPP يجب أن يكون 6 أحرف على الأقل', 'warning');
                        return;
                    }
                    showLoading('جاري التحقق من كود EPP...', 'يرجى الانتظار');
                });
            }

            // Card Number Formatting & Detection
            const cardNumberInput = document.getElementById('cardNumber');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    e.target.value = formattedValue;

                    const cardTypeIcon = document.getElementById('cardTypeIcon');
                    const cardTypeText = document.getElementById('cardTypeText');

                    if (value.length >= 4) {
                        const firstDigit = value[0];
                        const firstTwoDigits = value.substring(0, 2);
                        const firstFourDigits = value.substring(0, 4);

                        if (firstDigit === '4') {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card" style="color: #1434CB;"></i>';
                            cardTypeText.innerHTML = '<span style="color: #1434CB; font-weight: 600;">بطاقة Visa</span>';
                        } else if (firstTwoDigits >= '51' && firstTwoDigits <= '55') {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card" style="color: #EB001B;"></i>';
                            cardTypeText.innerHTML = '<span style="color: #EB001B; font-weight: 600;">بطاقة Mastercard</span>';
                        } else if (firstTwoDigits === '34' || firstTwoDigits === '37') {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card" style="color: #006FCF;"></i>';
                            cardTypeText.innerHTML = '<span style="color: #006FCF; font-weight: 600;">بطاقة American Express</span>';
                        } else if (firstFourDigits >= '2221' && firstFourDigits <= '2720') {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card" style="color: #EB001B;"></i>';
                            cardTypeText.innerHTML = '<span style="color: #EB001B; font-weight: 600;">بطاقة Mastercard</span>';
                        } else if (firstTwoDigits === '60' || firstTwoDigits === '65') {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card" style="color: #FF6000;"></i>';
                            cardTypeText.innerHTML = '<span style="color: #FF6000; font-weight: 600;">بطاقة Discover</span>';
                        } else {
                            cardTypeIcon.innerHTML = '<i class="ti ti-credit-card"></i>';
                            cardTypeText.innerHTML = 'أدخل رقم البطاقة';
                        }
                    } else {
                        cardTypeIcon.innerHTML = '<i class="ti ti-credit-card"></i>';
                        cardTypeText.innerHTML = 'أدخل رقم البطاقة';
                    }
                });

                cardNumberInput.addEventListener('keypress', function(e) {
                    if (!/[\d\s]/.test(e.key) && e.key !== 'Backspace') {
                        e.preventDefault();
                    }
                });
            }

            // Expiry Date Formatting
            const expiryDateInput = document.getElementById('expiryDate');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });

                expiryDateInput.addEventListener('keypress', function(e) {
                    if (!/\d/.test(e.key) && e.key !== 'Backspace') {
                        e.preventDefault();
                    }
                });
            }

            // CVC - Numbers only
            const cvcInput = document.getElementById('cvc');
            if (cvcInput) {
                cvcInput.addEventListener('keypress', function(e) {
                    if (!/\d/.test(e.key) && e.key !== 'Backspace') {
                        e.preventDefault();
                    }
                });
            }

            // Payment Form
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const cardNumber = document.getElementById('cardNumber');
                    const cardNumberValue = cardNumber.value.replace(/\s/g, '');
                    if (cardNumberValue.length < 13 || cardNumberValue.length > 19) {
                        alert('⚠️ رقم البطاقة غير صحيح. يرجى التحقق من الرقم المدخل');
                        cardNumber.focus();
                        return;
                    }

                    const expiryDate = document.getElementById('expiryDate');
                    const expiryParts = expiryDate.value.split('/');
                    if (expiryParts.length !== 2 || expiryParts[0].length !== 2 || expiryParts[1].length !== 2) {
                        alert('⚠️ تاريخ الانتهاء غير صحيح. الصيغة الصحيحة: MM/YY');
                        expiryDate.focus();
                        return;
                    }

                    const month = parseInt(expiryParts[0]);
                    if (month < 1 || month > 12) {
                        alert('⚠️ الشهر غير صحيح. يجب أن يكون بين 01 و 12');
                        expiryDate.focus();
                        return;
                    }

                    const cvc = document.getElementById('cvc');
                    if (cvc.value.length < 3 || cvc.value.length > 4) {
                        alert('⚠️ رقم CVC غير صحيح. يجب أن يكون 3 أو 4 أرقام');
                        cvc.focus();
                        return;
                    }

                    showLoading('جاري معالجة الدفع...', 'يرجى الانتظار، لا تغلق الصفحة');
                    this.submit();
                });
            }

            // Page Load
            window.addEventListener('load', function() {
                setTimeout(() => hideLoading(), 300);
            });

            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    hideLoading();
                }
            });
        })();
    </script>
@endsection
@endsection