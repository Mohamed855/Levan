@extends('tenants.layouts.layoutMaster')

@section('title', 'ربط دومين')

@section('page-style')
@vite('resources/assets/css/components/icons.css')
@endsection

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('toastr')
@vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

<style>
    :root {
        --primary-color: #373c52;
        --primary-dark: #372632;
        --primary-light: #4a5073;
        --success-color: #4caf50;
        --warning-color: #ffa726;
        --danger-color: #ef5350;
        --info-color: #0c5fa8;
        --border-color: #d4d7e3;
        --bg-light: #f8f9fc;
        --text-muted: #6c757d;
        --text-dark: #373c52;
    }

    body {
        background: var(--bg-light);
    }

    .link-container {
        min-height: 100vh;
        padding: 20px 15px;
    }

    .steps-progress {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
        padding: 20px 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }

    .steps-container {
        display: flex;
        justify-content: center;
        align-items: center;
        max-width: 650px;
        margin: 0 auto;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .step-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .step-item.active .step-circle {
        background: #4caf50;
        color: white;
        transform: scale(1.08);
        box-shadow: 0 3px 12px rgba(76, 175, 80, 0.35);
    }

    .step-item.completed .step-circle {
        background: rgba(76, 175, 80, 0.25);
        color: #4caf50;
        border-color: #4caf50;
    }

    .step-label {
        margin-top: 8px;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.75);
        font-weight: 500;
        text-align: center;
        max-width: 100px;
        line-height: 1.3;
    }

    .step-item.active .step-label {
        color: white;
        font-weight: 600;
    }

    .step-line {
        flex-grow: 1;
        height: 2px;
        background: rgba(255, 255, 255, 0.2);
        margin: 0 15px;
        border-radius: 2px;
        align-self: flex-start;
        margin-top: 21px;
    }

    .step-item.completed + .step-item .step-line {
        background: rgba(76, 175, 80, 0.45);
    }

    .form-section {
        display: none;
        animation: fadeIn 0.4s ease;
        background: white;
        padding: 28px 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        max-width: 800px;
        margin: 0 auto;
    }

    .form-section.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .section-title {
        color: var(--text-dark);
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-subtitle {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-bottom: 22px;
        line-height: 1.5;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-label-custom {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 7px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
    }

    .form-control-custom {
        width: 100%;
        padding: 10px 13px;
        border: 1.5px solid var(--border-color);
        border-radius: 7px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        font-family: 'Courier New', monospace;
        background: white;
    }

    .form-control-custom:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(55, 60, 82, 0.08);
    }

    /* Method Selector - عرض فقط بدون تفاعل */
    .method-selector {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        margin-top: 12px;
    }

    .method-option {
        padding: 18px;
        background: var(--bg-light);
        border: 2px solid var(--border-color);
        border-radius: 8px;
        text-align: center;
        position: relative;
        pointer-events: none;
        opacity: 0.6;
        transition: all 0.3s ease;
    }

    .method-option.selected {
        background: linear-gradient(135deg, rgba(55, 60, 82, 0.05), rgba(74, 80, 115, 0.03));
        border-color: var(--primary-color);
        box-shadow: 0 3px 12px rgba(55, 60, 82, 0.12);
        opacity: 1;
        pointer-events: none;
    }

    .method-option.selected::after {
        content: '\2713';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 22px;
        height: 22px;
        background: var(--success-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
    }

    .method-option i {
        font-size: 34px;
        color: var(--primary-color);
        margin-bottom: 10px;
        display: block;
    }

    .method-option h5 {
        margin: 0 0 4px 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
    }

    .method-option small {
        color: var(--text-muted);
        font-size: 0.75rem;
    }

    .dns-records-card {
        background: var(--bg-light);
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        margin: 18px 0;
    }

    .dns-record-item {
        background: white;
        border: 1.5px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
    }

    .dns-record-item:last-child {
        margin-bottom: 0;
    }

    .record-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--bg-light);
    }

    .record-type-badge {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
        color: white;
        padding: 5px 12px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.75rem;
    }

    .record-field {
        margin-bottom: 10px;
    }

    .record-field:last-child {
        margin-bottom: 0;
    }

    .record-field-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 4px;
        display: block;
        text-transform: uppercase;
    }

    .record-field-value {
        background: #f3f4f8;
        padding: 8px 11px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        color: var(--text-dark);
        border: 1px solid #e8e9f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .copy-btn-small {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .copy-btn-small i {
        font-size:15px
    }
    .copy-btn-small:hover {
        background: var(--primary-light);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(55, 60, 82, 0.2);
    }

    .confirmation-card {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.06), rgba(76, 175, 80, 0.03));
        border: 2px solid var(--success-color);
        border-radius: 10px;
        padding: 28px;
        text-align: center;
        margin-top: 20px;
    }

    .confirmation-card i {
        font-size: 55px;
        color: var(--success-color);
        margin-bottom: 14px;
    }

    .confirmation-card h5 {
        color: var(--text-dark);
        font-weight: 700;
        font-size: 1.15rem;
        margin-bottom: 10px;
    }

    .confirmation-card p {
        color: var(--text-muted);
        margin-bottom: 16px;
        line-height: 1.5;
        font-size: 0.85rem;
    }

    .domain-display {
        background: white;
        padding: 10px 18px;
        border-radius: 7px;
        font-family: 'Courier New', monospace;
        color: var(--primary-color);
        font-weight: 700;
        font-size: 0.95rem;
        margin: 14px 0;
        border: 2px solid var(--success-color);
    }

    .btn-navigation {
        margin-top: 25px;
        display: flex;
        gap: 10px;
    }

    .btn-custom {
        padding: 10px 22px;
        border: none;
        border-radius: 7px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
        color: white;
        flex: 1;
    }

    .btn-primary-custom:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 16px rgba(55, 60, 82, 0.25);
    }

    .btn-primary-custom:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-secondary-custom {
        background: #f3f4f8;
        color: var(--text-dark);
        border: 1.5px solid var(--border-color);
    }

    .btn-secondary-custom:hover {
        background: #e8e9f1;
        border-color: var(--primary-color);
    }

    .alert-box {
         border-radius: 8px;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.8rem;
        line-height: 1.5;
    }

    .alert-info {
        background: #e7f3ff;
        border: 1px solid rgba(12, 95, 168, 0.25);
        color: #0c5460;
    }

    .alert-info i {
        color: var(--info-color);
    }

    .alert-warning {
        background: #fff8e1;
        border: 1px solid rgba(255, 167, 38, 0.25);
        color: #856404;
    }

    .alert-warning i {
        color: var(--warning-color);
    }

    .alert-success {
        background: #e8f5e9;
        border: 1px solid rgba(76, 175, 80, 0.25);
        color: #155724;
    }

    .alert-success i {
        color: var(--success-color);
    }

    .alert-danger {
         color: #721c24;
    }

    .alert-danger i {
        color: var(--danger-color);
    }

    .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .link-container {
            padding: 15px 10px;
        }

        .steps-progress {
            padding: 18px 20px;
        }

        .form-section {
            padding: 22px 18px;
        }

        .step-circle {
            width: 38px;
            height: 38px;
            font-size: 0.9rem;
        }

        .step-label {
            font-size: 0.7rem;
            max-width: 85px;
        }

        .step-line {
            margin: 0 10px;
            margin-top: 19px;
        }

        .method-selector {
            grid-template-columns: 1fr;
        }

        .btn-navigation {
            flex-direction: column-reverse;
        }

        .section-title {
            font-size: 1.05rem;
        }

        .method-option i {
            font-size: 30px;
        }
    }
</style>

@section('content')
<div class="link-container">
    <div class="steps-progress">
        <div class="steps-container">
            <div class="step-item active" data-step="1">
                <div class="step-circle">1</div>
                <span class="step-label">إدخال الدومين واختيار الطريقة</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item" data-step="2">
                <div class="step-circle">2</div>
                <span class="step-label">التأكيد والربط</span>
            </div>
        </div>
    </div>

    <div id="alertContainer"></div>

    <!-- Step 1 -->
    <div class="form-section active" data-section="1">
        <h5 class="section-title">
            <i class="ti ti-search"></i>
            الخطوة الأولى: التحقق من الدومين
        </h5>
        <p class="section-subtitle">
            أدخل اسم الدومين الذي تريد ربطه بالمنصة، وسيتم تحديد طريقة الربط تلقائيًا
        </p>

        <div class="form-group-custom">
            <label class="form-label-custom">
                <i class="ti ti-world"></i>
                اسم الدومين
            </label>
            <input 
                type="text" 
                id="domainInput" 
                class="form-control-custom" 
                placeholder="example.com" 
                dir="ltr">
            <div class="alert-box alert-info mt-3">
                <i class="ti ti-info-circle" style="font-size: 16px;"></i>
                <div>
                    <strong>مثال:</strong> levan.com للدومين الرئيسي، أو app.levan.com للدومين الفرعي
                </div>
            </div>
        </div>

        <!-- طريقة الربط (تُحدد تلقائيًا) -->
        <div class="form-group-custom">
            <label class="form-label-custom">
                <i class="ti ti-settings"></i>
                طريقة الربط (يتم تحديدها تلقائيًا)
            </label>

            <div class="method-selector">
                <div class="method-option" data-method="nameserver" id="nameserverOption">
                    <i class="ti ti-server"></i>
                    <h5>Name Server</h5>
                    <small>للدومين الرئيسي</small>
                </div>

                <div class="method-option" data-method="cname" id="cnameOption">
                    <i class="ti ti-link"></i>
                    <h5>CNAME Record</h5>
                    <small>للدومين الفرعي</small>
                </div>
            </div>

            <div class="alert-box alert-warning mt-3" id="methodAlert" style="display: none;">
                <i class="ti ti-alert-triangle" style="font-size: 16px;"></i>
                <div id="methodAlertText"></div>
            </div>
        </div>

        <div class="btn-navigation" style="width:98px;">
            <button type="button" class="btn-custom btn-primary-custom" id="step1NextBtn" disabled>
                التالي
                <i class="ti ti-arrow-left"></i>
            </button>
        </div>
    </div>

    <!-- Step 2 -->
    <div class="form-section" data-section="2">
        <!-- للدومين الفرعي: CNAME Records -->
        <div id="dnsRecordsSection" style="display: none;">
            <h5 class="section-title">
                <i class="ti ti-database"></i>
                سجلات DNS المطلوبة
            </h5>
            <p class="section-subtitle">
                قم بإضافة السجلات التالية في لوحة تحكم الدومين الخاص بك
            </p>
            
            <div class="alert-box alert-info">
                <i class="ti ti-info-circle" style="font-size: 16px;"></i>
                <div>
                    قم بإضافة السجلات التالية ثم انتظر حتى يتم التحديث (5 دقائق إلى 48 ساعة)
                </div>
            </div>

            <div class="dns-records-card" id="dnsRecordsContainer"></div>

            <form method="POST" action="{{ url(tenant('id').'/domains/link-domain-proccess') }}" id="subdomainLinkForm">
                @csrf
                <input type="hidden" name="domain" id="subdomainValue">
                <input type="hidden" name="method" id="subdomainMethod">

                <div class="btn-navigation" style="width:247px">
                    <button type="button" class="btn-custom btn-secondary-custom" id="step2BackBtn">
                        <i class="ti ti-arrow-right"></i>
                        السابق
                    </button>
                    <button type="submit" class="btn-custom btn-primary-custom" id="subdomainSubmitBtn">
                        تأكيد الربط
                        <i class="ti ti-check"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- للدومين الرئيسي: NS Records ثم Confirmation -->
        <div id="confirmationSection" style="display: none;">
            <h5 class="section-title">
                <i class="ti ti-database"></i>
                سجلات Name Servers المطلوبة
            </h5>
            <p class="section-subtitle">
                قم بتحديث Name Servers في لوحة تحكم الدومين الخاص بك إلى القيم التالية
            </p>

            <div class="alert-box alert-info">
                <i class="ti ti-info-circle" style="font-size: 16px;"></i>
                <div>
                    قم بتحديث Name Servers ثم انتظر حتى يتم التحديث (قد يستغرق من 5 دقائق إلى 48 ساعة)
                </div>
            </div>

            <div class="dns-records-card" id="nsRecordsContainer"></div>

            <!-- كارت التأكيد بعد السجلات -->
            <div class="confirmation-card">
                <i class="ti ti-circle-check"></i>
                <h5>جاهز لربط الدومين!</h5>
                <p>سيتم ربط الدومين التالي بمنصة Levan:</p>
                
                <div class="domain-display" id="confirmDomainDisplay"></div>

                <div class="alert-box alert-success" style="text-align: right;">
                    <i class="ti ti-check-circle" style="font-size: 16px;"></i>
                    <div>
                        تأكد من تحديث Name Servers كما هو موضح أعلاه قبل الضغط على تأكيد الربط
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ url(tenant('id').'/domains/link-domain-proccess') }}" id="domainLinkForm">
                @csrf
                <input type="hidden" name="domain" id="domainValue">
                <input type="hidden" name="method" value="nameserver">

                <div class="btn-navigation" style="width:291px">
                    <button type="button" class="btn-custom btn-secondary-custom" id="step2BackBtnDomain">
                        <i class="ti ti-arrow-right"></i>
                        السابق
                    </button>
                    <button type="submit" class="btn-custom btn-primary-custom" id="domainSubmitBtn">
                        تأكيد الربط النهائي
                        <i class="ti ti-check"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainInput = document.getElementById('domainInput');
    const step1NextBtn = document.getElementById('step1NextBtn');
    const step2BackBtn = document.getElementById('step2BackBtn');
    const step2BackBtnDomain = document.getElementById('step2BackBtnDomain');
    const nameserverOption = document.getElementById('nameserverOption');
    const cnameOption = document.getElementById('cnameOption');
    const methodAlert = document.getElementById('methodAlert');
    const methodAlertText = document.getElementById('methodAlertText');
    const alertContainer = document.getElementById('alertContainer');
    const dnsRecordsSection = document.getElementById('dnsRecordsSection');
    const confirmationSection = document.getElementById('confirmationSection');
    const dnsRecordsContainer = document.getElementById('dnsRecordsContainer');
    const nsRecordsContainer = document.getElementById('nsRecordsContainer');
    const confirmDomainDisplay = document.getElementById('confirmDomainDisplay');
    const subdomainValue = document.getElementById('subdomainValue');
    const domainValue = document.getElementById('domainValue');
    const subdomainMethod = document.getElementById('subdomainMethod');
    const subdomainSubmitBtn = document.getElementById('subdomainSubmitBtn');
    const domainSubmitBtn = document.getElementById('domainSubmitBtn');

    let currentStep = 1;
    let currentDomain = '';
    let currentMethod = '';
    let isSubdomain = false;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    domainInput.addEventListener('input', function() {
        const domain = this.value.trim();
        currentMethod = '';
        isSubdomain = false;
        step1NextBtn.disabled = true;
        methodAlert.style.display = 'none';
        
        // إزالة التحديد من جميع الخيارات
        nameserverOption.classList.remove('selected');
        cnameOption.classList.remove('selected');

        if (domain && isValidDomain(domain)) {
            const cleanDomain = domain.replace(/^https?:\/\/(www\.)?/, '');
            isSubdomain = detectSubdomain(cleanDomain);

            if (isSubdomain) {
                // فرعي => CNAME
                currentMethod = 'cname';
                cnameOption.classList.add('selected');
                methodAlertText.innerHTML = '<strong>تم التحديد:</strong> سيتم استخدام CNAME لربط الدومين الفرعي.';
                methodAlert.style.display = 'flex';
            } else {
                // رئيسي => NameServer
                currentMethod = 'nameserver';
                nameserverOption.classList.add('selected');
                methodAlertText.innerHTML = '<strong>تم التحديد:</strong> سيتم استخدام Name Servers لربط الدومين الرئيسي.';
                methodAlert.style.display = 'flex';
            }
            
            step1NextBtn.disabled = false;
        }
    });

    step1NextBtn.addEventListener('click', function() {
        const domain = domainInput.value.trim();
        
        if (!domain) {
            showAlert('من فضلك أدخل الدومين', 'warning');
            return;
        }

        if (!isValidDomain(domain)) {
            showAlert('الدومين غير صحيح. مثال: example.com أو sub.example.com', 'warning');
            return;
        }

        if (!currentMethod) {
            showAlert('حدث خطأ في تحديد طريقة الربط', 'warning');
            return;
        }

        currentDomain = domain.replace(/^https?:\/\/(www\.)?/, '');
        isSubdomain = detectSubdomain(currentDomain);

        if (isSubdomain) {
            // CNAME Records
            generateDNSRecordsForSubdomain();
            dnsRecordsSection.style.display = 'block';
            confirmationSection.style.display = 'none';
            subdomainValue.value = currentDomain;
            subdomainMethod.value = currentMethod;
        } else {
            // NS Records ثم Confirmation
            dnsRecordsSection.style.display = 'none';
            confirmationSection.style.display = 'block';
            confirmDomainDisplay.textContent = currentDomain;
            domainValue.value = currentDomain;
            generateNSRecordsForMainDomain();
        }

        goToStep(2);
    });

    step2BackBtn.addEventListener('click', function() {
        goToStep(1);
    });

    step2BackBtnDomain.addEventListener('click', function() {
        goToStep(1);
    });

    function goToStep(step) {
        currentStep = step;

        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.remove('active');
        });

        document.querySelector(`.form-section[data-section="${step}"]`).classList.add('active');

        document.querySelectorAll('.step-item').forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index + 1 < step) {
                item.classList.add('completed');
            } else if (index + 1 === step) {
                item.classList.add('active');
            }
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function generateDNSRecordsForSubdomain() {
        const serverDomain = '{{ env("SERVER_DOMAIN", "levan.com") }}';
        const parts = currentDomain.split('.');
        const host = parts.length > 2 ? parts[0] : '@';

        let recordsHtml = `
            <div class="dns-record-item">
                <div class="record-header">
                    <span class="record-type-badge">CNAME Record</span>
                </div>
                <div class="record-field">
                    <span class="record-field-label">Type</span>
                    <div class="record-field-value">
                        <span>CNAME</span>
                        <button type="button" class="copy-btn-small" onclick="copyText('CNAME')">
                            <i class="ti ti-copy"></i> نسخ
                        </button>
                    </div>
                </div>
                <div class="record-field">
                    <span class="record-field-label">Host / Name</span>
                    <div class="record-field-value">
                        <span>${host}</span>
                        <button type="button" class="copy-btn-small" onclick="copyText('${host}')">
                            <i class="ti ti-copy"></i> نسخ
                        </button>
                    </div>
                </div>
                <div class="record-field">
                    <span class="record-field-label">Value / Points to</span>
                    <div class="record-field-value">
                        <span>${serverDomain}</span>
                        <button type="button" class="copy-btn-small" onclick="copyText('${serverDomain}')">
                            <i class="ti ti-copy"></i> نسخ
                        </button>
                    </div>
                </div>
                <div class="record-field">
                    <span class="record-field-label">TTL</span>
                    <div class="record-field-value">
                        <span>14400</span>
                        <button type="button" class="copy-btn-small" onclick="copyText('14400')">
                            <i class="ti ti-copy"></i> نسخ
                        </button>
                    </div>
                </div>
            </div>
        `;

        dnsRecordsContainer.innerHTML = recordsHtml;
    }

    function generateNSRecordsForMainDomain() {
        const requiredNs = @json(config('dns.required_ns', ['ns1.levan.com', 'ns2.levan.com']));

        let recordsHtml = '';

        requiredNs.forEach(function(ns, index) {
            recordsHtml += `
                <div class="dns-record-item">
                    <div class="record-header">
                        <span class="record-type-badge">NS Record ${index + 1}</span>
                    </div>
                    <div class="record-field">
                        <span class="record-field-label">Type</span>
                        <div class="record-field-value">
                            <span>NS</span>
                            <button type="button" class="copy-btn-small" onclick="copyText('NS')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    </div>
                    <div class="record-field">
                        <span class="record-field-label">Host / Name</span>
                        <div class="record-field-value">
                            <span>@</span>
                            <button type="button" class="copy-btn-small" onclick="copyText('@')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    </div>
                    <div class="record-field">
                        <span class="record-field-label">Value / Name Server</span>
                        <div class="record-field-value">
                            <span>${ns}</span>
                            <button type="button" class="copy-btn-small" onclick="copyText('${ns}')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    </div>
                    <div class="record-field">
                        <span class="record-field-label">TTL</span>
                        <div class="record-field-value">
                            <span>14400</span>
                            <button type="button" class="copy-btn-small" onclick="copyText('14400')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        nsRecordsContainer.innerHTML = recordsHtml;
    }

    function detectSubdomain(domain) {
        const cleanDomain = domain.replace(/^www\./i, '');
        const parts = cleanDomain.split('.');
        return parts.length > 2;
    }

    function isValidDomain(domain) {
        const cleanDomain = domain.replace(/^https?:\/\/(www\.)?/g, '');
        const parts = cleanDomain.split('.');

        if (parts.length < 2) return false;

        const domainRegex = /^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i;

        for (let part of parts) {
            if (!domainRegex.test(part)) return false;
        }

        const ext = parts[parts.length - 1];
        if (ext.length < 2 || ext.length > 6) return false;

        return true;
    }

    function showAlert(message, type) {
        const alertTypes = {
            'success': { class: 'alert-success', icon: 'ti-check-circle' },
            'warning': { class: 'alert-warning', icon: 'ti-alert-triangle' },
            'danger': { class: 'alert-danger', icon: 'ti-alert-circle' },
            'info': { class: 'alert-info', icon: 'ti-info-circle' }
        };

        const config = alertTypes[type] || alertTypes['info'];

        alertContainer.innerHTML = `
            <div class="alert-box ${config.class}">
                <i class="ti ${config.icon}" style="font-size: 16px;"></i>
                <div>${message}</div>
            </div>
        `;

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 9000);
    }

    domainInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (!step1NextBtn.disabled) {
                step1NextBtn.click();
            }
        }
    });

    document.getElementById('subdomainLinkForm').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyAndSubmit(this, subdomainSubmitBtn);
    });

    document.getElementById('domainLinkForm').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyAndSubmit(this, domainSubmitBtn);
    });

    function verifyAndSubmit(form, btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner me-2"></span>جاري التحقق...';

        const formData = new FormData(form);
        const domain = formData.get('domain');
        const method = formData.get('method');

        const tenantId = '{{ tenant("id") }}';
        
        fetch(`/${tenantId}/domains/verify-dns`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ domain, method })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
             return response.json();
        })
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<span class="spinner me-2"></span>جاري الربط...';
                form.submit();
            } else {
                btn.disabled = false;
                btn.innerHTML = method === 'nameserver'
                    ? 'تأكيد الربط النهائي <i class="ti ti-check ms-2"></i>'
                    : 'تأكيد الربط <i class="ti ti-check ms-2"></i>';
                
                let errorMessage = data.message || 'الدومين غير متصل بالسيرفر. يرجى التحقق من إعدادات DNS.';

                if (data.current_ns && data.required_ns) {
                    errorMessage += '<br><br><div style="background: #fff; padding: 12px; border-radius: 7px; margin-top: 8px;">';
                    errorMessage += '<strong style="color: var(--danger-color); font-size: 0.8rem;">Name Servers الحالية:</strong><br>';
                    data.current_ns.forEach(ns => {
                        errorMessage += `<code style="padding: 3px 7px; border-radius: 4px; margin: 2px; display: inline-block; font-size: 0.75rem;">${ns}</code><br>`;
                    });
                    errorMessage += '<br><strong style="color: var(--success-color); font-size: 0.8rem;">Name Servers المطلوبة:</strong><br>';
                    data.required_ns.forEach(ns => {
                        errorMessage += `<code style="padding: 3px 7px; border-radius: 4px; margin: 2px; display: inline-block; font-size: 0.75rem;">${ns}</code><br>`;
                    });
                    errorMessage += '</div>';
                }
                
                showAlert(errorMessage, 'danger');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = method === 'nameserver'
                ? 'تأكيد الربط النهائي <i class="ti ti-check ms-2"></i>'
                : 'تأكيد الربط <i class="ti ti-check ms-2"></i>';
            showAlert('حدث خطأ أثناء التحقق من الدومين. يرجى المحاولة مرة أخرى.', 'danger');
            console.error('Verification error:', error);
        });
    }

    window.copyText = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            const toast = document.createElement('div');
            toast.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 10px 20px; border-radius: 7px; box-shadow: 0 3px 10px rgba(0,0,0,0.12); z-index: 9999; font-size: 0.8rem;';
            toast.innerHTML = '<i class="ti ti-check me-2"></i>تم النسخ!';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        });
    };
});
</script>
@endsection
