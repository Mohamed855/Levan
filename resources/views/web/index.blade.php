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
                            حل رقمي متكامل مصمم خصيصاً لتمكين الشركات والمكاتب والجهات القضائية من ضبط وإدارة المزادات
                            والإسنادات والأصول العقارية بثقة وكفاءة
                            استثنائية.
                        </p>

                        <div class="hero-actions">
                            <a href="{{ $plans->count() === 1 ? route('site.plans.checkout', $plans->first()->id) : '#pricing' }}"
                                class="btn-hero-primary" role="button">
                                <span>تسجيل اشتراك</span>
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
                    desc="نقدّم حزمة متطورة من الأدوات الذكية المصممة لإدارة المزادات والإسنادات والأصول العقارية بدقة متناهية." />

                <div class="services-grid">
                    <!-- Feature 1: Auctions Management -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-gavel"></i>
                        </div>
                        <h3 class="service-title">تنظيم وإدارة المزادات</h3>
                        <p class="service-desc">
                            إعداد وإدارة المزادات العلنية والإلكترونية باحترافية، وإصدار كراسات الشروط، وتنظيم الجلسات،
                            وتحديثات الترسية بدقة وشفافية.
                        </p>
                    </div>

                    <!-- Feature 2: Judicial Assignments -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-scale"></i>
                        </div>
                        <h3 class="service-title">إدارة الإسنادات والتصفية</h3>
                        <p class="service-desc">
                            توثيق ومتابعة أوامر الإسناد من محاكم التنفيذ والجهات القضائية، وربط العقارات بقرارات الإسناد،
                            ومتابعة مراحل التصفية حتى الإنجاز.
                        </p>
                    </div>

                    <!-- Feature 3: Real Estate & Asset Registry -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-building"></i>
                        </div>
                        <h3 class="service-title">سجل رقمي شامل للأصول</h3>
                        <p class="service-desc">
                            حصر وإدارة العقارات والأصول بتفاصيل الصكوك، والحدود والأطوال، والوحدات، وبيانات الملاك، وتحديد
                            الجاهزية للطرح في المزاد.
                        </p>
                    </div>

                    <!-- Feature 4: Smart Dashboard & Live Analytics -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-layout-dashboard"></i>
                        </div>
                        <h3 class="service-title">لوحة قيادة وتحليلات ذكية</h3>
                        <p class="service-desc">
                            رؤية شاملة ولحظية لمؤشرات الأداء الحيوية، وحالة المزادات، ومراحل الإسنادات، وإحصائيات المبيعات
                            لدعم اتخاذ القرار.
                        </p>
                    </div>

                    <!-- Feature 5: Digital Archiving & Governance -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-archive"></i>
                        </div>
                        <h3 class="service-title">أرشفة رقمية وحوكمة موثقة</h3>
                        <p class="service-desc">
                            حفظ وأرشفة لكافة المستندات ومحاضر المزادات والقرارات، مع سجل تدقيق تفصيلي لجميع الأنشطة
                            والعمليات لضمان أعلى معايير الأمان.
                        </p>
                    </div>

                    <!-- Feature 6: Custom Domains & Cloud Multi-tenancy -->
                    <div class="service-card scroll-revealed">
                        <div class="service-icon-wrap">
                            <i class="ti ti-world"></i>
                        </div>
                        <h3 class="service-title">بيئة سحابية ونطاق مخصص</h3>
                        <p class="service-desc">
                            عزل سحابي تام ومستقل لبيانات كل منشأة مع إمكانية ربط نطاق مخصص (Custom Domain)، وإدارة مرنة
                            لصلاحيات وأدوار فرق العمل.
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
