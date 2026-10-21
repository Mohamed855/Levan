@extends('layouts.dashboard')
@section('title', 'تفاصيل الدور')
@section('dashboard-scope', 'tenant')
@section('content')<div class="d-flex gap-2 mb-4"><a class="btn btn-label-secondary" href="{{ route('tenant.roles.index') }}">العودة للرئيسية</a><a class="btn btn-primary" href="{{ route('tenant.roles.edit', $role) }}">تعديل الدور</a></div><section class="card"><div class="card-body"><h5>{{ $role->display_name }}</h5><p class="text-muted">{{ $role->name }}</p><h6>الصلاحيات</h6><div class="d-flex flex-wrap gap-2">@forelse($role->permissions as $permission)<span class="badge bg-label-primary">{{ $permission->display_name }}</span>@empty<span class="text-muted">لا توجد صلاحيات.</span>@endforelse</div></div></section>@endsection
