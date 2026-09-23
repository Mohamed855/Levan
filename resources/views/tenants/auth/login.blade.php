@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'تسجيل الدخول')


@section('page-style')
    @vite(['resources/assets/css/tenants/auth/login.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/tenants/auth/login.js'])
@endsection

@section('content')

    <body>
        <div class="login-container">
            <!-- Left Side - Slider -->
            <div class="slider-section">
                <div class="slider-container">

                    <!-- Slide 1 -->
                    <div class="slide active">
                        <div class="slide-header">
                            <img src="{{ asset('img/Levan-logo.svg') }}" alt="Musnad Logo" class="brand-logo-img">
                        </div>

                        <div class="slide-welcome">مرحبا بك 👋</div>

                        <div class="slide-subtitle-group">
                            <div class="slide-subtitle-short">
                                قم بتسجيل الدخول للوصول إلى حسابك
                            </div>
                            <div class="slide-divider"></div>

                            <div class="slide-subtitle-long">
                                نظام متكامل لتحصيل الديون ومتابعة العملاء وإصدار التقارير التفصيلية بكفاءة عالية
                            </div>
                        </div>

                        <div class="slide-icon">
                            <img src="{{ asset('img/one.png') }}" alt="Smart Dashboard" class="slide-img">
                        </div>

                        <h3 class="slide-title">لوحة القيادة الذكية</h3>
                        <p class="slide-description">
                            نظرة شاملة لحظية على مؤشرات الأداء ونشاطات التحصيل والمهام الجارية
                        </p>
                    </div>

                    <!-- Slide 2 -->
                    <div class="slide">
                        <div class="slide-header">
                            <img src="{{ asset('img/Levan-logo.svg') }}" alt="Musnad Logo" class="brand-logo-img">
                        </div>

                        <div class="slide-welcome">مرحبا بك 👋</div>

                        <div class="slide-subtitle-group">
                            <div class="slide-subtitle-short">
                                قم بتسجيل الدخول للوصول إلى حسابك
                            </div>
                            <div class="slide-divider"></div>
                            <div class="slide-subtitle-long">
                                نظام متكامل لتحصيل الديون ومتابعة العملاء وإصدار التقارير التفصيلية بكفاءة عالية
                            </div>
                        </div>

                        <div class="slide-icon">
                            <img src="{{ asset('img/tow.png') }}" alt="Smart Distribution" class="slide-img">
                        </div>

                        <h3 class="slide-title">توزيع ذكي وعادل</h3>
                        <p class="slide-description">
                            نظام توزيع المحافظ بين الموظفين حسب المبالغ أو عدد العملاء لضمان العدالة
                        </p>
                    </div>

                    <!-- Slide 3 -->
                    <div class="slide">
                        <div class="slide-header">
                            <img src="{{ asset('img/Levan-logo.svg') }}" alt="Musnad Logo" class="brand-logo-img">
                        </div>

                        <div class="slide-welcome">مرحبا بك 👋</div>

                        <div class="slide-subtitle-group">
                            <div class="slide-subtitle-short">
                                قم بتسجيل الدخول للوصول إلى حسابك
                            </div>
                            <div class="slide-divider"></div>
                            <div class="slide-subtitle-long">
                                نظام متكامل لتحصيل الديون ومتابعة العملاء وإصدار التقارير التفصيلية بكفاءة عالية
                            </div>
                        </div>

                        <div class="slide-icon">
                            <img src="{{ asset('img/three.png') }}" alt="Competition Board" class="slide-img">
                        </div>

                        <h3 class="slide-title">لوحة التميز التنافسية</h3>
                        <p class="slide-description">
                            لوحة أداء تفاعلية لعرض تقدم الموظفين لحظياً وتحفيز التنافس البناء
                        </p>
                    </div>

                    <div class="slider-dots">
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-form-section">
                <div class="login-card">
                    <h2 class="login-title">تسجيل الدخول</h2>

                    @if (session('status'))
                        <div style="color: #4cc1bb; text-align: center; margin-bottom: 15px; font-size: 13px;">
                            {{ session('status') }}
                        </div>
                    @endif

                    @php
                        $currentHost = request()->getHost();
                        $centralDomains = array_map(function ($domain) {
                            return str_replace(['http://', 'https://', 'www.'], '', $domain);
                        }, config('tenancy.central_domains', []));

                        $isCentralDomain =
                            in_array($currentHost, $centralDomains) || in_array('www.' . $currentHost, $centralDomains);

                        $loginAction = $isCentralDomain ? url('/' . tenant('id') . '/login') : url('/login');
                    @endphp

                    <form id="formAuthentication" action="{{ $loginAction }}" method="POST" novalidate>
                        @csrf
                        <div class="form-group">
                            <label for="email" class="form-label">البريد الإلكتروني أو الهاتف</label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="الرجاء إدخال البريد الإلكتروني" value="{{ old('email') }}" required autofocus
                                autocomplete="username">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">كلمة المرور</label>
                            <div class="password-toggle">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="············" required autocomplete="current-password">
                                <i class="fas fa-eye toggle-icon" id="togglePassword"></i>
                            </div>
                            @error('password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="remember-forgot">
                            <div class="remember-me">
                                <input type="checkbox" id="remember_me" name="remember">
                                <label for="remember_me">تذكرني</label>
                            </div>
                            <a href="{{ tenant('password.request') }}" class="forgot-password">
                                نسيت كلمة المرور ؟
                            </a>
                        </div>

                        <button class="btn-login" type="submit">تسجيل الدخول</button>
                    </form>

                    <div class="footer-text">
                        <p>جميع الحقوق محفوظة 2026 ©</p>
                        <a href="https://e-tec.sa" class="footer-link">
                            إتمام لتقنية نظم المعلومات
                            <img src="{{ asset('img/logo-footer.png') }}" alt="Logo" class="footer-logo">
                        </a>
                        <p>V.{{ config('app.version') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
@endsection
