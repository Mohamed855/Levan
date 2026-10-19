@extends('site.layouts.main')

@section('title', 'لوت LOT - المنصة المتقدمة لإدارة المزادات والأصول')

@section('page-style')
    @vite(['resources/scss/shared/package-card.scss'])
@endsection

@section('page-script')
    {{-- Temporarily disabled reCAPTCHA.
    <script>
        window.recaptchaSiteKey = @json(config('services.recaptcha.site_key'));
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    --}}
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @foreach ($errors->all() as $error)
                    toastr.error(@json($error));
                @endforeach
            });
        </script>
    @endif

    <main class="site-main">
        <!-- 1. Hero Section -->
        <section id="home" class="hero-section">
            <div class="container">
                <div class="hero-grid">
                    <!-- Content Column -->
                    <div class="hero-content scroll-revealed">
                        <span class="hero-eyebrow">
                            <i class="ti ti-sparkles"></i>
                            المنصة الرقمية الرائدة
                        </span>

                        <h1 class="hero-title">
                            منصة لوت لإدارة <span class="hero-title-highlight">المزادات والأصول</span>
                        </h1>

                        <p class="hero-desc">
                            حل رقمي متكامل مصمم خصيصاً لتمكين الشركات والجهات من ضبط وإدارة المزادات والتحصيلات بثقة وكفاءة
                            استثنائية.
                        </p>

                        <div class="hero-actions">
                            <a href="{{ $plans->isNotEmpty() ? route('site.plans.checkout', $plans->first()->id) : '#pricing' }}"
                                class="btn-hero-primary" role="button">
                                <span>طلب اشتراك</span>
                                <i class="ti ti-arrow-left"></i>
                            </a>

                            <a href="{{ route('site.trial') }}" class="btn-hero-outline" role="button">
                                <span>تجربة مجانية</span>
                                <i class="ti ti-device-laptop"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Media Column -->
                    <div class="hero-media scroll-revealed">
                        <figure>
                            <img src="{{ asset('static/site/images/hero.png') }}" alt="لوت LOT منصة المزادات والأصول">
                        </figure>

                        <img src="{{ asset('static/site/images/dots.svg') }}" alt=""
                            class="hero-dots hero-dots--bottom-left">
                        <img src="{{ asset('static/site/images/dots.svg') }}" alt=""
                            class="hero-dots hero-dots--top-right">
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Services Section -->
        <section id="services" class="section-area">
            <div class="container">
                <x-site.section-header title="أبرز مميزات منصة لوت" subtitle="خدمات متكاملة"
                    desc="نقدّم حزمة متطورة من الأدوات الذكية المصممة لرفع كفاءة أعمالك وضبط العمليات بدقة متناهية." />

                <div class="services-grid">
                    <!-- Feature 1 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-layout-dashboard"></i>
                        </div>
                        <h3 class="service-title">لوحة القيادة الذكية</h3>
                        <p class="service-desc">
                            نظرة شاملة ولحظية على مؤشرات الأداء الحيوية، وحالة المزادات، وسير عمليات التحصيل والمهام
                            الجارية.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-arrows-shuffle"></i>
                        </div>
                        <h3 class="service-title">توزيع ذكي وعادل</h3>
                        <p class="service-desc">
                            آليات مؤتمتة لتوزيع المحافظ والمهام بين فرق العمل حسب المبالغ أو الكثافة لضمان أعلى مستويات
                            الإنتاجية.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-chart-bar"></i>
                        </div>
                        <h3 class="service-title">لوحة التميز التنافسية</h3>
                        <p class="service-desc">
                            شاشات تقدم لحظية تعزز الشفافية والتحفيز التنافسي الإيجابي بين فرق العمل التنفيذية.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-stack-front"></i>
                        </div>
                        <h3 class="service-title">إدارة شاملة للعمليات</h3>
                        <p class="service-desc">
                            إدارة مرنة للمحافظ والعملاء والأصول وصلاحيات المستخدمين بأعلى معايير الحوكمة والأمان.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-headset"></i>
                        </div>
                        <h3 class="service-title">دعم فني استشاري 24/7</h3>
                        <p class="service-desc">
                            فريق تقني واستشاري متخصص على مدار الساعة لمرافقتك في تخصيص وتشغيل المنصة بكل سلاسة.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-trending-up"></i>
                        </div>
                        <h3 class="service-title">تقارير تحليلية وأرشفة</h3>
                        <p class="service-desc">
                            تقارير مالية وتشغيلية تفصيلية جاهزة للتصدير مع نظام أرشفة موثق يحفظ كامل المستندات والوثائق.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Pricing Section -->
        <section id="pricing" class="section-area section-area--alt">
            <div class="container">
                <x-site.section-header title="باقات مرنة تناسب طموحك" subtitle="الباقات والأسعار"
                    desc="نوفر باقات اشتراك مدروسة وشفافة تمنحك كافة الإمكانات لتوسيع أعمالك وإدارة مزاداتك بأمان." />

                <div class="pricing-grid">
                    @foreach ($plans as $plan)
                        <div class="scroll-revealed">
                            <x-ui.package-card :plan="$plan" :action-url="route('site.plans.checkout', $plan->id)" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- 4. Call to Action Banner -->
        <section id="call-action" class="cta-section">
            <div class="container">
                <div class="cta-content scroll-revealed">
                    <h2 class="cta-title">
                        تحكّم في مزاداتك وتوقف عن إدارة الأصول يدوياً
                    </h2>
                    <p class="cta-desc">
                        مع لوت، كل إجراء منظم في مساره الصحيح، وكل مستند تحت عينك، مع متابعة دقيقة لمراحل العمل من البداية
                        وحتى الإسناد.
                        مزادك يبدأ مع لوت.
                    </p>
                    <a href="{{ route('site.trial') }}" class="btn-cta" role="button">
                        <span>ابدأ تجربتك المجانية الآن</span>
                        <i class="ti ti-arrow-left"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- <!-- 5. Portfolios Section -->
    <section id="portfolios" class="section-area">
        <div class="container">
            <x-site.section-header
                title="المحافظ الشريكة"
                subtitle="محافظ معتمدة"
                desc="نفخر بثقة المحافظ والجهات الاستثمارية التي تعتمد على تقنياتنا لتشغيل وإدارة أصولها."
            />

            <div class="logos-grid">
                @forelse ($portfolios as $portfolio)
                    <div class="logo-item scroll-revealed">
                        <img src="{{ asset('storage/' . $portfolio->logo) }}" alt="{{ $portfolio->name }}" loading="lazy">
                    </div>
                @empty
                    <div class="logos-empty">لا يوجد محافظ معروضة حالياً</div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 6. Clients Section -->
    <section id="clients" class="section-area section-area--alt">
        <div class="container">
            <x-site.section-header
                title="شركاء النجاح"
                subtitle="عملاؤنا"
                desc="شراكات نعتز بها ومسيرة مستمرة من النجاح مع كبرى الجهات والشركات."
            />

            <div class="logos-grid">
                @forelse ($ourClients as $client)
                    <div class="logo-item scroll-revealed">
                        <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}" loading="lazy">
                    </div>
                @empty
                    <div class="logos-empty">لا يوجد عملاء معروضون حالياً</div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 7. Testimonials Section -->
    <section id="testimonials" class="testimonials-section section-area">
        <div class="container">
            <x-site.section-header
                title="ماذا يقول عملاؤنا"
                subtitle="آراء وتجارب"
                desc="تجارب حقيقية تعكس الجودة والاحترافية التي نحرص على تقديمها لعملائنا في كل خطوة."
            />

            <div class="swiper testimonial-carousel scroll-revealed">
                <div class="swiper-wrapper">
                    <!-- Slide 1 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "قبل منصة لوت كنا نتابع المزادات والإجراءات يدوياً، والآن أصبح كل شيء واضحاً بنقرة واحدة وفي مكان موحد. وفّرت علينا مئات ساعات العمل."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">خالد العتيبي</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "التقارير التفصيلية ولوحة المؤشرات ساعدتنا في اتخاذ قرارات دقيقة وسريعة. تحسّن مستوى إدارة الأصول والمتابعة بشكل ملموس جداً."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">نورة الشمري</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 3 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "الواجهة عصرية وسهلة الاستخدام، وفريق العمل تمكّن من التعامل مع النظام دون الحاجة لتدريب معقد. تجربة استثنائية بكل المقاييس."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">فهد القحطاني</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "منذ بدأنا العمل مع لوت أصبحت كامل الوثائق والمستندات مؤرشفة ومنظمة بدقة، وفريق الدعم الفني متجاوب وسريع في تلبية كل المتطلبات."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">سارة الدوسري</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 5 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "ربط المنصة وإطلاق حسابنا تم بسلاسة وسرعة فائقة. التقارير المالية والتشغيلية في لوت ذات مستوى احترافي عالي جداً."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">عبدالله الزهراني</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 6 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="testimonial-text">
                                "إدارة المزادات والمحافظ المتعددة من لوحة واحدة غيّرت طريقة تنظيمنا بالكامل. نوصي بها لأي جهة تبحث عن الكفاءة والاحترافية."
                            </p>
                            <div class="testimonial-author">
                                <h4 class="author-name">منى الحربي</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="swiper-controls">
                    <button type="button" class="swiper-btn swiper-button-prev" aria-label="السابق">
                        <i class="ti ti-arrow-right"></i>
                    </button>
                    <button type="button" class="swiper-btn swiper-button-next" aria-label="التالي">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                </div>
            </div>
        </div>
    </section> --}}

        <!-- 8. FAQ Section -->
        <section id="faq" class="section-area section-area--alt">
            <div class="container">
                <x-site.section-header title="الأسئلة الشائعة" subtitle="إجابات واضحة"
                    desc="إليك أهم الأسئلة التي يطرحها عملاؤنا، لمساعدتك في التعرف على إمكانات النظام بكل وضوح." />

                <div class="faq-grid">
                    @foreach ($faqs as $faq)
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap"><i class="ti ti-help-circle"></i></div>
                        <div class="faq-body"><h3 class="faq-question">{{ $faq->question }}</h3><p class="faq-answer">{{ $faq->answer }}</p></div>
                    </div>
                    @endforeach
                    <div class="d-none" aria-hidden="true">
                    <!-- Q1 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل تتقاضون رسوماً مقابل التحديثات والدعم الفني؟</h3>
                            <p class="faq-answer">
                                لا، جميع التحديثات الدورية والتحسينات المستمرة وخدمات الدعم الفني مشمولة بالكامل ضمن
                                الاشتراك دون أي تكاليف خفية.
                            </p>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل أحتاج إلى شراء ترخيص لكل مستخدم؟</h3>
                            <p class="faq-answer">
                                يعتمد عدد تراخيص المستخدمين على الباقة المختارة، مع إمكانية الترقية وإضافة مستخدمين جدد بكل
                                سهولة في أي وقت.
                            </p>
                        </div>
                    </div>

                    <!-- Q3 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل يدعم النظام الربط والتكامل مع الأنظمة المحاسبية؟</h3>
                            <p class="faq-answer">
                                نعم، تدعم منصة لوت الربط والتكامل مع الأنظمة المحاسبية وأنظمة ERP الرائدة لتسهيل تبادل
                                البيانات بسلاسة وأمان.
                            </p>
                        </div>
                    </div>

                    <!-- Q4 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">هل يمكن تخصيص النظام ليتناسب مع سياسات شركتنا؟</h3>
                            <p class="faq-answer">
                                بالتأكيد، يتيح النظام تخصيص الصلاحيات، وهيكل فرق العمل، ونماذج التقارير، وطرق توزيع العمل
                                بما يتوافق مع لوائح وسياسات كل جهة.
                            </p>
                        </div>
                    </div>

                    <!-- Q5 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">ما مدى أمان وحماية البيانات في منصة لوت؟</h3>
                            <p class="faq-answer">
                                نطبق أعلى بروتوكولات التشفير والنسخ الاحتياطي السحابي المستمر وعزل بيانات كل منشأة لضمان
                                الامتثال التام لمعايير الأمن السيبراني.
                            </p>
                        </div>
                    </div>

                    <!-- Q6 -->
                    <div class="faq-item scroll-revealed">
                        <div class="faq-icon-wrap">
                            <i class="ti ti-help"></i>
                        </div>
                        <div class="faq-body">
                            <h3 class="faq-question">كيف يتم نقل البيانات من أنظمتنا القديمة؟</h3>
                            <p class="faq-answer">
                                يوفر فريقنا التقني خدمة نقل وترحيل البيانات مجاناً عند الاشتراك، مع التحقق الدقيق لضمان
                                سلامة واكتمال السجلات.
                            </p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Contact Section -->
        <section id="contact" class="section-area">
            <div class="container">
                <x-site.section-header title="ابدأ محادثتك معنا اليوم" subtitle="تواصل معنا"
                    desc="فريقنا المتخصص على أتم الاستعداد للإجابة على استفساراتك واستعراض مزايا المنصة المناسبة لعملك." />

                <div class="contact-layout">
                    <!-- Form Container -->
                    <x-site.contact-form data-form-validate :action="route('site.contact.store')" :validation-url="route('site.form-validation')" fields="contact"
                        title="أرسل لنا استفسارك" subtitle="يسعدنا تواصلك"
                        desc="املأ البيانات وسيقوم ممثلنا بالتواصل معك في أقرب فرصة." />

                    <!-- Info Cards -->
                    <x-site.contact-info />
                </div>
            </div>
        </section>

        <!-- 10. Map Section -->
        @php($googleMapsEmbedUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('google_maps_embed_url'))
        @if ($googleMapsEmbedUrl)
        <section id="map" class="map-section">
            <iframe
                src="{{ $googleMapsEmbedUrl }}"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="موقع لوت LOT">
            </iframe>
        </section>
        @endif
    </main>

@endsection
