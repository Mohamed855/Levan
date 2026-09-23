@extends('tenants.layouts.layoutMaster')

@section('title', 'ربط دومين')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])

    <style>
        .transfer-container {
            min-height: 100vh;
            padding: 60px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        }

        .transfer-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #282c3e 0%, #3d4152 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .card-header-custom h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .card-header-custom p {
            opacity: 0.9;
            font-size: 15px;
        }

        .card-body-custom {
            padding: 40px 30px;
        }

        .form-group-custom {
            margin-bottom: 25px;
        }

        .form-label-custom {
            font-weight: 600;
            color: #282c3e;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
        }

        .form-control-custom {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #282c3e;
            box-shadow: 0 0 0 3px rgba(40, 44, 62, 0.1);
        }

        .btn-check-domain {
            width: 100%;
            padding: 14px;
            background: #282c3e;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-check-domain:hover {
            background: #3d4152;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40, 44, 62, 0.3);
        }

        .btn-check-domain:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .btn-transfer {
            outline: none;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-transfer:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .alert-custom {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-success-custom {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error-custom {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-warning-custom {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .alert-info-custom {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .ip-highlight {
            background: #fff;
            border: 2px dashed #28a745;
            padding: 12px 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 700;
            color: #28a745;
            text-align: center;
            margin: 15px 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .btn-copy-ip {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-copy-ip:hover {
            background: #218838;
            transform: scale(1.05);
        }

        .btn-copy-ip.copied {
            background: #17a2b8;
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .guide-panel {
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        }

        .guide-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 18px 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border-bottom: 2px solid #e0e0e0;
        }

        .guide-header:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .guide-header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .guide-header-content i {
            font-size: 24px;
            color: #282c3e;
        }

        .guide-header-content h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #282c3e;
        }

        .guide-arrow {
            font-size: 20px;
            color: #6c757d;
            transition: transform 0.3s ease;
        }

        .guide-arrow.rotated {
            transform: rotate(180deg);
        }

        .guide-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }

        .guide-content.expanded {
            max-height: 5000px;
        }

        .guide-body {
            padding: 30px;
            background: #ffffff;
        }

        .guide-step {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #282c3e;
            border-radius: 8px;
        }

        .guide-step h6 {
            color: #282c3e;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .guide-step p {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .guide-step ul,
        .guide-step ol {
            margin: 10px 0;
            padding-right: 20px;
        }

        .guide-step ul li,
        .guide-step ol li {
            color: #495057;
            line-height: 1.8;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            overflow-x: auto;
        }

        .code-line {
            display: flex;
            gap: 15px;
            margin: 5px 0;
        }

        .code-label {
            color: #9cdcfe;
            font-weight: 600;
            min-width: 100px;
        }

        .code-value {
            color: #ce9178;
        }

        .provider-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .provider-tab {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
            color: #495057;
        }

        .provider-tab:hover {
            border-color: #282c3e;
            background: #f8f9fa;
        }

        .provider-tab.active {
            background: #282c3e;
            color: white;
            border-color: #282c3e;
        }

        .provider-content {
            display: none;
        }

        .provider-content.active {
            display: block;
        }

        .check-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #dee2e6;
            margin-bottom: 30px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .domain-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }

        .badge-domain {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-subdomain {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        /* إضافة تنسيق خاص للـ Main Section */
        .main-operations-section {
            margin-bottom: 40px;
        }

        /* DNS Info Styles */
        .dns-info-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .dns-section {
            margin-bottom: 20px;
        }

        .dns-section:last-child {
            margin-bottom: 0;
        }

        .dns-section h6 {
            color: #282c3e;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dns-list {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #e0e0e0;
        }

        .dns-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-family: 'Courier New', monospace;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dns-item:last-child {
            border-bottom: none;
        }

        .bg-success-subtle {
            background-color: #d4edda !important;
        }

        .dns-item code {
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        .copy-dns-btn {
            background: #282c3e;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-dns-btn:hover {
            background: #3d4152;
            transform: scale(1.05);
        }

        .copy-dns-btn i {
            font-size: 12px;
        }

        .info-box {
            background: #fff3cd;
            border-right: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .info-box.success {
            background: #d4edda;
            border-right-color: #28a745;
        }

        .info-box.warning {
            background: #fff3cd;
            border-right-color: #ffc107;
        }

        .info-box.danger {
            background: #f8d7da;
            border-right-color: #dc3545;
        }
    </style>
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('content')
    <div class="transfer-container">
        <div class="container">
            <div class="transfer-card">
                <!-- Card Header -->
                <div class="card-header-custom">
                    <i class="ti ti-link" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3 style="color:white">اربط دومينك الفرعي إلى استضافتنا</h3>
                    <p>اربط الدومين الفرعي الخاص بك إلى استضافتنا بخطوات بسيطة</p>
                </div>

                <!-- Card Body -->
                <div class="card-body-custom">
                    <!-- Flash Messages Display -->
                    @if (Session::has('success'))
                        <div class="alert-custom alert-success-custom">
                            <i class="ti ti-check-circle" style="font-size: 24px;"></i>
                            <div>{{ Session::get('success') }}</div>
                        </div>
                    @endif

                    @if (Session::has('error'))
                        <div class="alert-custom alert-error-custom">
                            <i class="ti ti-alert-circle" style="font-size: 24px;"></i>
                            <div>{{ Session::get('error') }}</div>
                        </div>
                    @endif

                    @if (Session::has('info'))
                        <div class="alert-custom alert-info-custom">
                            <i class="ti ti-info-circle" style="font-size: 24px;"></i>
                            <div>{{ Session::get('info') }}</div>
                        </div>
                    @endif

                    @if (Session::has('warning'))
                        <div class="alert-custom alert-warning-custom">
                            <i class="ti ti-bulb" style="font-size: 24px;"></i>
                            <div>{{ Session::get('warning') }}</div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert-custom alert-error-custom">
                            <i class="ti ti-alert-circle" style="font-size: 24px;"></i>
                            <div>
                                <strong>خطأ في التحقق:</strong>
                                <ul style="margin: 5px 0 0 0; padding-right: 20px;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Main Operations Section - ALL AT THE TOP -->
                    <div class="main-operations-section" id="mainOperationsSection">

                        <!-- Check Domain Section -->
                        <div id="checkDomainSection">
                            <h5 style="color: #282c3e; font-weight: 700; margin-bottom: 20px; text-align: center;">
                                <i class="ti ti-check me-2"></i>
                                تحقق من إعداد الدومين الفرعي
                            </h5>
                            <div id="alertContainer"></div>

                            <div class="form-group-custom">
                                <label class="form-label-custom">
                                    <i class="ti ti-world me-2"></i>
                                    اسم الدومين الفرعي
                                </label>
                                <input type="text" id="domainInput" class="form-control-custom"
                                    placeholder="test.example.com " dir="ltr">
                                <small class="text-muted">
                                    أدخل اسم الدومين الفرعي الكامل - يدعم الدومينات العادية (test.example.com)
                                </small>
                            </div>

                            <button type="button" id="checkDomainBtn" class="btn-check-domain">
                                <i class="ti ti-search me-2"></i>
                                التحقق من إعداد A Record
                            </button>
                        </div>

                        <!-- Transfer Form Section -->
                        <div id="transferSection" style="display: none;">
                            <div class="success-icon">
                                <i class="ti ti-check" style="font-size: 40px; color: white;"></i>
                            </div>

                            <h4 class="text-center mb-3" style="color: #282c3e;">الدومين جاهز للربط!</h4>
                            <div id="domainStatusBox" class="mb-4"></div>

                            <form method="POST" action="{{ url(tenant('id') . '/domains/link-domain-proccess') }}">
                                @csrf
                                <input type="hidden" id="domainName" name="domain">

                                <div class="form-group-custom">
                                    <label class="form-label-custom">
                                        <i class="ti ti-world me-2"></i>
                                        اسم الدومين الفرعي
                                        <span id="domainTypeBadge"></span>
                                    </label>
                                    <input type="text" id="domainDisplay" class="form-control-custom" dir="ltr"
                                        readonly style="background: #f8f9fa;">
                                </div>

                                <div class="d-flex gap-3">
                                    <button type="button" id="backBtn" class="btn btn-outline-secondary">
                                        <i class="ti ti-arrow-right me-2"></i>
                                        رجوع
                                    </button>
                                    <button type="submit" class="btn-transfer">
                                        <i class="ti ti-check me-2"></i>
                                        تأكيد الربط
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                    <!-- End of Main Operations Section -->

                    <!-- Success Section - SEPARATED BELOW -->
                    <div id="successSection" style="display: none; text-align: center;">
                        <div class="success-icon">
                            <i class="ti ti-check" style="font-size: 40px; color: white;"></i>
                        </div>
                        <h4 class="mt-4 mb-3" style="color: #282c3e;">تم ربط الدومين بنجاح!</h4>
                        <p class="text-muted mb-4">
                            الدومين الآن مرتبط بشكل صحيح. يمكنك البدء في استخدامه فوراً.
                        </p>
                        <a href="{{ url('/' . tenant('id') . '/domains/dashboard') }}" class="btn-check-domain">
                            <i class="ti ti-arrow-left me-2"></i>
                            العودة إلى لوحة التحكم
                        </a>
                    </div>

                    <hr style="margin: 40px 0; border-color: #dee2e6;">

                    <!-- Info Alert -->
                    <div class="alert-custom alert-info-custom">
                        <i class="ti ti-info-circle" style="font-size: 24px;"></i>
                        <div>
                            <strong>قبل البدء:</strong>
                            <p class="mb-0">يجب أن يكون لديك صلاحية الوصول إلى إعدادات DNS الخاصة بالدومين لإضافة A Record
                            </p>
                        </div>
                    </div>

                    <!-- Required IP Box -->
                    <div class="check-section">
                        <h6 style="color: #282c3e; font-weight: 700; margin-bottom: 15px; text-align: center;">
                            <i class="ti ti-server me-2"></i>
                            عنوان IP المطلوب للـ A Record
                        </h6>
                        <div class="ip-highlight">
                            <span id="serverIp">{{ env('SERVER_IP', '135.181.215.105') }}</span>
                            <button type="button" class="btn-copy-ip" id="copyIpBtn">
                                <i class="ti ti-copy"></i>
                                <span class="copy-text">نسخ</span>
                            </button>
                        </div>
                        <p class="text-center text-muted mb-0" style="font-size: 13px;">
                            قم بإضافة A Record في لوحة تحكم DNS يشير إلى هذا العنوان
                        </p>
                    </div>

                    <!-- Guide Panel -->
                    <div class="guide-panel">
                        <div class="guide-header" id="guideToggle">
                            <div class="guide-header-content">
                                <i class="ti ti-book"></i>
                                <h5>دليل ربط دومين خارجي(اضغط للعرض)</h5>
                            </div>
                            <i class="ti ti-chevron-down guide-arrow" id="guideArrow"></i>
                        </div>

                        <div class="guide-content" id="guideContent">
                            <div class="guide-body">
                                <!-- Provider Tabs -->
                                <div class="provider-tabs">
                                    <div class="provider-tab active" data-provider="general">عام (كل المزودين)</div>
                                    <div class="provider-tab" data-provider="godaddy">GoDaddy</div>
                                    <div class="provider-tab" data-provider="namecheap">Namecheap</div>
                                    <div class="provider-tab" data-provider="cloudflare">Cloudflare</div>
                                    <div class="provider-tab" data-provider="cpanel">cPanel</div>
                                </div>

                                <!-- General Guide -->
                                <div class="provider-content active" id="general">
                                    <div class="guide-step">
                                        <h6>
                                            <span
                                                style="background: #282c3e; color: white; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 13px;">1</span>
                                            تسجيل الدخول إلى لوحة تحكم الدومين
                                        </h6>
                                        <p>قم بتسجيل الدخول إلى حسابك لدى مزود خدمة الدومين (مثل GoDaddy، Namecheap،
                                            Cloudflare، إلخ)</p>
                                    </div>

                                    <div class="guide-step">
                                        <h6>
                                            <span
                                                style="background: #282c3e; color: white; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 13px;">2</span>
                                            الوصول إلى إعدادات DNS
                                        </h6>
                                        <p>ابحث عن قسم DNS Management أو DNS Settings أو Manage DNS في لوحة التحكم</p>
                                        <ul>
                                            <li>في بعض اللوحات يسمى "DNS Zone Editor"</li>
                                            <li>قد تجده تحت "Domain Settings" أو "Advanced Settings"</li>
                                        </ul>
                                    </div>

                                    <div class="guide-step">
                                        <h6>
                                            <span
                                                style="background: #282c3e; color: white; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 13px;">3</span>
                                            إضافة A Record جديد
                                        </h6>
                                        <p>اضغط على "Add Record" أو "Add DNS Record" ثم اختر نوع السجل "A"</p>

                                        <div class="code-block">
                                            <div class="code-line">
                                                <span class="code-label">Type:</span>
                                                <span class="code-value">A</span>
                                            </div>
                                            <div class="code-line">
                                                <span class="code-label">Host/Name:</span>
                                                <span class="code-value">@ (للدومين الرئيسي) أو subdomain (للصب
                                                    دومين)</span>
                                            </div>
                                            <div class="code-line">
                                                <span class="code-label">Points to/Value:</span>
                                                <span class="code-value">{{ env('SERVER_IP', '135.181.215.105') }}</span>
                                            </div>
                                            <div class="code-line">
                                                <span class="code-label">TTL:</span>
                                                <span class="code-value">3600 (أو تلقائي)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="guide-step">
                                        <h6>
                                            <span
                                                style="background: #282c3e; color: white; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 13px;">4</span>
                                            حفظ التغييرات والانتظار
                                        </h6>
                                        <p>اضغط على "Save" أو "Add Record" لحفظ الإعدادات</p>
                                        <ul>
                                            <li>قد يستغرق انتشار DNS من دقائق إلى 48 ساعة</li>
                                            <li>عادة ما يتم في أقل من ساعة مع معظم المزودين</li>
                                            <li>يمكنك استخدام أدوات مثل <a href="https://www.whatsmydns.net/"
                                                    target="_blank">whatsmydns.net</a> للتحقق</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- GoDaddy Guide -->
                                <div class="provider-content" id="godaddy">
                                    <div class="guide-step">
                                        <h6><i class="ti ti-list-numbers me-2"></i>خطوات إضافة A Record في GoDaddy</h6>
                                        <ol>
                                            <li>سجل دخولك إلى حسابك في GoDaddy</li>
                                            <li>اذهب إلى "My Products" > "Domains"</li>
                                            <li>اضغط على "DNS" بجانب الدومين المطلوب</li>
                                            <li>في قسم "Records"، اضغط على "Add"</li>
                                            <li>اختر Type: "A"</li>
                                            <li>في Host: أدخل @ للدومين الرئيسي أو اسم الصب دومين</li>
                                            <li>في Points to: أدخل
                                                <strong>{{ env('SERVER_IP', '135.181.215.105') }}</strong>
                                            </li>
                                            <li>TTL: اترك الافتراضي أو اختر 1 Hour</li>
                                            <li>اضغط على "Save"</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Namecheap Guide -->
                                <div class="provider-content" id="namecheap">
                                    <div class="guide-step">
                                        <h6><i class="ti ti-list-numbers me-2"></i>خطوات إضافة A Record في Namecheap</h6>
                                        <ol>
                                            <li>سجل دخولك إلى Namecheap Dashboard</li>
                                            <li>اذهب إلى "Domain List"</li>
                                            <li>اضغط على "Manage" بجانب الدومين</li>
                                            <li>اذهب إلى تبويب "Advanced DNS"</li>
                                            <li>في قسم "Host Records"، اضغط "Add New Record"</li>
                                            <li>Type: اختر "A Record"</li>
                                            <li>Host: أدخل @ أو اسم الصب دومين</li>
                                            <li>Value: <strong>{{ env('SERVER_IP', '135.181.215.105') }}</strong></li>
                                            <li>TTL: Automatic</li>
                                            <li>اضغط على علامة ✓ للحفظ</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Cloudflare Guide -->
                                <div class="provider-content" id="cloudflare">
                                    <div class="guide-step">
                                        <h6><i class="ti ti-list-numbers me-2"></i>خطوات إضافة A Record في Cloudflare</h6>
                                        <ol>
                                            <li>سجل دخولك إلى Cloudflare Dashboard</li>
                                            <li>اختر الدومين من القائمة</li>
                                            <li>اذهب إلى تبويب "DNS"</li>
                                            <li>اضغط على "Add record"</li>
                                            <li>Type: اختر "A"</li>
                                            <li>Name: أدخل @ أو اسم الصب دومين</li>
                                            <li>IPv4 address: <strong>{{ env('SERVER_IP', '135.181.215.105') }}</strong>
                                            </li>
                                            <li>Proxy status: يمكنك تفعيله أو تركه</li>
                                            <li>TTL: Auto</li>
                                            <li>اضغط على "Save"</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- cPanel Guide -->
                                <div class="provider-content" id="cpanel">
                                    <div class="guide-step">
                                        <h5><i class="ti ti-user-plus me-2"></i>خطوات إنشاء حساب على cPanel</h5>

                                        <!-- الطريقة الثانية: إنشاء حساب عبر مزود الاستضافة -->
                                        <h6 style="margin-top: 18px;"><i class="ti ti-arrow-badge-right me-2"></i>الخطوة
                                            الأولى: إنشاء حساب cPanel عبر مزود الاستضافة</h6>
                                        <ol>
                                            <li>إذا لم تكن تمتلك صلاحية الدخول إلى لوحة WHM، فيمكنك طلب إنشاء حساب cPanel
                                                جديد من مزود الاستضافة الخاص بك أو شراء باقة استضافة جديدة.</li>
                                            <li>بعد إنشاء الحساب، سيقوم مزود الخدمة بتزويدك ببيانات الدخول، وتشمل:
                                                <ul>
                                                    <li>رابط لوحة cPanel (عادة يكون بالشكل:
                                                        <code>https://yourdomain.com:2083</code> أو من خلال لوحة العميل).
                                                    </li>
                                                    <li>اسم المستخدم (<strong>Username</strong>).</li>
                                                    <li>كلمة المرور (<strong>Password</strong>).</li>
                                                </ul>
                                            </li>
                                            <li>قم بتسجيل الدخول إلى لوحة cPanel باستخدام هذه البيانات للتحكم في ملفات
                                                موقعك، البريد الإلكتروني، وسجلات الـ DNS.</li>
                                        </ol>

                                        <!-- ربط الدومين وتهيئة DNS -->
                                        <h6 style="margin-top: 18px;"><i class="ti ti-arrow-badge-right me-2"></i>الخطوة
                                            الثانية: ربط الدومين وتهيئة سجلات الـ DNS</h6>
                                        <p>حتى يعمل الدومين مع الحساب الذي تم إنشاؤه، يجب إعداد سجلات الـ DNS بشكل صحيح.
                                            يمكن تنفيذ ذلك من خلال أداة <strong>Zone Editor</strong> داخل لوحة cPanel:</p>
                                        <ol>
                                            <li>ادخل إلى لوحة cPanel الخاصة بالحساب.</li>
                                            <li>ابحث عن خيار <strong>Zone Editor</strong> أو <strong>DNS Zone
                                                    Editor</strong>، ثم اضغط على "Manage" بجانب الدومين المطلوب.</li>
                                            <li>لإضافة سجل جديد:
                                                <ul>
                                                    <li>اضغط على <strong>+ Add Record</strong>.</li>
                                                    <li>اختر نوع السجل: <strong>A</strong>.</li>
                                                    <li>في حقل <em>Name</em>:
                                                        <ul>
                                                            <li>للدومين الرئيسي اكتب <strong>@</strong> أو اسم الدومين
                                                                كاملاً.</li>
                                                            <li>للـ subdomain اكتب الجزء الفرعي فقط (مثال:
                                                                <strong>blog</strong>).
                                                            </li>
                                                        </ul>
                                                    </li>
                                                    <li>في حقل <em>Address / IPv4</em> أدخل عنوان الـ IP الخاص بالسيرفر:
                                                        <strong>{{ env('SERVER_IP', '135.181.215.105') }}</strong>
                                                    </li>
                                                    <li>اترك قيمة TTL كما هي، ثم اضغط <strong>Add Record</strong>.</li>
                                                </ul>
                                            </li>
                                            <li>إذا كان هناك سجل A موجود بالفعل ويشير إلى IP مختلف، اضغط
                                                <strong>Edit</strong>، ثم غيّر العنوان إلى الـ IP الصحيح واحفظ التعديلات.
                                            </li>
                                            <li>إذا كانت الاستضافة تستخدم خوادم أسماء (Nameservers) خاصة بها، فقد تحتاج إلى
                                                تعديلها من لوحة مسجل الدومين (مثل GoDaddy أو Namecheap) لتشير إلى خوادم
                                                الاستضافة الجديدة.</li>
                                        </ol>

                                        <!-- التحقق والانتشار -->
                                        <h6 style="margin-top: 18px;"><i class="ti ti-search me-2"></i>الخطوة الثالثة:
                                            التحقق من إعداد الـ DNS وانتظار الانتشار</h6>
                                        <ul>
                                            <li>بعد تعديل إعدادات الـ DNS، قد يستغرق انتشارها ما بين بضع دقائق إلى 24 ساعة
                                                (وفي العادة أقل من ذلك).</li>
                                            <li>يمكنك استخدام أدوات مثل <strong>dig</strong> أو مواقع فحص DNS للتأكد من أن
                                                سجل الـ A يشير إلى عنوان الـ IP الصحيح.</li>
                                            <li>إذا لم يعمل الموقع بعد التوجيه، تأكد من أن ملفات الموقع موضوعة في المجلد
                                                الصحيح (مثل: <code>public_html/example</code>).</li>
                                        </ul>

                                        <!-- نصائح أمنية -->
                                        <h6 style="margin-top: 18px;"><i class="ti ti-shield-check me-2"></i>نصائح مهمة
                                            للحماية والإدارة</h6>
                                        <ul>
                                            <li>استخدم كلمة مرور قوية وقم بتفعيل المصادقة الثنائية (Two-Factor
                                                Authentication) إذا كانت متاحة.</li>
                                            <li>احفظ نسخة من إعدادات الـ DNS قبل إجراء أي تعديل (سواء بصورة أو ملف احتياطي)
                                                لتجنب فقدان البيانات في حال حدوث خطأ.</li>
                                            <li>إذا لم يكن العميل يمتلك الخبرة الكافية لتعديل إعدادات DNS، يمكنك إعدادها له
                                                أو إرسال تعليمات واضحة لمسجل الدومين.</li>
                                        </ul>

                                        <div class="alert-custom alert-info-custom mt-3">
                                            <i class="ti ti-info-circle" style="font-size: 18px;"></i>
                                            <div>
                                                <strong>ملاحظة:</strong>
                                                <p class="mb-0">عملية إنشاء حساب cPanel تختلف عن عملية نقل ملكية الدومين
                                                    بين المسجلين (مثل GoDaddy إلى Namecheap). لا يشترط نقل الدومين لتشغيل
                                                    الموقع — المهم هو إعداد سجلات الـ DNS أو تغيير خوادم الأسماء لتشير إلى
                                                    خوادم الاستضافة.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="alert-custom alert-warning-custom mt-3">
                                    <i class="ti ti-bulb" style="font-size: 20px;"></i>
                                    <div>
                                        <strong>نصيحة:</strong>
                                        <p class="mb-0">بعد إضافة A Record، انتظر بضع دقائق ثم تحقق من الدومين باستخدام
                                            النموذج في أعلى الصفحة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const domainInput = document.getElementById('domainInput');
            const checkDomainBtn = document.getElementById('checkDomainBtn');
            const checkDomainSection = document.getElementById('checkDomainSection');
            const transferSection = document.getElementById('transferSection');
            const successSection = document.getElementById('successSection');
            const mainOperationsSection = document.getElementById('mainOperationsSection');
            const alertContainer = document.getElementById('alertContainer');
            const backBtn = document.getElementById('backBtn');

            // Copy IP Button
            const copyIpBtn = document.getElementById('copyIpBtn');
            const serverIp = document.getElementById('serverIp').textContent;

            copyIpBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(serverIp).then(function() {
                    const originalText = copyIpBtn.innerHTML;
                    copyIpBtn.classList.add('copied');
                    copyIpBtn.innerHTML =
                        '<i class="ti ti-check"></i><span class="copy-text">تم النسخ!</span>';

                    setTimeout(function() {
                        copyIpBtn.classList.remove('copied');
                        copyIpBtn.innerHTML = originalText;
                    }, 2000);
                }).catch(function(err) {
                    console.error('فشل النسخ:', err);
                    alert('حدث خطأ أثناء النسخ');
                });
            });

            // Guide Toggle
            const guideToggle = document.getElementById('guideToggle');
            const guideContent = document.getElementById('guideContent');
            const guideArrow = document.getElementById('guideArrow');

            guideToggle.addEventListener('click', function() {
                guideContent.classList.toggle('expanded');
                guideArrow.classList.toggle('rotated');
            });

            // Provider Tabs
            const providerTabs = document.querySelectorAll('.provider-tab');
            const providerContents = document.querySelectorAll('.provider-content');

            providerTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const provider = this.getAttribute('data-provider');

                    providerTabs.forEach(t => t.classList.remove('active'));
                    providerContents.forEach(c => c.classList.remove('active'));

                    this.classList.add('active');
                    document.getElementById(provider).classList.add('active');
                });
            });

            // دالة عرض معلومات DNS مع حالة التوجيه (بدون زرار المتابعة)
            function displayDNSInfo(dnsInfo, pointingStatus, domain) {
                // إزالة أي DNS Info سابق
                const existingCard = document.getElementById('dnsInfoCard');
                if (existingCard) {
                    existingCard.remove();
                }

                // إزالة زرار المتابعة السابق إن وُجد
                const existingContinueBtn = document.getElementById('continueToDomainBtn');
                if (existingContinueBtn) {
                    existingContinueBtn.remove();
                }

                let dnsHtml = `
                <div class="dns-info-card" id="dnsInfoCard">
                    <h5 class="mb-3">
                        <i class="ti ti-server me-2"></i>
                        معلومات DNS للدومين: <strong class="text-primary">${domain}</strong>
                    </h5>
            `;

                // عرض حالة التوجيه
                let statusClass = 'danger';
                let statusIcon = 'x-circle';
                let statusTitle = 'غير متوجه';

                dnsHtml += `
                <div class="alert-custom alert-${statusClass}-custom mb-3">
                    <i class="ti ti-${statusIcon}" style="font-size: 24px;"></i>
                    <div>
                        <h6 class="mb-2"><strong>حالة التوجيه: ${statusTitle}</strong></h6>
                        <p class="mb-0">${pointingStatus.message}</p>
                    </div>
                </div>
            `;

                // عرض A Records الحالية
                dnsHtml += `
                <div class="dns-section">
                    <h6>
                        <i class="ti ti-point-filled text-warning"></i>
                        A Records (عناوين IP الحالية)
                    </h6>
            `;

                if (dnsInfo.a_records && dnsInfo.a_records.length > 0) {
                    dnsHtml += `<div class="dns-list">`;
                    dnsInfo.a_records.forEach((ip, index) => {
                        dnsHtml += `
                        <div class="dns-item">
                            <div>
                                <span>${ip}</span>
                            </div>
                            <button class="copy-dns-btn" onclick="copyToClipboard('${ip}')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    `;
                    });
                    dnsHtml += `</div>`;
                } else {
                    dnsHtml += `
                    <div class="alert-custom alert-warning-custom mb-0">
                        <i class="ti ti-alert-triangle"></i>
                        <div>لم يتم العثور على A Records لهذا الدومين</div>
                    </div>
                `;
                }

                // عرض A Record المطلوب
                dnsHtml += `
                <div class="info-box warning mt-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>A Record المطلوب:</strong>
                            <code class="ms-2">${pointingStatus.server_ip}</code>
                        </div>
                        <button class="copy-dns-btn" onclick="copyToClipboard('${pointingStatus.server_ip}')">
                            <i class="ti ti-copy"></i> نسخ
                        </button>
                    </div>
                </div>
            `;

                dnsHtml += `</div>`;

                // عرض Name Servers الحالية
                dnsHtml += `
                <div class="dns-section">
                    <h6>
                        <i class="ti ti-database text-warning"></i>
                        Name Servers (الحالية)
                    </h6>
            `;

                if (dnsInfo.name_servers && dnsInfo.name_servers.length > 0) {
                    dnsHtml += `<div class="dns-list">`;
                    dnsInfo.name_servers.forEach((ns, index) => {
                        dnsHtml += `
                        <div class="dns-item">
                            <div>
                                <span>${ns}</span>
                            </div>
                            <button class="copy-dns-btn" onclick="copyToClipboard('${ns}')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    `;
                    });
                    dnsHtml += `</div>`;
                } else {
                    dnsHtml += `
                    <div class="alert-custom alert-warning-custom mb-0">
                        <i class="ti ti-alert-triangle"></i>
                        <div>لم يتم العثور على Name Servers لهذا الدومين</div>
                    </div>
                `;
                }

                // عرض Name Servers المطلوبة
                if (pointingStatus.server_ns && pointingStatus.server_ns.length > 0) {
                    dnsHtml += `
                    <div class="info-box warning mt-2">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Name Servers المطلوبة:</strong>
                        <div class="mt-2">
                `;
                    pointingStatus.server_ns.forEach((ns, index) => {
                        dnsHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <code>${ns}</code>
                            <button class="copy-dns-btn" onclick="copyToClipboard('${ns}')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    `;
                    });
                    dnsHtml += `
                        </div>
                    </div>
                `;
                }

                dnsHtml += `</div></div>`;

                // إضافة البطاقة بعد الـ alert
                alertContainer.insertAdjacentHTML('afterend', dnsHtml);
            }

            // Check Domain
            checkDomainBtn.addEventListener('click', function() {
                const domain = domainInput.value.trim();

                if (!domain) {
                    showAlert('الرجاء إدخال اسم الدومين', 'error');
                    return;
                }

                if (!isValidDomain(domain)) {
                    showAlert('صيغة الدومين غير صحيحة. أمثلة صحيحة: test.example.com',
                        'error');
                    return;
                }

                const cleanDomain = domain.replace(/^(https?:\/\/)?(www\.)?/, '');

                checkDomainBtn.disabled = true;
                checkDomainBtn.innerHTML = '<span class="spinner me-2"></span> جاري التحقق من DNS...';
                alertContainer.innerHTML = '';

                const tenantId = '{{ tenant('id') }}';
                var url = `/${tenantId}/domains/check-domainss/${cleanDomain}`;

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log('Response:', data);

                        // التحقق من أن الدومين مسجل
                        if (data.response && data.response.result === "available") {
                            showAlert('الدومين غير مسجل! يجب أن يكون الدومين مسجلاً أولاً.',
                                'error');
                            return;
                        }

                        if (data.response && data.response.result === "registered") {
                            // التحقق من حالة التوجيه
                            if (data.dns_info && data.pointing_status) {
                                if (data.pointing_status.is_pointing) {
                                    // الدومين متوجه صح - انقله مباشرة لنموذج الربط
                                    showAlert(
                                        'تم التحقق من الدومين بنجاح! الدومين متوجه إلى سيرفرنا. جاري التحويل...',
                                        'success');

                                    setTimeout(() => {
                                        // إخفاء قسم التحقق وإظهار نموذج الربط
                                        checkDomainSection.style.display = 'none';
                                        transferSection.style.display = 'block';

                                        // ملء البيانات
                                        document.getElementById('domainName').value =
                                            cleanDomain;
                                        document.getElementById('domainDisplay').value =
                                            cleanDomain;

                                        const domainType = getDomainType(cleanDomain);
                                        const badgeClass = domainType === 'domain' ?
                                            'badge-domain' : 'badge-subdomain';
                                        const badgeText = domainType === 'domain' ?
                                            'دومين رئيسي' : 'صب دومين';
                                        document.getElementById('domainTypeBadge')
                                            .innerHTML =
                                            `<span class="domain-badge ${badgeClass}">${badgeText}</span>`;
                                    }, 1000);
                                } else {
                                    // الدومين مش متوجه - اعرض معلومات DNS
                                    displayDNSInfo(data.dns_info, data.pointing_status,
                                        cleanDomain);

                                    // رسالة تحذير تختفي بعد ثانيتين
                                    showAlert(`
                                    <strong>الدومين غير متوجه إلى سيرفرنا</strong><br>
                                    <div style="margin-top: 10px;">
                                        <strong>IP الحالي:</strong> <code>${data.dns_info.a_records.length > 0 ? data.dns_info.a_records.join(', ') : 'غير محدد'}</code><br>
                                        <strong>IP المطلوب:</strong> <code>${data.pointing_status.server_ip}</code>
                                    </div>
                                `, 'warning');

                                    // إخفاء الرسالة بعد ثانيتين
                                    setTimeout(() => {
                                        alertContainer.innerHTML = '';
                                    }, 2000);
                                }
                            } else {
                                showAlert('حدث خطأ في استرجاع معلومات DNS', 'error');
                            }
                        } else {
                            showAlert('حدث خطأ أثناء التحقق من الدومين', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', xhr.responseJSON || error);
                        let errorMessage = 'حدث خطأ أثناء التحقق من الدومين';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        showAlert(errorMessage, 'error');
                    },
                    complete: function() {
                        checkDomainBtn.disabled = false;
                        checkDomainBtn.innerHTML =
                            '<i class="ti ti-search me-2"></i> التحقق من إعداد A Record';
                    }
                });
            });

            // Back Button
            backBtn.addEventListener('click', function() {
                transferSection.style.display = 'none';
                checkDomainSection.style.display = 'block';
                alertContainer.innerHTML = '';
            });

            // Form Submit with AJAX
            document.getElementById('transferForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner me-2"></span> جاري الربط...';

                const formData = new FormData(this);

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => Promise.reject(err));
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);

                        if (data.success) {
                            mainOperationsSection.style.display = 'none';

                            if (data.result === 'exists') {
                                showSuccessSection(
                                    data.message,
                                    'info',
                                    'الدومين مرتبط بالفعل',
                                    'الدومين يعمل بشكل صحيح ولكن لم يتم إنشاء حساب جديد لأنه موجود مسبقاً.'
                                );
                            } else if (data.result === true) {
                                showSuccessSection(
                                    data.message,
                                    'success',
                                    'تم ربط الدومين بنجاح!',
                                    'الدومين الآن مرتبط بشكل صحيح. يمكنك البدء في استخدامه فوراً.'
                                );
                            } else {
                                showSuccessSection(
                                    data.message,
                                    data.type || 'success',
                                    'تمت العملية',
                                    'تم إتمام العملية بنجاح.'
                                );
                            }

                            successSection.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                        } else {
                            showAlert(data.message || 'حدث خطأ أثناء ربط الدومين', data.type ||
                                'error');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorMessage = error.message ||
                            'حدث خطأ أثناء ربط الدومين. حاول مرة أخرى.';
                        showAlert(errorMessage, 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
            });

            // Helper Functions
            function showAlert(message, type) {
                const alertTypes = {
                    'success': {
                        class: 'alert-success-custom',
                        icon: 'ti-check-circle'
                    },
                    'error': {
                        class: 'alert-error-custom',
                        icon: 'ti-alert-circle'
                    },
                    'warning': {
                        class: 'alert-warning-custom',
                        icon: 'ti-bulb'
                    },
                    'info': {
                        class: 'alert-info-custom',
                        icon: 'ti-info-circle'
                    }
                };

                const alertConfig = alertTypes[type] || alertTypes['error'];

                alertContainer.innerHTML = `
                <div class="alert-custom ${alertConfig.class}">
                    <i class="ti ${alertConfig.icon}" style="font-size: 24px; flex-shrink: 0;"></i>
                    <div style="flex: 1;">${message}</div>
                </div>
            `;

                if (type === 'success') {
                    setTimeout(() => {
                        const alertElement = alertContainer.querySelector('.alert-custom');
                        if (alertElement) {
                            alertElement.style.transition = 'opacity 0.5s ease';
                            alertElement.style.opacity = '0';
                            setTimeout(() => {
                                alertContainer.innerHTML = '';
                            }, 500);
                        }
                    }, 5000);
                }
            }

            function showSuccessSection(alertMessage, alertType, title, description) {
                const successSection = document.getElementById('successSection');
                const successIcon = successSection.querySelector('.success-icon');

                const iconColors = {
                    'success': '#28a745',
                    'info': '#17a2b8',
                    'warning': '#ffc107',
                    'error': '#dc3545'
                };

                successIcon.style.background = iconColors[alertType] || iconColors['success'];

                const titleElement = successSection.querySelector('h4');
                const descElement = successSection.querySelector('p');

                if (titleElement) titleElement.textContent = title;
                if (descElement) descElement.textContent = description;

                successSection.style.display = 'block';

                showAlertInSuccessSection(alertMessage, alertType);
            }

            function showAlertInSuccessSection(message, type) {
                const alertTypes = {
                    'success': {
                        class: 'alert-success-custom',
                        icon: 'ti-check-circle'
                    },
                    'error': {
                        class: 'alert-error-custom',
                        icon: 'ti-alert-circle'
                    },
                    'warning': {
                        class: 'alert-warning-custom',
                        icon: 'ti-bulb'
                    },
                    'info': {
                        class: 'alert-info-custom',
                        icon: 'ti-info-circle'
                    }
                };

                const alertConfig = alertTypes[type] || alertTypes['success'];
                const successSection = document.getElementById('successSection');

                const oldAlert = successSection.querySelector('.alert-custom');
                if (oldAlert) {
                    oldAlert.remove();
                }

                const alertDiv = document.createElement('div');
                alertDiv.className = `alert-custom ${alertConfig.class}`;
                alertDiv.style.marginBottom = '20px';
                alertDiv.innerHTML = `
                <i class="ti ${alertConfig.icon}" style="font-size: 24px; flex-shrink: 0;"></i>
                <div style="flex: 1;">${message}</div>
            `;

                const firstChild = successSection.firstChild;
                if (firstChild) {
                    successSection.insertBefore(alertDiv, firstChild);
                } else {
                    successSection.appendChild(alertDiv);
                }
            }

            function isValidDomain(domain) {
                const cleanDomain = domain.replace(/(https?:\/\/|www\.)/g, '');
                const parts = cleanDomain.split('.');

                // ✅ يجب أن يكون في الضبط 2 أجزاء فقط (name.extension)
                if (parts.length !== 3) return false;

                // Regex للتحقق من صحة كل جزء
                const domainRegex = /^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i;

                // التحقق من الأول والآخر
                const [firstPart, extension] = parts;

                // ✅ التحقق من الصحة
                if (!domainRegex.test(firstPart) || !domainRegex.test(extension)) return false;

                // ✅ التحقق من طول Extension (يجب أن يكون من 2 إلى 6 أحرف)
                if (extension.length < 2 || extension.length > 6) return false;

                return true;
            }

            function getDomainType(domain) {
                const parts = domain.split('.');
                return parts.length > 2 ? 'subdomain' : 'domain';
            }

            // Copy to Clipboard Function
            window.copyToClipboard = function(text) {
                navigator.clipboard.writeText(text).then(function() {
                    const toast = document.createElement('div');
                    toast.className =
                        'position-fixed top-0 start-50 translate-middle-x mt-5 alert alert-success';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = '<i class="ti ti-check me-2"></i>تم النسخ بنجاح!';
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 2000);
                }).catch(function(err) {
                    console.error('فشل النسخ:', err);
                });
            };

            // Allow Enter key to trigger check
            domainInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    checkDomainBtn.click();
                }
            });

            // Auto-hide flash messages after 7 seconds
            setTimeout(function() {
                const flashAlerts = document.querySelectorAll('.card-body-custom > .alert-custom');
                flashAlerts.forEach(function(alert) {
                    if (alert.classList.contains('alert-success-custom')) {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => {
                            alert.remove();
                        }, 500);
                    }
                });
            }, 7000);
        });
    </script>



@endsection
