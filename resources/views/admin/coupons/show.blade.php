@extends('layouts.dashboard')

@section('title', 'تفاصيل الكوبون')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $coupon->name }}</h4>
            <code class="fs-6">{{ $coupon->code }}</code>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-label-secondary">رجوع</a>
    </div>

    <section class="card mb-4">
        <div class="card-body row g-3">
            <div class="col-md-3"><span class="text-muted d-block">قيمة الخصم</span><strong>{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value.'%' : number_format((float) $coupon->discount_value, 2).' ر.س' }}</strong></div>
            <div class="col-md-3"><span class="text-muted d-block">سقف الخصم</span><strong>{{ $coupon->max_discount === null ? 'غير محدد' : number_format((float) $coupon->max_discount, 2).' ر.س' }}</strong></div>
            <div class="col-md-2"><span class="text-muted d-block">الاستخدام</span><strong>{{ $coupon->used_count }}{{ $coupon->usage_limit === null ? '' : ' / '.$coupon->usage_limit }}</strong></div>
            <div class="col-md-2"><span class="text-muted d-block">الصلاحية</span><strong>{{ $coupon->expires_at?->format('Y-m-d') ?? 'دائم' }}</strong></div>
            <div class="col-md-2"><span class="text-muted d-block">الحالة</span><strong>{{ $coupon->is_active ? 'نشط' : 'غير نشط' }}</strong></div>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h5 class="mb-0">سجل الحجوزات والاسترداد</h5></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>الفاتورة</th><th>العميل</th><th>الخصم</th><th>الحالة</th><th>تاريخ الحجز</th><th>تاريخ الاسترداد</th></tr></thead>
                <tbody>
                    @forelse ($redemptions as $redemption)
                        <tr>
                            <td>{{ $redemption->bill?->bill_number ?? '-' }}</td>
                            <td>{{ $redemption->bill?->tenant?->company_name ?? '-' }}</td>
                            <td>{{ number_format((float) $redemption->discount_amount, 2) }} ر.س</td>
                            <td>{{ $redemption->status }}</td>
                            <td>{{ $redemption->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $redemption->redeemed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">لا توجد استخدامات مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($redemptions->hasPages())
            <div class="card-body">{{ $redemptions->links() }}</div>
        @endif
    </section>
@endsection
