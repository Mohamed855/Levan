@extends('site.layouts.main')

@section('title', 'ليفـــان LEVAN')

@section('page-style')
@vite(['resources/assets/css/site/landing.css', 'resources/assets/css/components/icons.css', 'resources/scss/shared/package-card.scss'])
@endsection

@section('page-script')
<script>
    window.recaptchaSiteKey = @json(config('services.recaptcha.site_key'));
</script>

<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>


@vite(['resources/assets/js/site/landing.js'])
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


<main class="main relative">
    <!-- Hero section -->
    <section id="home"
        class="relative overflow-hidden text-primary pt-[120px] pb-16 md:pt-[130px] lg:pt-[160px] lg:pb-24">
        <div class="container">
            <div class="-mx-5 flex flex-wrap items-center lg:flex-nowrap">
                <div class="w-full px-5 lg:order-2 lg:w-1/2">
                    <div class="scroll-revealed mx-auto max-w-[780px] text-right">
                        <h1
                            class="mb-6 text-3xl font-bold leading-snug text-primary sm:text-4xl sm:leading-snug lg:text-5xl lg:leading-tight">
                            منصة ليفان للمزادات العقارية

                        </h1>

                        <p class="mx-0 mb-9 max-w-[600px] text-base text-primary sm:text-lg sm:leading-normal">
                            حل متكامل لإدارة المزادات بثقة وكفاءة عالية

                        </p>

                        <ul class="flex flex-wrap items-center justify-center gap-4 md:justify-start md:gap-5">
                            <li>
                                <a href="{{ $plans->isNotEmpty() ? route('site.plans.checkout', $plans->first()->id) : '#pricing' }}"
                                    class="flex items-center gap-2 rounded-full bg-primary px-5 py-3 text-base font-medium text-primary-color transition-colors hover:bg-primary-light-10 hover:text-[#e3d8d1] md:px-7 md:py-[14px]"
                                    role="button">طلب اشتراك <i class="ti ti-arrow-left text-lg/none"></i></a>
                            </li>
                        </ul>

                    </div>
                </div>
                <div class="w-full px-5 lg:order-1 lg:w-1/2">
                    <div class="scroll-revealed relative z-10 mx-auto max-w-[845px]">
                        <figure class="mt-12 lg:mt-0">
                            <img src="{{ asset('static/site/images/hero.png') }}" alt="Hero image"
                                class="mx-auto max-w-full rounded-t-xl rounded-tr-xl" />
                        </figure>

                        <div class="absolute -left-9 bottom-0 z-[-1]">
                            <img src="{{ asset('static/site/images/dots.svg') }}" alt class="w-[120px] opacity-75" />
                        </div>

                        <div class="absolute -right-6 -top-6 z-[-1]">
                            <img src="{{ asset('static/site/images/dots.svg') }}" alt class="w-[120px] opacity-75" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- About section intentionally disabled for future reuse.
    <section id="about" class="section-area">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <div class="w-full">
                    <figure class="scroll-revealed max-w-[480px] mx-auto">
                        <img src="{{ asset('static/site/images/about-img.png') }}" alt="About image"
                            class="rounded-xl" />
                    </figure>
                </div>

                <div class="w-full m-auto">
                    <div class="scroll-revealed">
                        <h6 class="mb-2 block text-7xl font-semibold text-primary">
                            ليفـــان
                        </h6>
                        <p class="font-bold text-3xl mb-6">
                            المنصة ذكية لإدارة عمليات التحصيل
                        </p>
                    </div>

                    <div class="font-semibold mt-4">
                        <p>
                            منصة ذكية لإدارة عمليات التحصيل هدفها تمكين الجهات من ضبط العمليات و رفع أداء افرق التحصيل
                            عبر لوحات متابعة فورية و تقارير تحليلية و أرشفة للمستندات

                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    --}}



    <!-- Services section -->
    <section id="services" class="section-area">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    المميزات
                </h6>
                <p class="mb-6 font-medium text-sm">أفضل خدماتنا</p>
                <p class="text-base font-normal">
                    نقدّم مجموعة متكاملة من الخدمات المصممة بعناية لتلبية احتياجات عملك، مع التركيز على الجودة، السهولة،
                    والاعتمادية.
                </p>
            </div>

            <div class="row text-center">
                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti  ti-xl ti-xl ti-layout-dashboard"></i>

                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                لوحة القيادة الذكية
                            </h4>
                            <p>
                                نظرة شاملة لحظية على مؤشرات الأداء ونشاطات التحصيل والمهام الجارية
                            </p>
                        </div>
                    </div>
                </div>

                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti  ti-xl ti-xl ti-arrows-shuffle"></i>

                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                توزيع ذكي وعادل
                            </h4>
                            <p>
                                نظام توزيع المحافظ بين الموظفين حسب المبالغ أو عدد العملاء أو مختلط لضمان العدالة
                            </p>
                        </div>
                    </div>
                </div>

                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti  ti-xl ti-chart-bar"></i>
                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                لوحة التميز التنافسية
                            </h4>
                            <p>
                                لوحة أداء تفاعلية لعرض تقدم الموظفين لحظياً وتحفيز التنافس البناء
                            </p>
                        </div>
                    </div>
                </div>

                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti ti-xl ti-stack-front"></i>
                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                إدارة شاملة للعمليات
                            </h4>
                            <p>
                                إدارة الشركات والمحافظ والعملاء والمستخدمين مع تخصيص الصلاحيات
                            </p>
                        </div>
                    </div>
                </div>

                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti  ti-xl ti-headset"></i>

                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                دعم فني متاح 24/7
                            </h4>
                            <p>
                                دعم تقني متخصص على مدار الساعة مع إمكانية التخصيص حسب احتياجات الجهة
                            </p>
                        </div>
                    </div>
                </div>

                <div class="scroll-revealed col-12 sm:col-6 lg:col-4">
                    <div class="group hover:-translate-y-1">
                        <div
                            class="w-[70px] h-[70px] rounded-2xl mx-auto mb-6 flex items-center justify-center text-[37px]/none bg-primary text-primary-color">
                            <i class="ti  ti-xl ti-trending-up"></i>
                        </div>
                        <div class="w-full">
                            <h4 class="text-[1.25rem]/tight font-semibold mb-5">
                                تقارير تحليلية دقيقة
                            </h4>
                            <p>
                                لوحات تحكم تحليلية وتقارير مالية دقيقة مع أرشيف منظم للوثائق والمستندات
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Pricing section -->
    <section id="pricing" class="section-area">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    الاسعار
                </h6>
                <p class="mb-6 font-medium text-sm">الباقات و الاسعار</p>
                <p class="text-base font-normal">
                    نوفر باقات اشتراك متنوعة بأسعار تنافسية، تتيح لك اختيار الخطة الأنسب لطبيعة عملك
                </p>
            </div>

            <div class="row">

                @foreach ($plans as $plan)
                <div class="scroll-revealed col-12 sm:col-6 lg:col-4 flex">
                    <x-ui.package-card :plan="$plan" :action-url="route('site.plans.checkout', $plan->id)" />
                </div>
                @endforeach

            </div>
        </div>
    </section>

    <!-- Call action section -->
    <section id="call-action" class="section-area !bg-primary !text-primary-color">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[650px] mx-auto">
                <h2 class="mb-8 text-inherit text-3xl md:text-4xl font-bold">
                    تحكّم في تحصيلاتك وتوقف عن ملاحقة المستحقات يدوياً
                </h2>
                <p class="text-lg md:text-xl leading-relaxed">
                    مع ليفـــان، كل فاتورة في مكانها، وكل موعد استحقاق تحت عينك، وكل عميل متأخر يصلك تنبيهه تلقائياً.
                    ركّز على تنمية أعمالك ودع المنصة تتولى متابعة التحصيلات عنك.
                </p>
                <a href="{{ route('site.trial') }}"
                    class="inline-block px-7 py-4 rounded-full mt-11 bg-white text-primary font-medium text-lg hover:bg-opacity-90 hover:text-primary focus:bg-opacity-90 focus:text-primary hover:shadow-xl focus:shadow-xl transition-all duration-300"
                    role="button">
                    ابدأ تجربتك المجانية الآن
                </a>
            </div>
        </div>
    </section>

    <!-- Portfolio section -->
    <section id="portfolios" class="section-area">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    المحافظ
                </h6>
                <p class="mb-6 font-medium text-sm">
                    شركاء النجاح
                </p>
                <p class="text-base font-normal">
                    نفخر بثقة عملائنا وشركائنا، ونسعى دائمًا إلى تقديم حلول احترافية تلبي تطلعاتهم
                    وتدعم نجاحهم في مختلف المجالات.
                </p>
            </div>

            <div class="">
                <div class="row">
                    @forelse ($portfolios as $portfolio)
                    <div class="scroll-revealed text-center p-4 col-12 sm:col-6 md:col-4 lg:col-3">
                        <img src="{{ asset('storage/' . $portfolio->logo) }}" alt="{{ $portfolio->name }}"
                            class="h-[100px] w-auto object-contain inline-block grayscale dark:invert hover:grayscale-0 hover:invert-0" />
                    </div>
                    @empty
                    <span class="text-center">لا يوجد عملاء حاليا</span>
                    @endforelse
                </div>
            </div>
        </div>
    </section>


    <!-- Clients section -->
    <section id="clients" class="section-area">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    عملاؤنا
                </h6>
                <p class="mb-6 font-medium text-sm">
                    شركاء النجاح
                </p>
                <p class="text-base font-normal">
                    نفخر بثقة عملائنا وشركائنا، ونسعى دائمًا إلى تقديم حلول احترافية تلبي تطلعاتهم
                    وتدعم نجاحهم في مختلف المجالات.
                </p>
            </div>

            <div class="">
                <div class="row">
                    @forelse ($ourClients as $client)
                    <div class="scroll-revealed text-center p-4 col-12 sm:col-6 md:col-4 lg:col-3">
                        <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}"
                            class="h-[100px] inline-block grayscale dark:invert hover:grayscale-0 hover:invert-0" />
                    </div>
                    @empty
                    <span class="text-center">لا يوجد عملاء حاليا</span>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials section -->
    <section id="testimonials" class="section-area z-0">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    آراء العملاء
                </h6>
                <p class="mb-6 font-medium text-sm">
                    ماذا يقول عملاؤنا
                </p>
                <p class="text-base font-normal">
                    نعتز بآراء عملائنا وتجاربهم معنا، فهي تعكس مستوى الجودة والاحترافية التي نحرص
                    على تقديمها في جميع خدماتنا.
                </p>
            </div>

            <div class="swiper testimonial-carousel common-carousel scroll-revealed">
                <div class="swiper-wrapper">

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "قبل المنصة كنا نتابع التحصيلات يدوياً عبر الإكسل، والآن أصبح كل شيء واضح أمامي بنقرة
                                واحدة. وفّرت علينا ساعات من العمل الأسبوعي."
                            </p>
                            <figure class="flex items-center gap-4">
                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        خالد العتيبي
                                    </h3>

                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "التقارير التفصيلية ساعدتنا نحدد العملاء المتأخرين بسرعة ونتواصل معهم في الوقت المناسب.
                                تحسّن معدل التحصيل لدينا بشكل ملحوظ."
                            </p>
                            <figure class="flex items-center gap-4">
                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        نورة الشمري
                                    </h3>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "الواجهة سهلة جداً، أي موظف في الفريق يقدر يستخدمها بدون تدريب طويل. أكثر شيء أعجبني
                                لوحة التحكم اللي تعطيك صورة كاملة عن الوضع المالي."
                            </p>
                            <figure class="flex items-center gap-4">

                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        فهد القحطاني
                                    </h3>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "منذ اشتركنا في المنصة لم نفقد أي فاتورة ولم نتأخر في متابعة أي مستحق. فريق الدعم متجاوب
                                ويحل أي استفسار بسرعة."
                            </p>
                            <figure class="flex items-center gap-4">
                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        سارة الدوسري
                                    </h3>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "ربطنا المنصة مع نظام الفوترة الخاص بنا وكان الأمر أسهل مما توقعنا. الآن التحصيلات
                                تُسجَّل تلقائياً ولا حاجة لإدخال يدوي."
                            </p>
                            <figure class="flex items-center gap-4">
                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        عبدالله الزهراني
                                    </h3>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="swiper-slide">
                        <div class="rounded-xl bg-body-light-1 dark:bg-body-dark-12/10 px-5 py-8 shadow-card-2 sm:px-8">
                            <p class="mb-6 text-base text-body-light-11 dark:text-body-dark-11">
                                "أصبحت أتابع تحصيلات فروعنا الثلاثة من مكان واحد. التنبيهات التلقائية عند اقتراب مواعيد
                                الاستحقاق غيّرت طريقة عملنا كلياً."
                            </p>
                            <figure class="flex items-center gap-4">
                                <figcaption class="flex-grow">
                                    <h3 class="text-sm font-semibold text-body-light-11 dark:text-body-dark-11">
                                        منى الحربي
                                    </h3>
                                </figcaption>
                            </figure>
                        </div>
                    </div>

                </div>

                <div class="mt-[60px] flex items-center justify-center gap-1">
                    <div class="swiper-button-next">
                        <i class="ti ti-arrow-right"></i>
                    </div>
                    <div class="swiper-button-prev">
                        <i class="ti ti-arrow-left"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ section -->
    <section id="faq" class="section-area relative">
        <div class="container">
            <div class="scroll-revealed text-center max-w-[550px] mx-auto mb-12">
                <h6 class="mb-2 block text-3xl font-semibold text-primary">
                    الأسئلة الشائعة
                </h6>
                <p class="mb-6 font-medium text-sm">
                    كل ما تريد معرفته
                </p>
                <p class="text-base font-normal">
                    تجد هنا أهم الأسئلة التي يطرحها عملاؤنا والإجابات عليها، لمساعدتك على فهم
                    خدماتنا واستخدامها بكل سهولة ويسر.
                </p>
            </div>

            <div class="grid gap-x-8 gap-y-12 grid-cols-1 lg:grid-cols-2">

                <!-- Question 1 -->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            هل تتقاضون رسوماً مقابل التحديثات والدعم الفني؟
                        </h3>
                        <p>
                            لا، جميع التحديثات والتحسينات والدعم الفني مشمولة ضمن الاشتراك السنوي دون أي رسوم إضافية.
                        </p>
                    </div>
                </div>

                <!-- Question 2 -->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            هل أحتاج إلى شراء ترخيص لكل فرع؟
                        </h3>
                        <p>
                            عدد الفروع يعتمد على الباقة المختارة. بعض الباقات تشمل فرعاً واحداً، بينما توفر الباقات
                            الأعلى إمكانية إضافة فروع متعددة.
                        </p>
                    </div>
                </div>

                <!-- Question 3 -->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            هل يدعم النظام الربط مع الأنظمة المالية أو أنظمة ERP؟
                        </h3>
                        <p>
                            نعم، يدعم “ليفـان” التكامل مع الأنظمة المحاسبية وERP لتسهيل عمليات تبادل البيانات بشكل
                            مؤتمت.
                        </p>
                    </div>
                </div>

                <!-- Question 4-->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            هل يمكن تخصيص النظام حسب احتياجات الجهة؟
                        </h3>
                        <p>
                            نعم، يمكن تخصيص العديد من الإعدادات مثل الصلاحيات، طرق توزيع المحافظ، التقارير، وهيكل الجهات
                            والفروع، بما يناسب سياسات كل جهة.
                        </p>
                    </div>
                </div>

                <!-- Question 5 -->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            ما مدى أمان البيانات وكيف يتم حفظها؟
                        </h3>
                        <p>
                            النظام يعتمد بروتوكولات تشفير قوية، ونسخ احتياطي دوري، وصلاحيات وصول دقيقة لضمان حماية
                            بيانات الجهة والعملاء.
                        </p>
                    </div>
                </div>

                <!-- Question 6 -->
                <div class="scroll-revealed flex">
                    <div
                        class="ml-4 flex h-[50px] w-full max-w-[50px] items-center justify-center rounded-xl bg-primary text-primary-color text-[28px] sm:mr-6 sm:h-[60px] sm:max-w-[60px] sm:text-[32px]">
                        <i class="ti ti-xl ti-help-circle"></i>
                    </div>
                    <div class="w-full">
                        <h3
                            class="mb-3 text-xl font-semibold text-body-light-12 dark:text-body-dark-12 sm:text-2xl lg:text-xl xl:text-2xl">
                            كيف يتم نقل بياناتي من النظام القديم إلى ليفـان؟
                        </h3>
                        <p>
                            نوفر خدمة نقل بيانات احترافية عند الاشتراك. يقوم فريقنا التقني بترحيل كافة البيانات من نظامك
                            الحالي إلى منصة ليفـان بسرية تامة وبدون أي تكاليف إضافية.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <div>

        </div>
    </section>

    <!-- Contact section -->
    <section id="contact" class="section-area">
        <div class="container">
            <div class="row">

                <div class="col-12 xl:col-8">
                    <div
                        class="scroll-revealed bg-body-light-1 dark:bg-primary-dark-2 rounded-xl py-8 sm:py-12 px-6 sm:px-10 z-0 relative shadow-card-1 hover:shadow-lg">
                        <div class="text-center max-w-[550px] mx-auto mb-12">
                            <h6 class="mb-2 block text-3xl font-semibold text-primary">
                                تواصل معنا
                            </h6>
                            <p class="mb-6 font-medium text-sm">
                                جاهز للبدء؟
                            </p>
                            <p class="text-base font-normal">
                                نحن هنا لمساعدتك والإجابة على جميع استفساراتك، فلا تتردد في التواصل معنا لبدء رحلتك
                                معنا.
                            </p>
                        </div>
                        <form id="form" method="POST" action="{{ route('site.contact.store') }}" autocomplete="off"
                            novalidate>
                            @csrf

                            <div class="row">
                                <div class="col-12 md:col-6">
                                    <input type="text" name="customer_name" minlength="3"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="الاسم" required />
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1">
                                        الرجاء إدخال الاسم الكامل (على الأقل 3 أحرف)
                                    </p>
                                </div>

                                <div class="col-12 md:col-6">
                                    <input type="email" name="customer_email"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="البريد الإلكتروني" />
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1">
                                        الرجاء إدخال بريد إلكتروني صحيح
                                    </p>
                                </div>

                                <div class="col-12">
                                    <textarea name="message" rows="5"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="رسالتك أو استفسارك" required></textarea>
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1">
                                        الرجاء كتابة رسالتك
                                    </p>
                                </div>

                                <!-- recaptcha -->
                                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                                @error('recaptcha')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror

                                <div class="col-12">
                                    <div class="w-full text-center">
                                        <button type="submit" id="submit-form"
                                            class="inline-flex items-center gap-3 px-8 py-3 rounded-full text-base font-medium bg-primary text-primary-color hover:bg-primary-light-10 hover:text-[#e3d8d1] dark:hover:bg-primary-dark-10 disabled:opacity-60 disabled:cursor-not-allowed transition-all duration-200">
                                            <span class="spinner-border spinner-border-sm hidden" role="status"
                                                aria-hidden="true"></span>
                                            <span class="btn-text">إرسال الرسالة</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="col-12 xl:col-4">
                    <div class="row">
                        <div class="col-12 md:col-6 xl:col-12">
                            <div
                                class="scroll-revealed py-5 px-6 rounded-xl shadow-card-1 bg-body-light-1 dark:bg-primary-dark-2 flex gap-6 hover:shadow-lg">
                                <div
                                    class="w-[50px] h-[50px] flex items-center justify-center rounded-lg bg-primary text-primary-color text-[1.25rem] flex-shrink-0">
                                    <i class="ti ti-phone"></i>
                                </div>
                                <div>
                                    <h4 class="text-[1.25rem] text-primary mb-3">تواصل معنا</h4>
                                    <p class="m-0 font-normal">0500181100</p>
                                    <p class="m-0 font-normal">info@e-tec.sa</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 md:col-6 xl:col-12">
                            <div
                                class="scroll-revealed py-5 px-6 rounded-xl shadow-card-1 bg-body-light-1 dark:bg-primary-dark-2 flex gap-6 hover:shadow-lg">
                                <div
                                    class="w-[50px] h-[50px] flex items-center justify-center rounded-lg bg-primary text-primary-color text-[1.25rem] flex-shrink-0">
                                    <i class="ti ti-map-pin"></i>
                                </div>
                                <div>
                                    <h4 class="text-[1.25rem] text-primary mb-3">العنوان</h4>
                                    <p class="m-0 font-normal">الرياض، طريق الأمير محمد بن عبدالعزيز، حي السليمانية،
                                        12242</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 md:col-6 xl:col-12">
                            <div
                                class="scroll-revealed py-5 px-6 rounded-xl shadow-card-1 bg-body-light-1 dark:bg-primary-dark-2 flex gap-6 hover:shadow-lg">
                                <div
                                    class="w-[50px] h-[50px] flex items-center justify-center rounded-lg bg-primary text-primary-color text-[1.25rem] flex-shrink-0">
                                    <i class="ti ti-alarm"></i>
                                </div>
                                <div>
                                    <h4 class="text-[1.25rem] text-primary mb-3">ساعات العمل</h4>
                                    <p class="m-0 font-normal">متاح 24 ساعة / 7 أيام</p>
                                    <p class="m-0 font-normal">ساعات المكتب: 10:00 ص - 6:00 م</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Map section -->
    <section id="map" class="w-full h-[500px] overflow-hidden -mt-[12rem]">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d453.0895346409257!2d46.697927505802454!3d24.702278165008337!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f1d8fe707d467%3A0x665443538d6963!2z2KfZhNio2LHZh9in2YYg2YTZhNmF2K3Yp9mF2KfYqQ!5e0!3m2!1sen!2ssa!4v1778593837435!5m2!1sen!2ssa"
            class="border-0 w-full h-full" allowfullscreen loading="lazy">
        </iframe>
    </section>
</main>

@endsection
