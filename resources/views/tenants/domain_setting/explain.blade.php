@extends('tenants.layouts.layoutMaster')

@section('title', 'شرح ربط الدومين')

<style>
    /* ===== Global Styles ===== */
    :root {
        --primary-color: #372632;
        --primary-light: #3d4261;
        --primary-dark: #1f2132;
        --accent-color: #5a6fa8;
        --accent-light: #7a8fc8;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --text-primary: #372632;
        --text-secondary: #6c757d;
        --text-muted: #9ca3af;
        --bg-light: #f8f9fc;
        --bg-white: #ffffff;
        --border-color: #e5e7eb;
        --shadow-sm: 0 2px 6px rgba(47, 51, 73, 0.06);
        --shadow-md: 0 3px 12px rgba(47, 51, 73, 0.1);
        --shadow-lg: 0 6px 20px rgba(47, 51, 73, 0.12);
    }

    body {
        background: var(--bg-light);
        color: var(--text-primary);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        font-size: 14px;
    }

    /* ===== Hero Section (Smaller) ===== */
    .guide-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: var(--bg-white);
        padding: 50px 20px;
        text-align: center;
        border-radius: 0 0 30px 30px;
        box-shadow: var(--shadow-md);
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }

    .guide-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        opacity: 0.3;
    }

    .guide-hero .container {
        position: relative;
        z-index: 1;
    }

    .guide-hero h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .guide-hero p {
        font-size: 1rem;
        opacity: 0.95;
        max-width: 600px;
        margin: 0 auto;
    }

    .guide-hero i {
        font-size: 50px;
        margin-bottom: 15px;
    }

    /* ===== Info Boxes (Smaller) ===== */
    .info-box {
        background: var(--bg-white);
        border-right: 4px solid var(--accent-color);
        padding: 15px 18px;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 20px;
        transition: all 0.3s ease;
        font-size: 13px;
    }

    .info-box:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .info-box i {
        font-size: 22px;
        color: var(--accent-color);
        margin-left: 10px;
        vertical-align: middle;
    }

    .info-box.success {
        border-right-color: var(--success-color);
        background: linear-gradient(to left, rgba(76, 175, 80, 0.04) 0%, var(--bg-white) 100%);
    }

    .info-box.success i {
        color: var(--success-color);
    }

    .info-box.warning {
        border-right-color: var(--warning-color);
        background: linear-gradient(to left, rgba(255, 152, 0, 0.04) 0%, var(--bg-white) 100%);
    }

    .info-box.warning i {
        color: var(--warning-color);
    }

    .info-box.info {
        border-right-color: var(--info-color);
        background: linear-gradient(to left, rgba(33, 150, 243, 0.04) 0%, var(--bg-white) 100%);
    }

    .info-box.info i {
        color: var(--info-color);
    }

    .info-box strong {
        color: var(--primary-color);
        font-size: 0.95rem;
    }

    .info-box p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    /* ===== Step Cards (Smaller) ===== */
    .step-card {
        background: var(--bg-white);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        position: relative;
        transition: all 0.4s ease;
    }

    .step-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateX(-3px);
        border-color: var(--accent-light);
    }

    .step-number {
        position: absolute;
        top: -15px;
        right: 30px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        color: var(--bg-white);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
        box-shadow: var(--shadow-md);
        border: 4px solid var(--bg-light);
    }

    .step-header {
        display: flex;
        align-items: center;
        margin-bottom: 18px;
        gap: 15px;
    }

    .step-icon {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, rgba(47, 51, 73, 0.08) 0%, rgba(90, 111, 168, 0.08) 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .step-icon i {
        font-size: 28px;
        color: var(--primary-color);
    }

    .step-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
    }

    .step-content {
        padding-right: 15px;
        font-size: 13px;
    }

    .step-content p {
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.7;
        margin-bottom: 15px;
    }

    .step-content h5 {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 1rem;
        margin-top: 20px;
        margin-bottom: 12px;
    }

    .step-content ul, .step-content ol {
        color: var(--text-secondary);
        padding-right: 20px;
        line-height: 1.8;
        font-size: 13px;
    }

    .step-content li {
        margin-bottom: 10px;
        padding-right: 8px;
    }

    .step-content li strong {
        color: var(--primary-color);
    }

    /* ===== Timeline (Smaller) ===== */
    .timeline {
        position: relative;
        padding-right: 35px;
        margin: 20px 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        right: 7px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, var(--accent-color), var(--accent-light));
        border-radius: 10px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 25px;
        padding-right: 30px;
    }

    .timeline-dot {
        position: absolute;
        right: 0;
        top: 4px;
        width: 16px;
        height: 16px;
        background: var(--bg-white);
        border: 3px solid var(--accent-color);
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(90, 111, 168, 0.1);
    }

    .timeline-item strong {
        display: block;
        color: var(--primary-color);
        font-size: 0.9rem;
        margin-bottom: 4px;
    }

    .timeline-item .text-muted {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin: 0;
    }

    /* ===== Tables (Smaller) ===== */
    .ns-table {
        width: 100%;
        background: var(--bg-white);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        margin: 20px 0;
        font-size: 13px;
    }

    .ns-table thead {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    }

    .ns-table thead th {
        color: var(--bg-white);
        font-weight: 600;
        padding: 12px 15px;
        text-align: right;
        font-size: 0.9rem;
    }

    .ns-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.3s ease;
    }

    .ns-table tbody tr:hover {
        background: rgba(90, 111, 168, 0.04);
    }

    .ns-table tbody tr:last-child {
        border-bottom: none;
    }

    .ns-table tbody td {
        padding: 14px 15px;
        color: var(--text-secondary);
        vertical-align: middle;
    }

    .ns-table tbody td strong {
        color: var(--primary-color);
        font-size: 0.9rem;
    }

    .ns-table code {
        background: var(--bg-light);
        padding: 6px 12px;
        border-radius: 6px;
        color: var(--primary-color);
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        border: 1px solid var(--border-color);
    }

    /* ===== Code Box (Smaller) ===== */
    .code-box {
        background: var(--primary-dark);
        padding: 18px;
        border-radius: 10px;
        margin: 20px 0;
        position: relative;
        box-shadow: var(--shadow-md);
        border: 1px solid rgba(90, 111, 168, 0.2);
    }

    .code-box code {
        color: #a8dadc;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        display: block;
        line-height: 1.7;
        background: none;
        padding: 0;
        border: none;
    }

    .copy-btn {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(255, 255, 255, 0.1);
        color: var(--bg-white);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .copy-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .copy-btn i {
        margin-left: 4px;
    }

    /* ===== Buttons (Smaller) ===== */
    .btn {
        background: var(--bg-white);
        color: var(--primary-color);
        padding: 10px 22px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: var(--shadow-sm);
        font-size: 0.9rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        color: var(--primary-color);
    }

    .btn-outline-primary {
        background: transparent;
        border-color: var(--accent-color);
        color: var(--accent-color);
    }

    .btn-outline-primary:hover {
        background: var(--accent-color);
        color: var(--bg-white);
    }

    .btn-sm {
        padding: 6px 14px;
        font-size: 0.8rem;
    }

    /* ===== Accordion (Smaller) ===== */
    .accordion-item {
        border: 1px solid var(--border-color);
        margin-bottom: 12px;
    }

    .accordion-button {
        background: var(--bg-white);
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.9rem;
        padding: 14px 20px;
        transition: all 0.3s ease;
    }

    .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, rgba(47, 51, 73, 0.05) 0%, rgba(90, 111, 168, 0.05) 100%);
        color: var(--primary-color);
        box-shadow: none;
    }

    .accordion-button:hover {
        background: rgba(90, 111, 168, 0.06);
    }

    .accordion-button:focus {
        box-shadow: 0 0 0 0.2rem rgba(90, 111, 168, 0.2);
    }

    .accordion-body {
        padding: 18px 20px;
        background: var(--bg-white);
        color: var(--text-secondary);
        line-height: 1.7;
        font-size: 13px;
    }

    .accordion-body ul {
        margin: 12px 0;
        padding-right: 20px;
    }

    .accordion-body li {
        margin-bottom: 8px;
        color: var(--text-secondary);
    }

    /* ===== Need Help Box (Smaller) ===== */
    .need-help-box {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        color: var(--bg-white);
        text-align: center;
        padding: 40px 25px;
        border-radius: 15px;
        margin-top: 40px;
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }

    .need-help-box::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
        background-size: 25px 25px;
        animation: moveBackground 20s linear infinite;
    }

    @keyframes moveBackground {
        0% {
            transform: translate(0, 0);
        }
        100% {
            transform: translate(25px, 25px);
        }
    }

    .need-help-box h3 {
        color: var(--bg-white);
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 12px;
        position: relative;
        z-index: 1;
    }

    .need-help-box p {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.95rem;
        max-width: 500px;
        margin: 0 auto 25px;
        position: relative;
        z-index: 1;
    }

    .need-help-box i {
        font-size: 50px;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }

    .need-help-box .btn {
        position: relative;
        z-index: 1;
    }

    /* ===== General Info Card ===== */
    .info-card {
        background: var(--bg-white);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .info-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-3px);
    }

    .info-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--bg-light);
    }

    .info-card-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, rgba(47, 51, 73, 0.08) 0%, rgba(90, 111, 168, 0.08) 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-card-icon i {
        font-size: 24px;
        color: var(--primary-color);
    }

    .info-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
    }

    .info-card-content {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.7;
    }

    .info-card-content ul {
        margin: 10px 0;
        padding-right: 20px;
    }

    .info-card-content li {
        margin-bottom: 8px;
    }

    /* ===== Badge ===== */
    .badge-custom {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 3px;
    }

    .badge-primary {
        background: rgba(47, 51, 73, 0.1);
        color: var(--primary-color);
    }

    .badge-success {
        background: rgba(76, 175, 80, 0.1);
        color: var(--success-color);
    }

    .badge-warning {
        background: rgba(255, 152, 0, 0.1);
        color: var(--warning-color);
    }

    .badge-info {
        background: rgba(33, 150, 243, 0.1);
        color: var(--info-color);
    }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        .guide-hero h1 {
            font-size: 1.6rem;
        }

        .guide-hero p {
            font-size: 0.9rem;
        }

        .guide-hero i {
            font-size: 40px;
        }

        .step-card {
            padding: 20px 15px;
        }

        .step-number {
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
            top: -12px;
            right: 20px;
        }

        .step-title {
            font-size: 1.1rem;
        }

        .step-icon {
            width: 50px;
            height: 50px;
        }

        .step-icon i {
            font-size: 24px;
        }

        .timeline {
            padding-right: 25px;
        }

        .need-help-box {
            padding: 30px 15px;
        }

        .need-help-box h3 {
            font-size: 1.3rem;
        }

        .need-help-box i {
            font-size: 40px;
        }
    }

    /* ===== Animations ===== */
    .animate__animated {
        animation-duration: 0.6s;
        animation-fill-mode: both;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes bounceIn {
        from, 20%, 40%, 60%, 80%, to {
            animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
        }
        0% {
            opacity: 0;
            transform: scale3d(.3, .3, .3);
        }
        50% {
            transform: scale3d(1.05, 1.05, 1.05);
        }
        to {
            opacity: 1;
            transform: scale3d(1, 1, 1);
        }
    }

    .animate__fadeInDown {
        animation-name: fadeInDown;
    }

    .animate__fadeInUp {
        animation-name: fadeInUp;
    }

    .animate__fadeInRight {
        animation-name: fadeInRight;
    }

    .animate__fadeInLeft {
        animation-name: fadeInLeft;
    }

    .animate__bounceIn {
        animation-name: bounceIn;
    }

    /* ===== Custom Scrollbar ===== */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--bg-light);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--accent-color);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-color);
    }
</style>

@section('content')
<!-- Hero Section -->
<div class="guide-hero">
    <div class="container">
        <i class="ti ti-plug-connected animate__animated animate__bounceIn"></i>
        <h1 class="animate__animated animate__fadeInDown" style="color:white">دليل ربط ونقل الدومين</h1>
        <p class="animate__animated animate__fadeInUp">اتبع هذه الخطوات البسيطة لربط دومينك بالسيرفر أو نقله من مزود آخر</p>
    </div>
</div>

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Quick Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="info-box">
                <i class="ti ti-info-circle"></i>
                <strong>ما هو ربط الدومين؟</strong>
                <p class="mb-0 mt-2">ربط الدومين هو عملية توجيه اسم النطاق الذي اشتريته إلى السيرفر الذي يستضيف موقعك، مما يسمح للزوار بالوصول إلى موقعك عن طريق كتابة اسم الدومين بدلاً من عنوان IP.</p>
            </div>
        </div>
    </div>

    <!-- General Info Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="ti ti-file-text"></i>
                    </div>
                    <h4 class="info-card-title">شروط ربط الدومين</h4>
                </div>
                <div class="info-card-content">
                    <ul>
                        <li>أن يكون الدومين مسجلاً باسمك</li>
                        <li>الوصول إلى لوحة تحكم الدومين</li>
                        <li>معرفة Name Servers الخاصة بالسيرفر</li>
                        <li>الدومين غير محظور أو منتهي</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="ti ti-clock"></i>
                    </div>
                    <h4 class="info-card-title">مدة التفعيل</h4>
                </div>
                <div class="info-card-content">
                    <span class="badge-custom badge-success" style="color:black">سريع: 2-8 ساعات</span>
                    <span class="badge-custom badge-warning" style="color:black">عادي: 24 ساعة</span>
                    <span class="badge-custom badge-info" style="color:black">أقصى: 48 ساعة</span>
                    <p class="mt-3 mb-0" style="color:black">تعتمد المدة على مزود الدومين والسيرفرات العالمية</p>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="ti ti-alert-triangle"></i>
                    </div>
                    <h4 class="info-card-title">ملاحظات مهمة</h4>
                </div>
                <div class="info-card-content">
                    <ul>
                        <li>احتفظ بنسخة احتياطية من موقعك</li>
                        <li>لا تحذف الاستضافة القديمة قبل اكتمال النقل</li>
                        <li>تحقق من تاريخ انتهاء الدومين</li>
                        <li>تأكد من صحة بيانات التواصل</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: ربط الدومين -->
    <h3 class="mb-4" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;">
        <i class="ti ti-link me-2"></i>خطوات ربط الدومين
    </h3>

    <!-- Step 1 -->
    <div class="step-card animate__animated animate__fadeInRight">
        <div class="step-number">1</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-login"></i>
            </div>
            <h3 class="step-title">تسجيل الدخول</h3>
        </div>
        <div class="step-content">
            <p>سجل دخولك إلى حساب الشركة التي اشتريت منها الدومين (GoDaddy, Namecheap, إلخ).</p>

            <div class="timeline" style="height:220px">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <strong style="margin-right: 19px;">افتح موقع الشركة</strong>
                    <p class="text-muted">انتقل إلى موقع مزود الدومين</p>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <strong style="margin-right: 19px;">اضغط تسجيل الدخول</strong>
                    <p class="text-muted">عادة في أعلى الصفحة</p>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <strong style="margin-right: 19px;">أدخل البيانات</strong>
                    <p class="text-muted">البريد وكلمة المرور</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2 -->
    <div class="step-card animate__animated animate__fadeInLeft">
        <div class="step-number">2</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-world"></i>
            </div>
            <h3 class="step-title">إعدادات الدومين</h3>
        </div>
        <div class="step-content">
            <p>من لوحة التحكم، اختر قسم "النطاقات" أو "Domains"، ثم اختر الدومين واضغط "إدارة" أو "Manage".</p>
        </div>
    </div>

    <!-- Step 3 -->
    <div class="step-card animate__animated animate__fadeInRight">
        <div class="step-number">3</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-server"></i>
            </div>
            <h3 class="step-title">تعديل Name Servers</h3>
        </div>
        <div class="step-content">
            <p>ابحث عن "Nameservers" أو "DNS Management" واختر "Custom DNS"، ثم أدخل Name Servers التالية:</p>

            <div class="info-box success">
                <i class="ti ti-check-circle"></i>
                <strong>Name Servers الخاصة بنا:</strong>
            </div>

            <table class="ns-table">
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>القيمة</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Name Server 1</strong></td>
                        <td>
                            <code>ns1.levan.com</code>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="copyToClipboard('ns1.levan.com')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Name Server 2</strong></td>
                        <td>
                            <code>ns2.levan.com</code>
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="copyToClipboard('ns2.levan.com')">
                                <i class="ti ti-copy"></i> نسخ
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- <div class="code-box">
                <button type="button" class="copy-btn" onclick="copyToClipboard('ns1.levan.com\nns2.levan.com')">
                    <i class="ti ti-copy"></i> نسخ الكل
                </button>
                <div style="margin-top: 25px;">
                    <code>Nameserver 1: ns1.levan.com</code><br>
                    <code>Nameserver 2: ns2.levan.com</code>
                </div>
            </div> --}}
        </div>
    </div>

    <!-- Step 4 -->
    <div class="step-card animate__animated animate__fadeInLeft">
        <div class="step-number">4</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-device-floppy"></i>
            </div>
            <h3 class="step-title">حفظ التغييرات</h3>
        </div>
        <div class="step-content">
            <p>اضغط "حفظ" أو "Save" أو "Update Nameservers" وانتظر رسالة التأكيد.</p>
            <div class="info-box success">
                <i class="ti ti-check"></i>
                <strong>تم!</strong> إذا ظهرت رسالة نجاح، فقد أتممت العملية بنجاح.
            </div>
        </div>
    </div>

    <!-- Section: نقل الدومين -->
    <h3 class="mb-4 mt-5" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;">
        <i class="ti ti-transfer me-2"></i>خطوات نقل الدومين
    </h3>

    <div class="row mb-4">
        <div class="col-12">
            <div class="info-box info">
                <i class="ti ti-info-circle"></i>
                <strong>ما هو نقل الدومين؟</strong>
                <p class="mb-0 mt-2">نقل الدومين (Domain Transfer) هو عملية نقل ملكية وإدارة الدومين من شركة تسجيل إلى أخرى مع الاحتفاظ بنفس اسم الدومين.[web:34][web:35]</p>
            </div>
        </div>
    </div>

    <!-- Transfer Step 1 -->
    <div class="step-card animate__animated animate__fadeInRight">
        <div class="step-number">1</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-lock-open"></i>
            </div>
            <h3 class="step-title">فك قفل الدومين</h3>
        </div>
        <div class="step-content">
            <p>الدومينات مقفلة افتراضياً لحمايتها. لنقل الدومين يجب فك القفل أولاً.[web:36][web:39]</p>
            
            <h5>خطوات فك القفل:</h5>
            <ol>
                <li>سجل دخول إلى حساب الدومين الحالي</li>
                <li>اذهب إلى إعدادات الدومين</li>
                <li>ابحث عن "Registrar Lock" أو "Domain Lock"</li>
                <li>اضغط "Disable" أو "Unlock Domain"</li>
            </ol>

            <div class="info-box warning">
                <i class="ti ti-alert-triangle"></i>
                <strong>مهم:</strong> الدومين يجب أن يكون مسجلاً منذ 60 يوماً على الأقل قبل نقله.[web:34]
            </div>
        </div>
    </div>

    <!-- Transfer Step 2 -->
    <div class="step-card animate__animated animate__fadeInLeft">
        <div class="step-number">2</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-key"></i>
            </div>
            <h3 class="step-title">الحصول على كود التحويل (EPP/Auth Code)</h3>
        </div>
        <div class="step-content">
            <p>كود التحويل (Authorization Code أو EPP Code) هو رمز أمان مكون من أحرف وأرقام مطلوب لإتمام عملية النقل.[web:35][web:38]</p>

            <h5>كيفية الحصول على الكود:</h5>
            <ol>
                <li>من لوحة تحكم الدومين الحالية</li>
                <li>اختر الدومين المراد نقله</li>
                <li>ابحث عن "Transfer Out" أو "Get Auth Code"</li>
                <li>اضغط على الزر واحتفظ بالكود</li>
            </ol>

            <div class="info-box info">
                <i class="ti ti-bulb"></i>
                <strong>ملاحظة:</strong> قد يتم إرسال الكود إلى بريدك الإلكتروني المسجل.[web:38]
            </div>
        </div>
    </div>

    <!-- Transfer Step 3 -->
    <div class="step-card animate__animated animate__fadeInRight">
        <div class="step-number">3</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-send"></i>
            </div>
            <h3 class="step-title">طلب النقل</h3>
        </div>
        <div class="step-content">
            <p>تواصل معنا وقدم المعلومات التالية لبدء عملية النقل:</p>

            <ul>
                <li><strong>اسم الدومين:</strong> الدومين الذي تريد نقله</li>
                <li><strong>كود التحويل (EPP/Auth Code):</strong> الكود الذي حصلت عليه</li>
                <li><strong>البريد الإلكتروني:</strong> بريدك المسجل لدى الشركة الحالية</li>
            </ul>

            <div class="info-box success">
                <i class="ti ti-check"></i>
                <strong>سنقوم نحن بـ:</strong> إرسال طلب النقل إلى الشركة الحالية نيابة عنك.
            </div>
        </div>
    </div>

    <!-- Transfer Step 4 -->
    <div class="step-card animate__animated animate__fadeInLeft">
        <div class="step-number">4</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-mail-opened"></i>
            </div>
            <h3 class="step-title">تأكيد النقل</h3>
        </div>
        <div class="step-content">
            <p>ستصلك رسالة بريد إلكتروني من الشركة الحالية للتأكيد على طلب النقل.[web:35]</p>

            <h5>ما يجب فعله:</h5>
            <ol>
                <li>افتح البريد الإلكتروني المرسل</li>
                <li>اضغط على رابط التأكيد أو زر "Approve Transfer"</li>
                <li>قد يطلب منك تسجيل الدخول للتأكيد</li>
            </ol>

            <div class="info-box warning">
                <i class="ti ti-clock"></i>
                <strong>المدة الزمنية:</strong> عملية النقل تستغرق من 5 إلى 7 أيام للدومينات العادية (.com, .net, .org).[web:34]
            </div>
        </div>
    </div>

    <!-- Transfer Step 5 -->
    <div class="step-card animate__animated animate__fadeInRight">
        <div class="step-number">5</div>
        <div class="step-header">
            <div class="step-icon">
                <i class="ti ti-circle-check"></i>
            </div>
            <h3 class="step-title">اكتمال النقل</h3>
        </div>
        <div class="step-content">
            <p>بعد التأكيد، ستتم عملية النقل تلقائياً وستصلك رسالة تأكيد باكتمال النقل.</p>

            <div class="info-box success">
                <i class="ti ti-check-circle"></i>
                <strong>تمت العملية بنجاح!</strong> الدومين الآن مسجل لدينا ويمكنك إدارته من لوحة التحكم الخاصة بك.
            </div>

            <h5 class="mt-3">بعد اكتمال النقل:</h5>
            <ul>
                <li>تحقق من إعدادات DNS</li>
                <li>تأكد من عمل الموقع بشكل صحيح</li>
                <li>حدّث معلومات التواصل إذا لزم الأمر</li>
            </ul>
        </div>
    </div>

    <!-- Common Issues -->
    <div class="row mt-4">
        <div class="col-12">
            <h3 class="mb-3" style="color: var(--primary-color); font-weight: 700; font-size: 1.3rem;">
                <i class="ti ti-alert-circle me-2"></i>المشاكل الشائعة وحلولها
            </h3>

            <div class="accordion" id="troubleshootingAccordion">
                <div class="accordion-item" style="border-radius: 10px; overflow: hidden;">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#issue1">
                            الموقع لا يعمل بعد 48 ساعة
                        </button>
                    </h2>
                    <div id="issue1" class="accordion-collapse collapse show" data-bs-parent="#troubleshootingAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li>تأكد من إدخال Name Servers بشكل صحيح</li>
                                <li>تحقق من عدم وجود مسافات زائدة</li>
                                <li>امسح الكاش من المتصفح</li>
                                <li>تواصل مع الدعم الفني</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 12px;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue2">
                            رفض طلب نقل الدومين
                        </button>
                    </h2>
                    <div id="issue2" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                        <div class="accordion-body">
                            <p>الأسباب المحتملة:[web:36]</p>
                            <ul>
                                <li>الدومين مازال مقفلاً (Domain Lock)</li>
                                <li>كود التحويل (EPP) غير صحيح</li>
                                <li>الدومين مسجل منذ أقل من 60 يوماً</li>
                                <li>البريد الإلكتروني غير صحيح</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 12px;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue3">
                            لم أستلم بريد تأكيد النقل
                        </button>
                    </h2>
                    <div id="issue3" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li>تحقق من مجلد البريد المزعج (Spam)</li>
                                <li>تأكد من صحة البريد الإلكتروني المسجل</li>
                                <li>انتظر 24 ساعة ثم تواصل مع الدعم</li>
                                <li>تحقق من إعدادات تصفية البريد</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item" style="border-radius: 10px; overflow: hidden; margin-top: 12px;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue4">
                            لا أجد خيار Custom Nameservers
                        </button>
                    </h2>
                    <div id="issue4" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                        <div class="accordion-body">
                            <p>قد يكون بأسماء مختلفة:</p>
                            <ul>
                                <li>"Change Nameservers"</li>
                                <li>"DNS Management"</li>
                                <li>"Custom DNS"</li>
                                <li>"Use custom nameservers"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Need Help Box -->
    <div class="need-help-box animate__animated">
        <i class="ti ti-headset"></i>
        <h3>هل تحتاج مساعدة؟</h3>
        <p>فريق الدعم الفني جاهز لمساعدتك في ربط أو نقل الدومين</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ tenant_route_name('tickets.index') }}" class="btn" style="color:white">
                <i class="ti ti-message-circle" style="font-size: 39px;"></i>
                تواصل مع الدعم
            </a>
                <a href="{{ tenant_route_name('domains.faq') }}" class="btn" style="background: transparent;  color: white;">
                <i class="ti ti-help-circle" style="font-size: 39px;"></i>
                الأسئلة الشائعة
            </a>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-5 alert alert-success';
            toast.style.zIndex = '9999';
            toast.style.fontSize = '0.9rem';
            toast.innerHTML = '<i class="ti ti-check me-2"></i>تم النسخ بنجاح!';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 2000);
        });
    }
</script>
@endsection
