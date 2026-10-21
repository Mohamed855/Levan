@extends('layouts.dashboard')
@section('title', 'تفاصيل مستخدم')
@section('dashboard-scope', 'tenant')
@section('content')
    <div class="d-flex gap-2 mb-4"><a class="btn btn-label-secondary" href="{{ route('tenant.users.index') }}">العودة للرئيسية</a><a class="btn btn-primary" href="{{ route('tenant.users.edit', $user) }}">تعديل البيانات</a></div>
    <section class="card"><div class="card-body"><h5>{{ $user->name }}</h5><dl class="row mb-0"><dt class="col-md-3">البريد</dt><dd class="col-md-9">{{ $user->email }}</dd><dt class="col-md-3">الجوال</dt><dd class="col-md-9">{{ $user->phone ?: '—' }}</dd><dt class="col-md-3">الأدوار</dt><dd class="col-md-9">{{ $user->roles->pluck('display_name')->join('، ') ?: 'دون دور' }}</dd><dt class="col-md-3">الحالة</dt><dd class="col-md-9">{{ $user->status === 'active' ? 'نشط' : 'غير نشط' }}</dd></dl></div></section>
@endsection
