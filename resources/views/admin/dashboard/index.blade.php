@extends('layouts.dashboard')

@section('title', 'لوحة إدارة المنصة')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="row g-4 mb-4" aria-label="إحصاءات المنصة">
        @foreach ($statistics as $statistic)
            <div class="col-sm-6 col-lg-4 col-xl-2">
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
        {{-- جدول أحدث طلبات التجربة --}}
        <div class="col-lg-6 col-12">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-clock-hour-4 text-primary me-2"></i> أحدث طلبات التجربة
                    </h5>
                    <a href="{{ route('admin.trial-requests.index') }}" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>مقدم الطلب</th>
                                <th>المنشأة</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTrialRequests as $request)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $request->customer_name }}</div>
                                        <small class="text-muted">{{ $request->customer_phone ?? $request->customer_email }}</small>
                                    </td>
                                    <td>{{ $request->company_name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ $request->status === 'approved' ? 'تم القبول' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted">{{ $request->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد طلبات تجربة جديدة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- جدول أحدث تذاكر الدعم الفني --}}
        <div class="col-lg-6 col-12">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="ti ti-headset text-danger me-2"></i> تذاكر الدعم الفني الأخيرة
                    </h5>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-label-danger">
                        عرض الكل
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التذكرة</th>
                                <th>المستأجر</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="fw-bold text-primary">
                                            #{{ $ticket->ticket_number }}
                                        </a>
                                        <div class="small text-muted text-truncate" style="max-width: 180px;">{{ $ticket->subject }}</div>
                                    </td>
                                    <td>{{ $ticket->tenant?->company_name ?? 'مستأجر' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ticket->status->color() }}">
                                            {{ $ticket->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted">{{ $ticket->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد تذاكر دعم فني.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
