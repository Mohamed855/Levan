@extends('layouts.dashboard')

@section('title', 'لوحة التحكم للمستأجر')
@section('dashboard-scope', 'tenant')

@section('content')
    {{-- تنبيه حالة الاشتراك --}}
    @if ($subscription)
        <div class="card mb-4 border-0 {{ $subscription['is_expired'] ? 'bg-danger-subtle border-danger' : ($subscription['days_remaining'] !== null && $subscription['days_remaining'] < 30 ? 'bg-warning-subtle border-warning' : 'bg-primary-subtle border-primary') }}">
            <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md {{ $subscription['is_expired'] ? 'bg-danger text-white' : ($subscription['days_remaining'] !== null && $subscription['days_remaining'] < 30 ? 'bg-warning text-dark' : 'bg-primary text-white') }} rounded d-flex align-items-center justify-content-center">
                        <i class="ti ti-crown fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">الاشتراك الحالي: {{ $subscription['plan_name'] }}</h6>
                        <small class="text-muted">
                            @if ($subscription['is_expired'])
                                <span class="text-danger fw-bold">انتهى الاشتراك في {{ $subscription['ends_at'] }}</span>
                            @elseif ($subscription['days_remaining'] !== null)
                                متبقي <span class="fw-bold">{{ $subscription['days_remaining'] }} يوم</span> (ينتهي في {{ $subscription['ends_at'] }})
                            @else
                                اشتراك فعال
                            @endif
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('tenant.bills.index') }}" class="btn btn-sm {{ $subscription['is_expired'] ? 'btn-danger' : 'btn-primary' }}">
                        <i class="ti ti-receipt me-1"></i> الفواتير والاشتراكات
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- بطاقات الإحصاءات التشغيلية الأربعة --}}
    <div class="row g-4 mb-4" aria-label="إحصاءات المستأجر">
        @foreach ($statistics as $statistic)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar avatar-sm bg-label-{{ $statistic['tone'] }} rounded me-2 d-flex align-items-center justify-content-center">
                                <i class="ti {{ $statistic['icon'] }} fs-5"></i>
                            </div>
                            <span class="fw-semibold text-muted small text-truncate" title="{{ $statistic['label'] }}">{{ $statistic['label'] }}</span>
                        </div>
                        <h4 class="fw-bold mb-1">{{ number_format($statistic['value']) }}</h4>
                        <small class="text-muted d-block text-truncate">{{ $statistic['subtitle'] }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- جدول أحدث الإسنادات --}}
        <div class="col-lg-6 col-12">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-file-description text-primary me-2"></i> أحدث الإسنادات
                    </h5>
                    <a href="{{ route('tenant.assignments.index') }}" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الإسناد</th>
                                <th class="text-center">العقارات</th>
                                <th class="text-center">المزادات</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAssignments as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('tenant.assignments.show', $assignment) }}" class="fw-bold text-primary">
                                            {{ $assignment->name }}
                                        </a>
                                        <div class="small text-muted font-monospace">#{{ $assignment->assignment_number }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-info">{{ $assignment->properties_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-secondary">{{ $assignment->auctions_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $assignment->is_active ? 'success' : 'danger' }}">
                                            {{ $assignment->is_active ? 'نشط' : 'معطل' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد إسنادات مضافة بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- جدول أحدث المزادات --}}
        <div class="col-lg-6 col-12">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-gavel text-info me-2"></i> أحدث المزادات
                    </h5>
                    <a href="{{ route('tenant.auctions.index') }}" class="btn btn-sm btn-label-info">
                        عرض الكل
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المزاد</th>
                                <th>الإسناد التابع</th>
                                <th class="text-center">تاريخ البدء</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAuctions as $auction)
                                <tr>
                                    <td>
                                        <a href="{{ route('tenant.auctions.show', $auction) }}" class="fw-bold text-primary">
                                            {{ $auction->name }}
                                        </a>
                                        <div class="small text-muted">{{ $auction->type }}</div>
                                    </td>
                                    <td>{{ $auction->assignment?->name ?? 'غير مرتبط' }}</td>
                                    <td class="text-center small text-muted">{{ $auction->start_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $auction->is_active ? 'success' : 'secondary' }}">
                                            {{ $auction->is_active ? 'نشط' : 'متوقف' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد مزادات مسجلة بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
