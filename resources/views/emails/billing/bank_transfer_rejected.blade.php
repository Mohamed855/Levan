@extends('emails.layouts.master')

@section('title', 'تعذر اعتماد الحوالة البنكية — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge danger">تعذر اعتماد الحوالة</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        نحيطك علماً بأنه تعذر على القسم المالي التحقق من إيصال التحويل البنكي المرفوع للفاتورة رقم <strong>#{{ $bill->bill_number }}</strong>.
    </p>

    @if(!empty($reason))
        <div class="info-card" style="border-right: 4px solid #ef4444;">
            <div class="info-card-title" style="color: #991b1b;">ملاحظات التدقيق المالي:</div>
            <div style="font-size: 14px; color: #334155; line-height: 1.7;">
                {{ $reason }}
            </div>
        </div>
    @endif

    <div class="info-card">
        <div class="info-card-title">بيانات الفاتورة</div>
        <div class="info-row">
            <span class="info-label">رقم الفاتورة:</span>
            <span class="info-value" dir="ltr">#{{ $bill->bill_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المبلغ المطلوب:</span>
            <span class="info-value">{{ number_format((float)$bill->amount, 2) }} ر.س</span>
        </div>
    </div>

    @if(!empty($uploadUrl))
        <div class="cta-button-wrapper">
            <a href="{{ $uploadUrl }}" target="_blank" class="cta-button">
                إعادة رفع الإيصال الصحيح أو السداد
            </a>
        </div>
    @endif

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        إذا كنت بحاجة إلى استفسار حول تفاصيل الحسابات البنكية المعتمدة، يرجى التواصل مع فريق المالية: <a href="mailto:{{ $officeEmail }}">{{ $officeEmail }}</a>
    </p>
@endsection
