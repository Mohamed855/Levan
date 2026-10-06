@extends('site.layouts.main')

@section('title', 'حدث خطأ في الخادم')

@section('content')
    <section class="px-6 py-24 sm:py-32" aria-labelledby="error-title">
        <div class="mx-auto max-w-xl rounded-3xl border border-primary-light-2 bg-white p-8 text-center shadow-lg sm:p-12">
            <p class="text-sm font-semibold text-primary">500</p>
            <h1 id="error-title" class="mt-3 text-3xl font-bold text-primary sm:text-4xl">حدث خطأ غير متوقع</h1>
            <p class="mt-4 text-base leading-7 text-primary-dark-2">نعتذر عن هذا الخطأ. يرجى المحاولة مرة أخرى لاحقاً.</p>
            <a href="{{ route('site.index') }}" class="mt-8 inline-flex rounded-xl bg-primary px-5 py-3 font-semibold text-white transition-colors hover:bg-primary-dark-1 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                العودة إلى الصفحة الرئيسية
            </a>
        </div>
    </section>
@endsection
