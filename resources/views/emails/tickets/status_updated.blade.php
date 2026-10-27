@extends('emails.layouts.master')

@section('title', 'تحديث حالة التذكرة #' . $ticket->ticket_number . ' — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge {{ $newStatus === 'resolved' || $newStatus === 'closed' ? 'success' : 'info' }}">
            🔄 تحديث التذكرة
        </span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        تم تحديث حالة التذكرة رقم <strong>#{{ $ticket->ticket_number }}</strong> ({{ $ticket->title }}) إلى:
        <strong style="color: #4f46e5;">{{ $newStatusLabel }}</strong>.
    </p>

    <div class="info-card">
        <div class="info-card-title">تفاصيل التذكرة</div>
        <div class="info-row">
            <span class="info-label">رقم التذكرة:</span>
            <span class="info-value" dir="ltr">#{{ $ticket->ticket_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">عنوان التذكرة:</span>
            <span class="info-value">{{ $ticket->title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الحالة الجديدة:</span>
            <span class="info-value">{{ $newStatusLabel }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التحديث:</span>
            <span class="info-value">{{ date('Y-m-d H:i') }}</span>
        </div>
    </div>

    @if($newStatus === 'resolved' || $newStatus === 'resolved_pending_survey')
        <div class="alert-box info">
            🌟 يسعدنا أن نكون قد ساعدناك في حل المشكلة. نرجو منك تقييم جودة الخدمة المقدمة من خلال الدخول إلى التذكرة لمساعدتنا على تحسين خدماتنا باستمرار.
        </div>
    @endif

    <div class="cta-button-wrapper">
        <a href="{{ $viewUrl }}" target="_blank" class="cta-button">
            عرض تفاصيل التذكرة
        </a>
    </div>
@endsection
