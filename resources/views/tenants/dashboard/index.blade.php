@extends('tenants.layouts.layoutMaster')

@section('title', 'لوحة القيادة')

@section('content')
    <div class="row g-4 mb-4">
        <!-- بطاقة الترحيب -->
        <div class="col-12">
            <div class="card bg-label-primary border-0 shadow-none">
                <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center p-4">
                    <div class="mb-3 mb-sm-0">
                        <h4 class="card-title text-primary mb-1">مرحباً بك، {{ auth()->user()->name }} 👋</h4>
                        <p class="text-body mb-0">لوحة تحكم الشركة - جاهزة للانطلاق في إدارة المزادات العقارية</p>
                    </div>
                    <div class="d-flex gap-2">
                        @can('users.view')
                            <a href="{{ tenant_route_name('users.index') }}" class="btn btn-primary">
                                <i class="ti ti-users me-1"></i> إدارة المستخدمين
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- الإحصائيات الأساسية -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted d-block mb-1">المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['total_users'] }}</h4>
                                <span class="badge bg-label-success">{{ $stats['active_users'] }} نشط</span>
                            </div>
                            <small class="text-muted">الحد المتاح: {{ $stats['total_seats'] }} مقعد</small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-user-check ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted d-block mb-1">فرق العمل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['total_teams'] }}</h4>
                            </div>
                            <small class="text-muted">المجموعات التشغيلية</small>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-users-group ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted d-block mb-1">باقة الاشتراك</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2 text-primary">{{ $stats['plan_name'] }}</h5>
                            </div>
                            <small class="text-muted">
                                الحالة: <span class="badge bg-label-success">{{ $stats['subscription_status'] }}</span>
                            </small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-award ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- تنبيه بانتظار التحليل العقاري -->
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center p-5">
                    <div class="avatar avatar-xl bg-label-primary mx-auto mb-3">
                        <i class="ti ti-gavel ti-xl"></i>
                    </div>
                    <h4 class="mb-2">لوت للمزادات LOT</h4>
                    <p class="text-muted mx-auto mb-4" style="max-width: 600px;">

                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ tenant_route_name('settings.index') }}" class="btn btn-outline-primary">
                            <i class="ti ti-settings me-1"></i> إعدادات الشركة
                        </a>
                        <a href="{{ tenant_route_name('tickets.index') }}" class="btn btn-secondary">
                            <i class="ti ti-headset me-1"></i> الدعم الفني
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
