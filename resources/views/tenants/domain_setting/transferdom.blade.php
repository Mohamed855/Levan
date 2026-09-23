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

<style>
   

    body {
        background: #f5f5f9;
    }

    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(47, 51, 73, 0.9);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-content {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        max-width: 320px;
        animation: fadeInScale 0.3s ease;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e5e7eb;
        border-top: 4px solid #372632;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-text {
        font-size: 14px;
        font-weight: 600;
        color: #372632;
        margin-bottom: 6px;
    }

    .loading-subtext {
        font-size: 12px;
        color: #6b7280;
    }

    /* Main Container */
    .container-xxl {
        max-width: 1200px;
    }

    /* Progress Steps */
    .steps-wrapper {
        background: linear-gradient(135deg, #372632 0%, #3d4152 100%);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 16px rgba(47, 51, 73, 0.15);
    }

    .progress-steps {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 700px;
        margin: 0 auto;
    }

    .progress-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        position: relative;
        z-index: 2;
    }

    .step-circle.active {
        background: #10b981;
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        transform: scale(1.1);
    }

    .step-circle.completed {
        background: #fff;
        color: #10b981;
        border-color: #fff;
    }

    .step-circle.completed::after {
        content: "✓";
        position: absolute;
        font-size: 18px;
        font-weight: bold;
    }

    .step-line {
        flex-grow: 1;
        height: 2px;
        background: rgba(255, 255, 255, 0.2);
        margin: 0 10px;
        position: relative;
        z-index: 1;
    }

    .step-line.active {
        background: #10b981;
    }

    .step-text {
        margin-top: 8px;
        font-size: 11px;
        text-align: center;
        line-height: 1.3;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
    }

    .step-circle.active ~ .step-text,
    .step-circle.completed ~ .step-text {
        color: #fff;
        font-weight: 600;
    }

    /* Cards */
    .main-card {
        background: #fff;
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #f9fafb 0%, #fff 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 20px 24px;
    }

    .card-header h5 {
        font-size: 16px;
        font-weight: 700;
        color: #372632;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-body {
        padding: 30px 24px;
    }

    /* Content Cards */
    .content-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .content-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        border-color: #372632;
    }

    .info-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .info-card.warning {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        border-color: #fde68a;
    }

    .info-card.success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #bbf7d0;
    }

    .domain-display-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f1f4 100%);
        border: 2px solid #e0e2e7;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .domain-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .domain-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #372632 0%, #3d4152 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .domain-details h6 {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .domain-name {
        font-size: 18px;
        font-weight: 700;
        color: #372632;
        margin: 0;
    }

    .price-display {
        text-align: center;
        padding: 12px;
        background: #fff;
        border-radius: 8px;
        border: 2px dashed #e5e7eb;
    }

    .price-label {
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .price-amount {
        font-size: 19px;
        font-weight: 800;
         margin: 0;
    }

    /* Forms */
    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-control {
        font-size: 13px;
        padding: 10px 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        transition: all 0.2s;
        background: #fff;
    }

    .form-control:focus {
        border-color: #372632;
        box-shadow: 0 0 0 4px rgba(47, 51, 73, 0.1);
        background: #fff;
    }

    .form-control::placeholder {
        color: #9ca3af;
    }

    .form-text {
        font-size: 11px;
        margin-top: 6px;
        color: #6b7280;
    }

    .input-group {
        border-radius: 8px;
        overflow: hidden;
    }

    .input-group-text {
        background: #f9fafb;
        border: 1.5px solid #d1d5db;
        font-size: 14px;
        padding: 10px 14px;
    }

    /* Buttons */
    .btn {
        font-size: 13px;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #372632 0%, #3d4152 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(47, 51, 73, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #3d4152 0%, #4a4d5f 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(47, 51, 73, 0.4);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .btn-label-secondary {
        background: #f3f4f6;
        color: #6b7280;
        border: 1.5px solid #e5e7eb;
    }

    .btn-label-secondary:hover {
        background: #e5e7eb;
        color: #4b5563;
        border-color: #d1d5db;
    }

    /* Alerts */
    .alert {
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 13px;
        border: none;
        display: flex;
        align-items: start;
        gap: 10px;
    }

    .alert-info {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
    }

    .alert-success {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
    }

    .alert-danger {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #991b1b;
    }

    .alert strong {
        font-weight: 700;
    }

    .alert ul {
        margin: 8px 0 0 0;
        padding-right: 20px;
        font-size: 12px;
    }

    .alert ul li {
        margin-bottom: 4px;
    }

    /* Icons */
    .ti {
        font-size: 16px;
    }

    .ti-lg {
        font-size: 22px;
    }

    /* Success Screen */
    .success-icon {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: successPulse 2s infinite;
    }

    @keyframes successPulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        50% {
            box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
        }
    }

    .success-icon i {
        font-size: 60px;
        color: white;
    }

    /* Section Headers */
    .section-header {
        font-size: 15px;
        font-weight: 700;
        color: #372632;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-description {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 24px;
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-body {
            padding: 20px 16px;
        }

        .steps-wrapper {
            padding: 20px 15px;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            font-size: 13px;
        }

        .step-text {
            font-size: 10px;
        }

        .domain-display-card {
            padding: 16px;
        }

        .price-amount {
            font-size: 20px;
        }

        .btn {
            font-size: 12px;
            padding: 9px 16px;
        }
    }

    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

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