@extends('site.layouts.main')

@section('title', 'الصفحة غير موجودة | 404')

@section('content')
    <main class="site-error-page" id="main-content" role="main">
        <div class="container site-error-container">
            <article class="site-error-card" aria-labelledby="error-title">
                <div class="site-error-badge">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <span>رمز الخطأ 404</span>
                </div>
                <div class="site-error-code" aria-hidden="true">404</div>
                <h1 id="error-title" class="site-error-title">الصفحة التي تبحث عنها غير موجودة</h1>
                <p class="site-error-desc">
                    عذراً، لم نتمكن من العثور على الصفحة المطلوبة. قد تكون الصفحة قد نُقلت أو تم حذفها أو أنك اتبعت رابطاً غير صحيح.
                </p>
                <div class="site-error-actions">
                    <a href="{{ route('site.index') }}" class="site-error-btn">
                        <i class="ti ti-home" aria-hidden="true"></i>
                        <span>العودة للرئيسية</span>
                    </a>
                    <a href="{{ route('site.index') }}#contact" class="site-error-btn-outline">
                        <i class="ti ti-headset" aria-hidden="true"></i>
                        <span>تواصل مع الدعم</span>
                    </a>
                </div>
            </article>
        </div>
    </main>
@endsection
