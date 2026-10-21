@extends('layouts.dashboard')

@section('title', 'لوحة المستأجر')
@section('dashboard-scope', 'tenant')

@section('content')
    <section class="row g-4 mb-4" aria-label="إحصاءات المستأجر">
        @foreach ($statistics as $statistic)
            <div class="col-sm-6 col-xl">
                <x-shared.ui.stat-card :label="$statistic['label']" :value="$statistic['value']" :icon="$statistic['icon']" :tone="$statistic['tone']" />
            </div>
        @endforeach
    </section>

    <section class="card">
        <div class="card-header"><h5 class="mb-0">أحدث المستخدمين</h5></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الحالة</th><th>تاريخ الإضافة</th></tr></thead>
                <tbody>
                    @forelse ($recentUsers as $user)
                        <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td><span class="badge bg-label-primary">{{ $user->status }}</span></td><td>{{ $user->created_at?->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا يوجد مستخدمون بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
