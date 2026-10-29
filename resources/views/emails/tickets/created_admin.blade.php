@extends('emails.layouts.master')

@section('title', 'تذكرة دعم فني جديدة — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge warning">تذكرة دعم فني جديدة</span>
    </div>

    <h2 class="greeting">تنبيه: تم إنشاء تذكرة دعم فني جديدة</h2>

    <p class="lead-text">
        قام العميل <strong>{{ $companyName }}</strong> بفتح تذكرة دعم فني جديدة تتطلب متابعة فريق الدعم:
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات التذكرة</div>
        <div class="info-row">
            <span class="info-label">رقم التذكرة:</span>
            <span class="info-value" dir="ltr">#{{ $ticket->ticket_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المنشأة/العميل:</span>
            <span class="info-value">{{ $companyName }}</span>
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

    <div class="info-card" style="border-right: 4px solid #f59e0b;">
        <div class="info-card-title" style="color: #b45309;">نص رسالة العميل:</div>
        <div style="font-size: 14px; color: #334155; line-height: 1.8; white-space: pre-wrap;">{{ $ticket->message ?? $ticket->description }}</div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ route('admin.tickets.show', $ticket->ticket_number) }}" target="_blank" class="cta-button">
            معاينة التذكرة والرد
        </a>
    </div>
@endsection
