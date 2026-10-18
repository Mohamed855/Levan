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
                    <a href="javascript:void(0)" class="social-link" aria-label="Facebook">
                        <i class="ti ti-brand-facebook"></i>
                    </a>
                    <a href="javascript:void(0)" class="social-link" aria-label="X (Twitter)">
                        <i class="ti ti-brand-x"></i>
                    </a>
                    <a href="javascript:void(0)" class="social-link" aria-label="LinkedIn">
                        <i class="ti ti-brand-linkedin"></i>
                    </a>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="footer-nav-col">
                <h4 class="footer-heading">روابط سريعة</h4>
                <ul class="footer-links">
                    <li>
                        <a href="{{ route('site.index') }}#home">
                            <i class="ti ti-chevron-left"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('site.index') }}#services">
                            <i class="ti ti-chevron-left"></i>
                            المميزات
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('site.index') }}#pricing">
                            <i class="ti ti-chevron-left"></i>
                            الباقات والأسعار
                        </a>
                    </li>
                    {{-- <li>
                        <a href="{{ route('site.index') }}#portfolios">
                            <i class="ti ti-chevron-left"></i>
                            المحافظ
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('site.index') }}#clients">
                            <i class="ti ti-chevron-left"></i>
                            عملاؤنا
                        </a>
                    </li> --}}
                    <li>
                        <a href="{{ route('site.index') }}#faq">
                            <i class="ti ti-chevron-left"></i>
                            الأسئلة الشائعة
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('site.index') }}#contact">
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
                            الرياض، طريق الأمير محمد بن عبدالعزيز<br>
                            حي السليمانية، 12242
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="ti ti-phone"></i>
                        </div>
                        <div class="contact-text">
                            <a href="tel:0500181100" dir="ltr">0500181100</a>
                        </div>
                    </li>
                    <li>
                        <div class="contact-icon">
                            <i class="ti ti-mail"></i>
                        </div>
                        <div class="contact-text">
                            <a href="mailto:info@e-tec.sa">info@e-tec.sa</a>
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
