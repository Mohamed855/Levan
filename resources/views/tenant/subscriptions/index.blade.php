@extends('layouts.dashboard')

@section('title', 'الاشتراك والتراخيص')
@section('dashboard-scope', 'tenant')

@section('content')
<div class="container-fluid" dir="rtl">
    <h1 class="h4 mb-4">الاشتراك والتراخيص</h1>
    @if ($subscription)
        <div class="card mb-4"><div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md"><strong>{{ $subscription->plan?->name }}</strong><div class="text-muted">ينتهي: {{ $subscription->ends_at?->format('Y-m-d') ?? 'غير محدد' }}</div></div>
                <div class="col-md">المقاعد المستخدمة: {{ $subscription->used_users }} / {{ $subscription->total_users }}</div>
                <div class="col-md-auto"><span class="badge bg-label-primary">{{ $subscription->status }}</span></div>
            </div>
        </div></div>
        @can('subscriptions.renew')
        <form class="card card-body mb-3" method="POST" action="{{ route('tenant.subscriptions.renew') }}">@csrf
            <div class="d-flex flex-wrap gap-2 align-items-center"><strong>تجديد الاشتراك</strong><select class="form-select w-auto" name="payment_method"><option value="online">دفع إلكتروني</option><option value="bank_transfer">تحويل بنكي</option></select><button class="btn btn-primary">إنشاء فاتورة التجديد</button></div>
        </form>
        @endcan
        @can('subscriptions.add_licenses')
        <form class="card card-body" method="POST" action="{{ route('tenant.subscriptions.licenses') }}">@csrf
            <div class="d-flex flex-wrap gap-2 align-items-center"><strong>شراء تراخيص إضافية</strong><input class="form-control w-auto" name="license_count" type="number" min="1" value="1"><select class="form-select w-auto" name="payment_method"><option value="online">دفع إلكتروني</option><option value="bank_transfer">تحويل بنكي</option></select><button class="btn btn-primary">إنشاء فاتورة الشراء</button></div>
        </form>
        @endcan
    @else
        <div class="alert alert-warning">لا توجد بيانات اشتراك للمنشأة. راجع إدارة المنصة.</div>
    @endif
</div>
@endsection
