<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
    <div class="footer-top position-relative overflow-hidden z-1">
        <img src="{{ asset('static/dashboard/backgrounds/cta-bg-light3.png') }}"
            alt="footer bg" class="footer-bg banner-bg-img z-n1"
            data-app-light-img="backgrounds/cta-bg-light3.png"
            data-app-dark-img="backgrounds/cta-bg-light3.png" />
        <div class="container">
            <div class="row gx-0 gy-6 g-lg-10">
                <div class="col-lg-5">
                    <a href="{{ url('/') }}" class="app-brand-link mb-6">
                        <span class="app-brand-logo demo">@include('_partials.macros', [
                            'height' => 20,
                            'withbg' => "fill:
                                                              #fff;",
                        ])</span>
                        <span class="app-brand-text demo footer-link fw-bold ms-2 ps-1">ليفـان</span>
                    </a>
                    <p class="footer-text footer-logo-description mb-6">
                        حل تقني ذكي لإدارة عمليات التحصيل بكفاءة، عدالة، وتحفيز. منظومة رقمية متكاملة لتحقيق أعلى معدلات
                        الإنجاز.
                    </p>
                    {{-- <form class="footer-form">
            <label for="footer-email" class="small">اشترك في النشرة الإخبارية</label>
            <div class="d-flex mt-1">
              <input type="email" class="form-control rounded-0 rounded-start-bottom rounded-start-top"
                id="footer-email" placeholder="بريدك الإلكتروني" />
              <button type="submit" class="btn btn-primary shadow-none rounded-0 rounded-end-bottom rounded-end-top">
                اشتراك
              </button>
            </div>
          </form> --}}
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-6">المنصة</h6>
                    <ul class="list-unstyled">
                        <li class="mb-4">
                            <a href="#landingFeatures" class="footer-link">المميزات</a>
                        </li>
                        {{-- <li class="mb-4">
              <a href="#landingPricing" class="footer-link">الأسعار</a>
            </li> --}}
                        <li class="mb-4">
                            <a href="#landingFAQ" class="footer-link">الأسئلة الشائعة</a>
                        </li>
                        <li class="mb-4">
                            <a href="#landingContact" class="footer-link">تواصل معنا</a>
                        </li>
                        {{-- <li class="mb-4">
              <a href="{{url('/login')}}" class="footer-link">تسجيل الدخول</a>
            </li> --}}
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <h6 class="footer-title mb-6">الدعم</h6>
                    <ul class="list-unstyled">
                        <li class="mb-4">
                            <a href="javascript:void(0);" class="footer-link">مركز المساعدة</a>
                        </li>
                        <li class="mb-4">
                            <a href="javascript:void(0);" class="footer-link">الدعم الفني</a>
                        </li>
                        {{-- <li class="mb-4">
              <a href="javascript:void(0);" class="footer-link">دليل الاستخدام</a>
            </li> --}}
                        <li class="mb-4">
                            <a href="javascript:void(0);" class="footer-link">سياسة الخصوصية</a>
                        </li>
                        {{-- <li class="mb-4">
              <a href="javascript:void(0);" class="footer-link">شروط الاستخدام</a>
            </li> --}}
                    </ul>
                </div>
                <!--
        <div class="col-lg-3 col-md-4">
          <h6 class="footer-title mb-6">حمل التطبيق</h6>
          <a href="javascript:void(0);" class="d-block mb-4">متجر التطبيقات</a>
          <a href="javascript:void(0);" class="d-block">Google Play</a>
        </div>
        -->
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
            <div class="mb-2 mb-md-0">
                <span class="footer-bottom-text">جميع الحقوق محفوظة
                    <script>
                        document.write(new Date().getFullYear());
                    </script>
                    ©
                </span>
                <a href="https://e-tec.sa" target="_blank" class="fw-medium text-white text-white">إتمام لتقنية نظم
                    المعلومات،</a>
                <span class="footer-bottom-text"> ❤️</span>
            </div>
            <div>
                <a href="{{ config('variables.githubUrl') }}" class="me-3" target="_blank">
                    <i class="bx bxl-github" aria-hidden="true"></i><span class="visually-hidden">GitHub</span>
                </a>
                <a href="{{ config('variables.facebookUrl') }}" class="me-3" target="_blank">
                    <i class="bx bxl-facebook" aria-hidden="true"></i><span class="visually-hidden">Facebook</span>
                </a>
                <a href="{{ config('variables.twitterUrl') }}" class="me-3" target="_blank">
                    <i class="bx bxl-twitter" aria-hidden="true"></i><span class="visually-hidden">X</span>
                </a>
                <a href="{{ config('variables.instagramUrl') }}" target="_blank">
                    <i class="bx bxl-instagram" aria-hidden="true"></i><span class="visually-hidden">Instagram</span>
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- Footer: End -->
