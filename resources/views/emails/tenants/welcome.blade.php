@extends('emails.layouts.master')

@section('title', 'مرحباً بك في ' . $brandName . ' — بيانات مساحة العمل الخاصة بك')

@section('content')
    <div style="text-align: center;">
        @if ($creationSource === 'trial')
            <span class="status-badge success">تم تفعيل الحساب التجريبي بنجاح</span>
        @elseif($creationSource === 'superadmin')
            <span class="status-badge primary">تم إنشاء مساحة عمل منشأتكم بنجاح</span>
        @else
            <span class="status-badge success">تم تفعيل الاشتراك وتجهيز مساحة العمل بنجاح</span>
        @endif
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        @if ($creationSource === 'trial')
            يسرنا إبلاغك بأنه تمت الموافقة على طلبك وتجهيز مساحة العمل المستقلة الخاصة بمنشأتك
            <strong>{{ $companyName }}</strong> على منصة <strong>{{ $brandName }}</strong> لفترة تجريبية مجانية لمدة
            <strong>{{ $trialDays ?? 7 }} أيام</strong>!
        @elseif($creationSource === 'superadmin')
            يسرنا إبلاغك بأنه تم إنشاء وتجهيز مساحة العمل المستقلة الخاصة بمنشأتك <strong>{{ $companyName }}</strong> على
            منصة <strong>{{ $brandName }}</strong> من قِبل إدارة النظام، وجميع أدوات الإدارة أصبحت جاهزة للاستخدام الآن.
        @else
            يسعدنا انضمام منشأتكم <strong>{{ $companyName }}</strong> إلى عائلة <strong>{{ $brandName }}</strong>! تم
            تجهيز مساحة العمل المستقلة الخاصة بكم بنجاح، وبات بإمكانكم الآن تسجيل الدخول وإدارة كافة أعمالكم بكل سهولة.
        @endif
    </p>

    <div class="info-card">
        <div class="info-card-title">بيانات تسجيل الدخول إلى مساحة العمل</div>
        <div class="info-row">
            <span class="info-label">اسم المنشأة:</span>
            <span class="info-value">{{ $companyName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني لتسجيل الدخول:</span>
            <span class="info-value" dir="ltr">{{ $loginEmail }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">كلمة المرور:</span>
            <span class="info-value">
                @php
                    $displayPassword = $password ?? $temporaryPassword;
                @endphp
                @if (!empty($displayPassword))
                    <span dir="ltr"
                        style="font-family: monospace; font-size: 14px; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 4px; font-weight: 700; background-color: #f8fafc; color: #0f172a;">{{ $displayPassword }}</span>
                @elseif($creationSource === 'superadmin')
                    كلمة المرور المحددة لحسابكم من قِبل إدارة النظام
                @else
                    كلمة المرور المنشأة تلقائياً لحسابكم
                @endif
            </span>
        </div>
        @if ($creationSource === 'trial')
            <div class="info-row">
                <span class="info-label">نوع الحساب:</span>
                <span class="info-value">فترة تجريبية مجانية ({{ $trialDays ?? 7 }} أيام)</span>
            </div>
            @if (!empty($trialEndDate))
                <div class="info-row">
                    <span class="info-label">تاريخ نهاية التجربة:</span>
                    <span class="info-value">{{ $trialEndDate }}</span>
                </div>
            @endif
        @endif
    </div>

    <div class="cta-button-wrapper">
        <a href="{{ $loginUrl }}" target="_blank" class="cta-button">
            الدخول إلى لوحة التحكم الخاصة بك
        </a>
    </div>

    <div class="alert-box info">
        <strong>خطوتك الأولى:</strong> يمكنك الآن تسجيل الدخول باستخدام بريدك الإلكتروني الموضح أعلاه لإدارة العقارات
        والمزادات، تخصيص إعدادات المنشأة، وإضافة أعضاء فريق عملك. فريق الدعم الفني جاهز لمساعدتك في أي وقت.
    </div>

    <p style="font-size: 13px; color: #64748b; text-align: center; margin-top: 24px; margin-bottom: 12px;">
        رابط تسجيل الدخول المباشر: <br />
        <a href="{{ $loginUrl }}" target="_blank"
            style="word-break: break-all; color: #4f46e5; font-size: 13px; font-weight: 600;"
            dir="ltr">{{ $loginUrl }}</a>
    </p>

    <div
        style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 12px 16px; margin: 0 auto; text-align: center; max-width: 480px;">
        <span style="font-size: 12.5px; color: #475569; line-height: 1.6;">
            <strong>تنبيه هام:</strong> يُرجى حفظ والاحتفاظ برابط تسجيل الدخول الخاص بمنشأتك (في المفضلة لديك) لاستخدامه
            دائماً في تسجيل الدخول للمنصة ومتابعة أعمالك.
        </span>
    </div>
@endsection
