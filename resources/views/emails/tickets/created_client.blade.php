@extends('emails.layouts.master')

@section('title', 'تم فتح تذكرة دعم فني جديدة — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge info">تم استلام تذكرة الدعم الفني</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        تم استلام طلب الدعم الفني الخاص بك بنجاح، وتم إنشاء تذكرة برقم <strong>#{{ $ticket->ticket_number }}</strong>. سيعمل فريق الدعم المختص على متابعتها والرد عليك في أقرب وقت.
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات التذكرة</div>
        <div class="info-row">
            <span class="info-label">رقم التذكرة:</span>
            <span class="info-value" dir="ltr">#{{ $ticket->ticket_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">عنوان التذكرة:</span>
            <span class="info-value">{{ $ticket->title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">القسم:</span>
            <span class="info-value">{{ $ticket->section?->name ?? 'الدعم العام' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الأولوية:</span>
            <span class="info-value">{{ $ticket->priority_label ?? 'عادي' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الإنشاء:</span>
            <span class="info-value">{{ $ticket->created_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="info-card" style="border-right: 4px solid #3b82f6;">
        <div class="info-card-title" style="color: #1d4ed8;">تفاصيل المشكلة / الطلب:</div>
        <div style="font-size: 14px; color: #334155; line-height: 1.8; white-space: pre-wrap;">{{ $ticket->message ?? $ticket->description }}</div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $viewUrl }}" target="_blank" class="cta-button">
            متابعة حالة التذكرة
        </a>
    </div>
@endsection
