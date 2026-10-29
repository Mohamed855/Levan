@extends('emails.layouts.master')

@section('title', 'انتهى اشتراك منشأتك — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge danger">انتهى الاشتراك</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        نحيطك علماً بأن اشتراك منشأتك في منصة <strong>{{ $brandName }}</strong> قد انتهى. لقد تم تعليق بعض الصلاحيات تلقائياً لحين تجديد الاشتراك.
    </p>

    <div class="alert-box danger">
        بياناتك وملفاتك وإعدادات منشأتك محفوظة بأمان كامل. بمجرد التجديد، ستتم استعادة جميع صلاحياتك على الفور.
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $renewUrl }}" target="_blank" class="cta-button" style="background-color: #dc2626;">
            تجديد الاشتراك وتفعيل الحساب
        </a>
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px;">
        إذا كنت بحاجة إلى مساعدة أو خطة اشتراك مخصصة، يمكنك التواصل مع فريق المبيعات مباشرة عبر: <a href="mailto:{{ $officeEmail }}">{{ $officeEmail }}</a>
    </p>
@endsection
