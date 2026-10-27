@php
    $officeAddress = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('office_address');
    $phoneNumber = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('phone_number');
    $email = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('email');
    $phoneLink = preg_replace('/[^0-9+]/', '', (string) $phoneNumber);
    $facebookUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('facebook_url');
    $xUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('x_url');
    $linkedinUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('linkedin_url');
@endphp

<!-- Site Footer -->
<footer class="site-footer" role="contentinfo">
    <div class="container footer-main">
        <div class="footer-grid">
            <!-- Col 1: Brand Info & Socials -->
            <div class="footer-brand">
                <a href="{{ route('site.index') }}" class="footer-logo" aria-label="لوت LOT">
                    <img src="{{ asset('static/brand/logos/lot/trans-profile-white.png') }}" alt="لوت LOT">
                </a>

                <p class="footer-tagline">
                    المنصة الرقمية المتقدمة لإدارة المزادات والأصول والمستندات بكفاءة واحترافية.
                </p>

                <div class="footer-social">
                    @if($facebookUrl)<a href="{{ $facebookUrl }}" class="social-link" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <i class="ti ti-brand-facebook"></i>
                    </a>@endif
                    @if($xUrl)<a href="{{ $xUrl }}" class="social-link" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer">
                        <i class="ti ti-brand-x"></i>
                    </a>@endif
                    @if($linkedinUrl)<a href="{{ $linkedinUrl }}" class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                        <i class="ti ti-brand-linkedin"></i>
                    </a>@endif
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="footer-nav-col">
                <h4 class="footer-heading">روابط سريعة</h4>
                <ul class="footer-links">
                    <li>
                        <a href="{{ route('site.index') }}">
                            <i class="ti ti-chevron-left"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li>
                        <a href="/#services">
                            <i class="ti ti-chevron-left"></i>
                            المميزات
                        </a>
                    </li>
                    <li>
                        <a href="/#pricing">
                            <i class="ti ti-chevron-left"></i>
                            الباقات والأسعار
                        </a>
                    </li>
                    <li>
                        <a href="/#faq">
                            <i class="ti ti-chevron-left"></i>
                            الأسئلة الشائعة
                        </a>
                    </li>
                    <li>
                        <a href="/#contact">
                            <i class="ti ti-chevron-left"></i>
                            تواصل معنا
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 3: Policies -->
            <div class="footer-nav-col">
                <h4 class="footer-heading">السياسات</h4>
                <ul class="footer-links">
                    <li>
                        <a href="{{ route('site.privacy.policy') }}">
                            <i class="ti ti-chevron-left"></i>
                            سياسة الاستخدام
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('site.trial') }}">
                            <i class="ti ti-chevron-left"></i>
                            التجربة المجانية
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Contact Info -->
            <div class="footer-contact-col">
                <h4 class="footer-heading">معلومات التواصل</h4>
                <ul class="footer-contact-list">
                    <li>
                        <div class="contact-icon">
                            <i class="ti ti-map-pin"></i>
                        </div>
                        <div class="contact-text">
                            {{ $officeAddress ?: '—' }}
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="ti ti-phone"></i>
                        </div>
                        <div class="contact-text">
                            @if ($phoneNumber)
                                <a href="tel:{{ $phoneLink }}" dir="ltr">{{ $phoneNumber }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="ti ti-mail"></i>
                        </div>
                        <div class="contact-text">
                            @if ($email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <div>
                جميع الحقوق محفوظة © {{ date('Y') }}
                <a href="{{ route('site.index') }}">لوت LOT</a>
            </div>
            <div>
                تطوير وتصميم
                <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="developer-credit">
                    إتمام لتقنية نظم المعلومات
                    <i class="ti ti-heart-filled"></i>
                </a>
            </div>
        </div>
    </div>
</footer>
