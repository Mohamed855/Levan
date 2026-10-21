@extends('layouts.dashboard')
@section('title', 'تفاصيل الاشتراك — ' . $subscription->subscription_number)
@section('dashboard-scope', 'admin')

@section('content')

<div class="row g-4">
    {{-- Left: Subscription Details --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">

        {{-- Profile card --}}
        <div class="card mb-4">
            <div class="card-body text-center pt-5 pb-4">
                <div class="avatar avatar-xl mb-3">
                    <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.5rem;">
                        <i class="ti ti-file-invoice"></i>
                    </span>
                </div>
                <h5 class="mb-1">{{ $subscription->subscription_number }}</h5>
                <p class="text-muted mb-3">{{ $subscription->tenant?->company_name ?? '—' }}</p>

                @php
                    $statusColors = [
                        'active'    => 'success',
                        'trial'     => 'info',
                        'pending'   => 'warning',
                        'suspended' => 'secondary',
                        'expired'   => 'danger',
                        'cancelled' => 'dark',
                    ];
                    $statusLabels = [
                        'active'    => 'نشط',
                        'trial'     => 'تجريبي',
                        'pending'   => 'معلّق',
                        'suspended' => 'موقوف',
                        'expired'   => 'منتهٍ',
                        'cancelled' => 'ملغي',
                    ];
                @endphp

                <span class="badge bg-label-{{ $statusColors[$subscription->status] ?? 'primary' }} fs-6 px-3 py-2">
                    {{ $statusLabels[$subscription->status] ?? $subscription->status }}
                </span>
            </div>
            <div class="card-body border-top">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">الباقة</span>
                        <span class="fw-semibold">{{ $subscription->plan?->name ?? '—' }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">دورة الفوترة</span>
                        <span class="fw-semibold">{{ $subscription->billing_cycle }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">السعر</span>
                        <span class="fw-semibold">{{ number_format((float) $subscription->price, 2) }} ر.س</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">البداية</span>
                        <span class="fw-semibold">{{ $subscription->starts_at?->format('Y-m-d') ?? '—' }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">النهاية</span>
                        <span class="fw-semibold">{{ $subscription->ends_at?->format('Y-m-d') ?? '—' }}</span>
                    </li>
                    @if($subscription->cancelled_at)
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">تاريخ الإلغاء</span>
                            <span class="fw-semibold text-danger">{{ $subscription->cancelled_at->format('Y-m-d') }}</span>
                        </li>
                    @endif
                    <li class="d-flex justify-content-between py-2">
                        <span class="text-muted">المستخدمون</span>
                        <span class="fw-semibold">{{ $subscription->used_users }} / {{ $subscription->total_users }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Update Status --}}
        @can('subscriptions.update')
            @if(count($allowedTransitions) > 0)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">تغيير الحالة</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.subscriptions.status', $subscription) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة الجديدة</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="">— اختر الحالة —</option>
                                    @foreach($allowedTransitions as $s)
                                        <option value="{{ $s }}">{{ $statusLabels[$s] ?? $s }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-refresh me-1"></i>تحديث الحالة
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary">
                    <i class="ti ti-lock me-1"></i>
                    لا يمكن تغيير حالة هذا الاشتراك حالياً.
                </div>
            @endif
        @endcan

    </div>

    {{-- Right: Periods table --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">فترات الاشتراك</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>البداية</th>
                            <th>النهاية</th>
                            <th>الباقة</th>
                            <th>السعر</th>
                            <th>الحالة</th>
                            <th>الحالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscription->periods as $period)
                            <tr>
                                <td>{{ $period->period_number }}</td>
                                <td>{{ $period->period_start?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $period->period_end?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $period->plan_name_snapshot ?? '—' }}</td>
                                <td>{{ number_format((float) $period->price_snapshot, 2) }} ر.س</td>
                                <td>
                                    <span class="badge bg-label-{{ $statusColors[$period->status] ?? 'primary' }}">
                                        {{ $statusLabels[$period->status] ?? $period->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($period->is_current)
                                        <span class="badge bg-label-success">الحالي</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">لا توجد فترات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i>العودة للقائمة
        </a>
    </div>
</div>

@endsection
