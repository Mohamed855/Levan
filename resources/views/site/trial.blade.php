@extends('site.layouts.main')

@section('title', 'لوت LOT - التجربة المجانية')

@section('page-style')
@endsection

@section('page-script')
    <script>
        window.recaptchaSiteKey = @json(config('services.recaptcha.site_key'));
    </script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
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
        <!-- Sub Hero -->
        <section class="site-page-hero">
            <div class="container">
                <div class="scroll-revealed">
                    <span class="hero-eyebrow">
                        <i class="ti ti-gift"></i>
                        تجربة مجانية كاملة المزايا
                    </span>
                    <h1 class="site-page-hero-title">
                        جرّب منصة لوت مجاناً واكتشف الفرق بنفسك
                    </h1>
                    <p class="site-page-hero-desc">
                        استمتع بجميع إمكانات لوت بدون أي التزامات مالية أو بطاقة ائتمانية. ابدأ تجربتك المجانية اليوم واكتشف
                        كيف ترتقي بأعمالك إلى آفاق جديدة.
                    </p>
                </div>
            </div>
        </section>

        <!-- Trial Registration & Info Section -->
        <section class="section-area">
            <div class="container">
                <div class="contact-layout">
                    <!-- Registration Form -->
                    <x-site.contact-form data-form-validate :action="route('site.trial.store')" :validation-url="route('site.form-validation')" fields="trial"
                        title="طلب تجربة مجانية" subtitle="انضم إلينا الآن"
                        desc="أدخل بياناتك وسنقوم بتفعيل حسابك التجريبي وتزويدك ببيانات الدخول مباشرة." />

                    <!-- Info Cards -->
                    <x-site.contact-info />
                </div>
            </div>
        </section>
    </main>

@endsection
