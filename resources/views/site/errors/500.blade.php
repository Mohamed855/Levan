@extends('site.layouts.main')

@section('title', 'خطأ في الخادم | 500')

@section('content')
    <main class="site-error-page" id="main-content" role="main">
        <div class="container site-error-container">
            <article class="site-error-card" aria-labelledby="error-title">
                <div class="site-error-badge">
                    <i class="ti ti-server-off" aria-hidden="true"></i>
                    <span>رمز الخطأ 500</span>
                </div>
                <div class="site-error-code" aria-hidden="true">500</div>
                <h1 id="error-title" class="site-error-title">حدث خطأ غير متوقع في الخادم</h1>
                <p class="site-error-desc">
                    نعتذر بشدة عن هذا العطل الفني المؤقت. يعمل فريقنا الهندسي حالياً على حل المشكلة واستعادة الخدمة في أسرع وقت.
                </p>
                <div class="site-error-actions">
                    <a href="{{ route('site.index') }}" class="site-error-btn">
                        <i class="ti ti-home" aria-hidden="true"></i>
                        <span>العودة للرئيسية</span>
                    </a>
                    <a href="javascript:location.reload()" class="site-error-btn-outline">
                        <i class="ti ti-refresh" aria-hidden="true"></i>
                        <span>إعادة المحاولة</span>
                    </a>
                </div>
            </article>
        </div>
    </main>
@endsection
