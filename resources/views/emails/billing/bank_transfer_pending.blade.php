@extends('emails.layouts.master')

@section('title', 'تم استلام إيصال التحويل البنكي — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge info">إيصال التحويل قيد التدقيق</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        لقد تم استلام إيصال التحويل البنكي الخاص بالفاتورة رقم <strong>#{{ $billNumber }}</strong> بنجاح. يقوم القسم المالي حالياً بمطابقة الحوالة واعتمادها.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل الفاتورة والحوالة</div>
        <div class="info-row">
            <span class="info-label">رقم الفاتورة:</span>
            <span class="info-value" dir="ltr">#{{ $billNumber }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المبلغ المطلوب:</span>
            <span class="info-value">{{ number_format((float)$amount, 2) }} ر.س</span>
        </div>
        @if(!empty($operationType))
            <div class="info-row">
                <span class="info-label">نوع العملية:</span>
                <span class="info-value">{{ $operationType }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">حالة الفاتورة:</span>
            <span class="info-value" style="color: #b45309;">بانتظار التحقق من الإيصال</span>
        </div>
    </div>

    <div class="alert-box info">
        تستغرق عملية التحقق من الحوالات البنكية عادة من 1 إلى 24 ساعة عمل. ستصلك رسالة تأكيد فور اكتمال التفعيل.
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        مع تحيات الإدارة المالية لمنصة {{ $brandName }}
    </p>
@endsection
