@extends('layouts.dashboard')
@section('title', 'تفاصيل الباقة')
@section('dashboard-scope', 'admin')
@section('content')<div class="d-flex gap-2 mb-4"><a class="btn btn-label-secondary" href="{{ route('admin.plans.index') }}">العودة للرئيسية</a><a class="btn btn-primary" href="{{ route('admin.plans.edit',$plan) }}">تعديل البيانات</a></div><section class="card"><div class="card-body"><h5>{{ $plan->name }}</h5><p class="text-muted">{{ $plan->description }}</p><dl class="row"><dt class="col-3">السعر بعد الخصم</dt><dd class="col-9">{{ number_format($plan->price_after_discount,2) }} ر.س</dd><dt class="col-3">دورة الفوترة</dt><dd class="col-9">{{ $plan->subscription_billing_cycle }}</dd></dl></div></section>@endsection
