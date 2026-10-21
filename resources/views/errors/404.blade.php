@extends('layouts.error')
@section('title','الصفحة غير موجودة | 404')
@section('content')<main class="site-error-page"><div class="container site-error-container"><article class="site-error-card"><div class="site-error-code">404</div><h1 class="site-error-title">الصفحة غير موجودة</h1><p class="site-error-desc">تعذر العثور على الصفحة المطلوبة.</p><div class="site-error-actions"><a href="{{ route('site.index') }}" class="site-error-btn">العودة للرئيسية</a><a href="{{ route('site.index') }}#contact" class="site-error-btn-outline">تواصل مع الدعم</a></div></article></div></main>@endsection
