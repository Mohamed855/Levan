@extends('site.layouts.main')

@section('title', 'غير مصرح بالوصول | 403')

@section('content')
    <main class="site-error-page" id="main-content" role="main">
        <div class="container site-error-container">
            <article class="site-error-card" aria-labelledby="error-title">
                <div class="site-error-badge">
                    <i class="ti ti-shield-lock" aria-hidden="true"></i>
                    <span>رمز الخطأ 403</span>
                </div>
                <div class="site-error-code" aria-hidden="true">403</div>
                <h1 id="error-title" class="site-error-title">غير مصرح لك بالوصول لهذه الصفحة</h1>
                <p class="site-error-desc">
                    عذراً، ليس لديك الصلاحيات الكافية للوصول إلى هذا المحتوى أو الرابط. إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع فريق الدعم.
                </p>
                <div class="site-error-actions">
                    <a href="{{ route('site.index') }}" class="site-error-btn">
                        <i class="ti ti-home" aria-hidden="true"></i>
                        <span>العودة للرئيسية</span>
                    </a>
                    <a href="{{ route('site.index') }}#contact" class="site-error-btn-outline">
                        <i class="ti ti-help-circle" aria-hidden="true"></i>
                        <span>طلب المساعدة</span>
                    </a>
                </div>
            </article>
        </div>
    </main>
@endsection
