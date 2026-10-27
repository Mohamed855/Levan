@extends('emails.layouts.master')

@section('title', 'تم اعتماد الحوالة البنكية وتفعيل الحساب — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge success">✅ تم اعتماد الحوالة البنكية</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        يسرنا إبلاغك بأنه تم التحقق من الحوالة البنكية واعتماد الفاتورة رقم <strong>#{{ $bill->bill_number }}</strong> بنجاح. تم تفعيل اشتراكك وخدماتك فوراً.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل الفاتورة المعتمدة</div>
        <div class="info-row">
            <span class="info-label">رقم الفاتورة:</span>
            <span class="info-value" dir="ltr">#{{ $bill->bill_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المبلغ المعتمد:</span>
            <span class="info-value">{{ number_format((float)$bill->amount, 2) }} ر.س</span>
        </div>
        <div class="info-row">
            <span class="info-label">طريقة السداد:</span>
            <span class="info-value">تحويل بنكي</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الاعتماد:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            الانتقال إلى لوحة التحكم
        </a>
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center;">
        نشكركم لاختياركم منصة {{ $brandName }}، ونتمنى لكم تجربة عمل استثنائية.
    </p>
@endsection
