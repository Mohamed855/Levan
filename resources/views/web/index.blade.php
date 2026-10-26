@extends('layouts.web')

@section('title', 'لوت LOT - المنصة المتقدمة لإدارة المزادات والأصول')

@section('page-style')
    @vite(['resources/assets/scss/shared/package-card.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
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
                            <a href="{{ $plans->count() === 1 ? route('site.plans.checkout', $plans->first()->id) : '#pricing' }}"
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
                <x-web.section-header title="أبرز مميزات منصة لوت" subtitle="خدمات متكاملة"
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
                <x-web.section-header title="باقات مرنة تناسب طموحك" subtitle="الباقات والأسعار"
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

        <section id="faq" class="section-area section-area--alt">
            <div class="container">
                <x-web.section-header title="الأسئلة الشائعة" subtitle="إجابات واضحة"
                    desc="إليك أهم الأسئلة التي يطرحها عملاؤنا، لمساعدتك في التعرف على إمكانات النظام بكل وضوح." />

                <div class="faq-grid">
                    @foreach ($faqs as $faq)
                        <div class="faq-item scroll-revealed">
                            <div class="faq-icon-wrap"><i class="ti ti-help-circle"></i></div>
                            <div class="faq-body">
                                <h3 class="faq-question">{{ $faq->question }}</h3>
                                <p class="faq-answer">{{ $faq->answer }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- 9. Contact Section -->
        <section id="contact" class="section-area">
            <div class="container">
                <x-web.section-header title="ابدأ محادثتك معنا اليوم" subtitle="تواصل معنا"
                    desc="فريقنا المتخصص على أتم الاستعداد للإجابة على استفساراتك واستعراض مزايا المنصة المناسبة لعملك." />

                <div class="contact-layout">
                    <!-- Form Container -->
                    <x-web.contact-form data-form-validate :action="route('site.contact.store')" :validation-url="route('site.form-validation')" fields="contact"
                        title="أرسل لنا استفسارك" subtitle="يسعدنا تواصلك"
                        desc="املأ البيانات وسيقوم ممثلنا بالتواصل معك في أقرب فرصة." />

                    <!-- Info Cards -->
                    <x-web.contact-info />
                </div>
            </div>
        </section>

        @php($googleMapsEmbedUrl = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('google_maps_embed_url'))
        @if ($googleMapsEmbedUrl)
            <section id="map" class="map-section">
                <iframe src="{{ $googleMapsEmbedUrl }}" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade" title="موقع لوت LOT">
                </iframe>
            </section>
        @endif
    </main>

@endsection
