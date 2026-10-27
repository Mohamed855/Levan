@extends('emails.layouts.master')

@section('title', 'بخصوص طلب التجربة المجانية — ' . $brandName)

@section('content')
    <div style="text-align: center;">
        <span class="status-badge danger">طلب التجربة المجانية</span>
    </div>

    <h2 class="greeting">مرحباً {{ $customerName }}،</h2>

    <p class="lead-text">
        نشكرك على اهتمامك بمنصة <strong>{{ $brandName }}</strong>. بعد مراجعة بيانات الطلب المقدم للحصول على تجربة مجانية، نأسف لإبلاغك بتعذر قبول الطلب في الوقت الحالي.
    </p>

    @if(!empty($reason))
        <div class="info-card" style="border-right: 4px solid #ef4444;">
            <div class="info-card-title" style="color: #991b1b;">سبب الاعتذار:</div>
            <div style="font-size: 14px; color: #334155; line-height: 1.7;">
                {{ $reason }}
            </div>
        </div>
    @endif

    <div class="alert-box info">
        إذا كنت تعتقد أن هناك خطأ أو ترغب في تعديل بيانات منشأتك وإعادة تقديم الطلب، يسعدنا تواصلك معنا مباشرة عبر:
        <br />
        البريد: <a href="mailto:{{ $officeEmail }}">{{ $officeEmail }}</a>
        @if(!empty($officePhone))
            | الهاتف: <span dir="ltr">{{ $officePhone }}</span>
        @endif
    </div>

    <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
        مع أطيب التحيات،<br />
        فريق إدارة {{ $brandName }}
    </p>
@endsection
