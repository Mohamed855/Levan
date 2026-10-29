@extends('emails.layouts.master')

@section('title', 'إشعار تحويل بنكي جديد للمراجعة — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">إيصال تحويل بنكي جديد</span>
    </div>

    <h2 class="greeting">تنبيه مالي: تم رفع إيصال تحويل بنكي جديد</h2>

    <p class="lead-text">
        قام عميل برفع إيصال تحويل بنكي لسداد فاتورة في منصة <strong>{{ $brandName }}</strong> ويتطلب مراجعة واعتماد المحاسبة:
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل الفاتورة والمحول</div>
        <div class="info-row">
            <span class="info-label">رقم الفاتورة:</span>
            <span class="info-value" dir="ltr">#{{ $bill->bill_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">اسم العميل/المنشأة:</span>
            <span class="info-value">{{ $customerName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المبلغ:</span>
            <span class="info-value" style="color: #047857; font-weight: 700;">{{ number_format((float)$bill->amount, 2) }} ر.س</span>
        </div>
        <div class="info-row">
            <span class="info-label">نوع العملية:</span>
            <span class="info-value">{{ $bill->type?->label() ?? 'اشتراك جديد' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الرفع:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ route('admin.bills.show', $bill->id) }}" target="_blank" class="cta-button">
            معاينة الإيصال وتأكيد الفاتورة
        </a>
    </div>
@endsection
