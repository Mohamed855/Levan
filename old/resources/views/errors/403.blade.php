@extends('web.layouts.main')
@section('title', 'غير مصرح بالوصول | 403')
@section('content')
    <main class="site-error-page">
        <div class="container site-error-container">
            <article class="site-error-card">
                <div class="site-error-code">403</div>
                <h1 class="site-error-title">غير مصرح لك بالدخول</h1>
                <p class="site-error-desc">ليس لديك الصلاحية الكافية للوصول إلى هذه الصفحة.</p>
                <div class="site-error-actions"><a href="{{ route('site.index') }}" class="site-error-btn">العودة
                        للرئيسية</a><a href="{{ route('site.index') }}#contact" class="site-error-btn-outline">تواصل مع
                        الدعم</a></div>
            </article>
        </div>
</main>@endsection
