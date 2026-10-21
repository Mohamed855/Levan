@extends('layouts.dashboard')

@section('title', 'لوحة إدارة المنصة')
@section('dashboard-scope', 'admin')

@section('content')
    <section class="row g-4 mb-4" aria-label="إحصاءات المنصة">
        @foreach ($statistics as $statistic)
            <div class="col-sm-6 col-xl">
                <x-shared.ui.stat-card :label="$statistic['label']" :value="$statistic['value']" :icon="$statistic['icon']" :tone="$statistic['tone']" />
            </div>
        @endforeach
    </section>

    <section class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">أحدث طلبات التجربة</h5>
            <span class="badge bg-label-primary">{{ number_format($faqCount) }} سؤال شائع</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الحالة</th><th>تاريخ الطلب</th></tr></thead>
                <tbody>
                    @forelse ($recentRequests as $request)
                        <tr><td>{{ $request->customer_name }}</td><td>{{ $request->customer_email }}</td><td><span class="badge bg-label-primary">{{ $request->status }}</span></td><td>{{ $request->created_at?->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد طلبات تجربة بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
