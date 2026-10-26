@extends('layouts.web')
@section('title', 'تم إرسال طلب التجربة — ليفان')
@section('content')
<section class="container py-5 text-center">
    <h1>تم إرسال طلب التجربة بنجاح</h1>
    <p>تم تأكيد بريدك، وطلبك الآن قيد مراجعة الإدارة. بعد الموافقة يصبح حساب شركتك جاهزًا وتبدأ فترة التجربة لمدة 7 أيام.</p>
    <a class="btn btn-primary" href="{{ route('site.index') }}">العودة للرئيسية</a>
</section>
@endsection
