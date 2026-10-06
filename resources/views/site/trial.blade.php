@extends('site.layouts.main')

@section('title', 'ليفـــان LEVAN - التجربة المجانية')

@section('page-style')
@vite(['resources/assets/css/site/landing.css'])
@endsection

@section('page-script')
<script>
    window.recaptchaSiteKey = @json(config('services.recaptcha.site_key'));
</script>

<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>

@vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/site/trial.js'])
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
        class="relative overflow-hidden bg-primary text-primary-color pt-[120px] md:pt-[130px] lg:pt-[160px]">
        <div class="container">
            <div class="-mx-5 flex flex-wrap items-center">
                <div class="w-full px-5">
                    <div class="scroll-revealed mx-auto max-w-[780px] text-center">
                        <h1
                            class="mb-12 text-3xl font-bold leading-snug text-primary-color sm:text-4xl sm:leading-snug lg:text-5xl lg:leading-tight">
                            جرّب نظام مسند مجاناً واكتشف الفرق بنفسك
                        </h1>

                        <p class="mx-auto mb-9 max-w-[600px] text-base text-primary-color sm:text-lg sm:leading-normal">
                            استمتع بجميع مميزات ليفـــان كاملةً بدون أي التزامات مالية أو بطاقة ائتمانية.
                            ابدأ تجربتك المجانية اليوم وانضم إلى آلاف الشركات التي تثق في مسند لإدارة أعمالها بكفاءة
                            واحترافية.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- Contact section -->
    <section id="" class="section-area">
        <div class="container">
            <div class="row">

                <div class="col-12 xl:col-8">
                    <div
                        class="scroll-revealed bg-body-light-1 dark:bg-primary-dark-2 rounded-xl py-8 sm:py-12 px-6 sm:px-10 z-0 relative shadow-card-1 hover:shadow-lg">
                        <div class="text-center max-w-[550px] mx-auto mb-12">
                            <h6 class="mb-2 block text-3xl font-semibold text-primary">
                                تجربة مجانية كاملة
                            </h6>
                            <p class="mb-6 font-medium text-sm">
                                جاهز للبدء؟
                            </p>
                            <p class="text-base font-normal">
                                قم بالتسجيل الآن للحصول على تجربة مجانية كاملة لنظام ليفـــان واكتشف كيف
                                يمكن أن يساعدك في تحسين عمليات التحصيل وإدارة المستندات بكفاءة عالية.

                            </p>
                        </div>
                        <form id="form" method="POST" action="{{ route('site.trial.store') }}" autocomplete="off"
                            data-form-validate data-validation-url="{{ route('site.form-validation') }}" novalidate>
                            @csrf

                            <div class="row">
                                <div class="col-12" data-form-field data-field-name="customer_name">
                                    <input type="text" name="customer_name" data-form-control
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="الاسم" required />
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1" data-form-error hidden></p>
                                </div>

                                <div class="col-12 md:col-6" data-form-field data-field-name="customer_email">
                                    <input type="email" name="customer_email" data-form-control
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="البريد الإلكتروني" required />
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1" data-form-error hidden></p>
                                </div>

                                <div class="col-12 md:col-6" data-form-field data-field-name="customer_phone">
                                    <input type="tel" name="customer_phone" data-form-control dir="rtl" data-phone-format="international"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        placeholder="رقم الهاتف" required />
                                    <p class="invalid-feedback hidden text-red-500 text-xs mt-1" data-form-error hidden></p>
                                </div>

                                <!-- recaptcha -->
                                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" data-validation-skip>

                                @error('recaptcha')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror

                                <div class="col-12">
                                    <div class="w-full text-center">
                                        <button type="submit" id="submit-form"
                                            class="inline-flex items-center gap-3 px-8 py-3 rounded-md text-base font-medium bg-primary text-primary-color hover:bg-primary-light-10 dark:hover:bg-primary-dark-10 disabled:opacity-60 disabled:cursor-not-allowed transition-all duration-200">
                                            <span class="spinner-border spinner-border-sm hidden" role="status"
                                                aria-hidden="true"></span>
                                            <span class="btn-text">طلب تجربتك المجانية</span>
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


    {{--
    <!-- About section -->
    <section id="" class="section-area">
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
    </section> --}}
</main>


@endsection
