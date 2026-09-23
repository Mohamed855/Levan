@extends('site.layout.soon_main')

@section('title', 'ليفـــان LEVAN')

@section('page-style')
    @vite(['resources/assets/css/site/landing.css', 'resources/assets/css/components/icons.css'])
@endsection

@section('content')

    <main class="main relative">
        <!-- Hero section -->
        <section id="home"
            class="relative flex min-h-screen items-center overflow-hidden bg-primary text-primary-color pt-[120px] pb-16 md:pt-[130px] lg:pt-[150px]">
            <div class="container">
                <div class="-mx-5 flex flex-wrap items-center">
                    <div class="w-full px-5">
                        <div class="scroll-revealed mx-auto max-w-[780px] text-center">
                            <h1
                                class="mb-6 text-3xl font-bold leading-snug text-primary-color sm:text-4xl sm:leading-snug lg:text-5xl lg:leading-tight">
                                ليفـــان

                            </h1>

                            <p class="mx-auto mb-9 max-w-[600px] text-base text-primary-color sm:text-lg sm:leading-normal">
                                الموقع قيد التحديث حاليًا
                            </p>
                            نعتذر عن أي إزعاج، ونشكر لكم تفهمكم.



                        </div>
                    </div>
                    <div class="w-full px-5" style="margin-top:42px">
                        <div class="scroll-revealed relative z-10 mx-auto max-w-[845px]">
                            <figure class="mt-16">
                                <img src="{{ asset('assets/website/img/hero.png') }}" alt="Hero image"
                                    class="mx-auto max-w-full rounded-t-xl rounded-tr-xl" />
                            </figure>

                            <div class="absolute -left-9 bottom-0 z-[-1]">
                                <img src="{{ asset('assets/website/img/dots.svg') }}" alt class="w-[120px] opacity-75" />
                            </div>

                            <div class="absolute -right-6 -top-6 z-[-1]">
                                <img src="{{ asset('assets/website/img/dots.svg') }}" alt class="w-[120px] opacity-75" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    </main>

@endsection
