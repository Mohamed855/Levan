@extends('emails.layouts.master')

@section('title', 'رد جديد على التذكرة #' . $ticket->ticket_number . ' — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge info">رد جديد على التذكرة</span>
    </div>

    <h2 class="greeting">مرحباً {{ $recipientName }}،</h2>

    <p class="lead-text">
        تمت إضافة رد جديد على التذكرة رقم <strong>#{{ $ticket->ticket_number }}</strong> ({{ $ticket->title }}) من قبل <strong>{{ $senderName }}</strong>:
    </p>

    <div class="info-card" style="border-right: 4px solid #4f46e5;">
        <div class="info-card-title" style="color: #4338ca;">نص الرد الجديد:</div>
        <div style="font-size: 14.5px; color: #1e293b; line-height: 1.8; white-space: pre-wrap;">{{ $replyMessage }}</div>
    </div>

    <div class="info-card">
        <div class="info-card-title">معلومات التذكرة</div>
        <div class="info-row">
            <span class="info-label">رقم التذكرة:</span>
            <span class="info-value" dir="ltr">#{{ $ticket->ticket_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الحالة الحالية:</span>
            <span class="info-value">{{ $ticketStatusLabel }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الرد:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $viewUrl }}" target="_blank" class="cta-button">
            عرض التذكرة والرد
        </a>
    </div>
@endsection
