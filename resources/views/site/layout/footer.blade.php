<!-- Footer -->
<footer class="bg-primary text-primary-color">
    <div class="container py-16 lg:py-20">
        <div class="row">
            <!-- معلومات الشركة -->
            <div class="col-12 lg:col-4 mb-10 lg:mb-0">
                <div class="">
                    <a href="." class="inline-block mb-6">
                        <img src="{{ asset('static/brand/logos/Levan-logo-light.svg') }}" class="h-14 lg:h-16" alt="ليفـــان">
                    </a>

                    <p class="mb-6 text-primary-color/80 leading-relaxed max-w-[320px]">
                        المنصة المتقدمة لإدارة عمليات التحصيل
                    </p>

                    <!-- روابط التواصل الاجتماعي -->
                    <div class="flex items-center gap-3">
                        <a href="javascript:void(0)"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-primary-color hover:bg-[#e3d8d1] hover:text-primary transition-all duration-300">
                            <i class="ti ti-brand-facebook text-lg"></i>
                        </a>

                        <a href="javascript:void(0)"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-primary-color hover:bg-[#e3d8d1] hover:text-primary transition-all duration-300">
                            <i class="ti ti-brand-x text-lg"></i>
                        </a>


                    </div>
                </div>
            </div>

            <!-- روابط سريعة -->
            <div class="col-6 sm:col-4 lg:col-2 mb-10 lg:mb-0">
                <div class="">
                    <h4 class="mb-6 text-lg font-bold text-white">روابط سريعة</h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="#home"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li>
                            <a href="#services"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                المميزات
                            </a>
                        </li>
                        <li>
                            <a href="#pricing"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                الباقات و الاسعار
                            </a>
                        </li>
                        <li>
                            <a href="#portfolios"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                المحافظ
                            </a>
                        </li>
                        <li>
                            <a href="#clients"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                عملائنا
                            </a>
                        </li>
                        <li>
                            <a href="#faq"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                الأسئلة الشائعة
                            </a>
                        </li>

                        <li>
                            <a href="#contact"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                تواصل معنا
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- السياسات -->
            <div class="col-6 sm:col-4 lg:col-2 mb-10 lg:mb-0">
                <div class="">
                    <h4 class="mb-6 text-lg font-bold text-white">السياسات</h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ route('site.privacy.policy') }}"
                                class="inline-flex items-center text-primary-color/80 hover:text-[#e3d8d1] transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                سياسة الاستخدام
                            </a>
                        </li>

                        {{-- <li>
                            <a href="javascript:void(0)"
                                class="inline-flex items-center text-primary-color/80 hover:text-secondary transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                سياسة الخصوصية
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)"
                                class="inline-flex items-center text-primary-color/80 hover:text-secondary transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                الشروط والأحكام
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)"
                                class="inline-flex items-center text-primary-color/80 hover:text-secondary transition-colors duration-200">
                                <i class="lni lni-chevron-left text-xs ml-2"></i>
                                اتفاقية الخدمة
                            </a>
                        </li> --}}
                    </ul>
                </div>
            </div>

            <!-- معلومات التواصل -->
            <div class="col-12 sm:col-8 lg:col-4">
                <div class="">
                    <h4 class="mb-6 text-lg font-bold text-white">معلومات التواصل</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-[#e3d8d1] flex items-center justify-center mt-0.5">
                                <i class="footer-contact-icon ti ti-map-pin text-primary"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-primary-color leading-relaxed">
                                    الرياض، طريق الأمير محمد بن عبدالعزيز<br>
                                    حي السليمانية، 12242
                                </p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-[#e3d8d1] flex items-center justify-center">
                                <i class="footer-contact-icon ti ti-phone text-primary"></i>
                            </div>
                            <div class="flex-grow">
                                <a href="tel:0500181100"
                                    class="mt-2 text-primary-color hover:text-[#e3d8d1] transition-colors block">
                                    0500181100
                                </a>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full bg-[#e3d8d1] flex items-center justify-center">
                                <i class="footer-contact-icon ti ti-mail text-primary"></i>
                            </div>
                            <div class="flex-grow">
                                <a href="mailto:info@e-tec.sa"
                                    class="mt-2 text-primary-color hover:text-[#e3d8d1] transition-colors block">
                                    info@e-tec.sa
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- خط فاصل -->
    <div class="w-full border-t border-solid border-white/10"></div>

    <!-- حقوق النشر -->
    <div class="container py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="w-full md:w-auto text-center md:text-right">
                <p class="text-primary-color/80 text-sm">
                    جميع الحقوق محفوظة © 2025
                    <a href="."
                        class="text-primary-color hover:text-[#e3d8d1] transition-colors font-semibold">ليفـــان</a>
                </p>
            </div>
            <div class="w-full md:w-auto text-center md:text-left">
                <p class="text-primary-color/80 text-sm">
                    تطوير وتصميم
                    <a href="https://e-tec.sa" target="_blank"
                        class="text-primary-color hover:text-[#e3d8d1] transition-colors font-semibold inline-flex items-center gap-1">
                        إتمام لتقنية نظم المعلومات
                        <i class="lni lni-heart-filled text-red-500 text-xs"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>
</footer>
