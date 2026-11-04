<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
    <div class="footer-top position-relative overflow-hidden z-1">
        <img src="{{ asset('static/dashboard/backgrounds/cta-bg-secondary3.png') }}" alt="footer bg"
            class="footer-bg banner-bg-img z-n1" data-app-light-img="backgrounds/cta-bg-secondary3.png"
            data-app-dark-img="backgrounds/cta-bg-secondary3.png" />
        <div class="container">
            <div class="row gx-0 gy-6 g-lg-10">
                <div class="col-lg-5">
                    <a href="{{ url('/') }}" class="app-brand-link mb-6">
                        <x-partials.admin-logo :height="20" withbg="fill:#fff;" />
                        <span class="app-brand-text demo footer-link fw-bold ms-2 ps-1">ليفـان</span>
                    </a>
                    <p class="footer-text footer-logo-description mb-6">
                        منصة سحابية متقدمة لإدارة المزادات والإسنادات القضائية والأصول العقارية، تُمكّن المنشآت من ضبط
                        وتنظيم عملياتها وتحقيق أعلى معايير الشفافية والكفاءة.
                    </p>

                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-6">الدعم</h6>
                    <ul class="list-unstyled">
                        {{-- <li class="mb-4">
                            <a href="javascript:void(0);" class="footer-link">مركز المساعدة</a>
                        </li> --}}
                        <li class="mb-4">
                            <a href="{{ route('site.index') }}#landingContact" class="footer-link">الدعم الفني</a>
                        </li>

                        <li class="mb-4">
                            <a href="{{ route('site.privacy.policy') }}" class="footer-link">سياسة الاستخدام</a>
                        </li>

                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h6 class="footer-title mb-6">تواصل معنا</h6>
                    <ul class="list-unstyled">
                        <li class="mb-4">
                            <span class="footer-link">
                                <i class="bx bx-phone me-2"></i>
                                0500181100
                            </span>
                        </li>
                        <li class="mb-4">
                            <span class="footer-link">
                                <i class="bx bx-envelope me-2"></i>
                                info@e-tec.sa
                            </span>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom py-3 py-md-5">
        <div
            class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
            <div class="mb-2 mb-md-0 d-inline-flex align-items-center flex-wrap gap-1">
                <span class="footer-bottom-text">جميع الحقوق محفوظة
                    <script>
                        document.write(new Date().getFullYear());
                    </script>
                    ©
                </span>
                <a href="https://e-tec.sa" target="_blank" class="fw-medium text-white d-inline-flex align-items-center gap-1">
                    <span>إتمام لتقنية نظم المعلومات</span>
                    <img src="{{ asset('static/brand/logos/logo-footer.png') }}" alt="إتمام لتقنية نظم المعلومات" class="footer-logo" style="height: 18px; width: auto; object-fit: contain; vertical-align: middle;">
                </a>
            </div>

        </div>
    </div>
</footer>
<!-- Footer: End -->
