<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="auction-regulatory-form" content="{{ $type }}">
    <title>{{ $type === 'establishment' ? 'نموذج طلب إقامة مزاد عقاري' : 'نموذج إقفال المزاد العقاري' }}</title>
    <style>{!! $css !!}</style>
</head>
<body>
@foreach($pages as $page)
    <article class="form-page form-page--{{ $page['template'] }}" data-form-page="{{ $page['template'] }}" data-orientation="{{ $page['orientation'] }}" style="page:{{ $page['orientation'] }}">
        <img class="watermark" src="{{ $asset('watermark-'.$page['watermark'].'.svg') }}" alt="">
        <header class="form-header">
            <img class="form-logo" src="{{ $asset('logo.png') }}" alt="الهيئة العامة للعقار REGA">
            @if(!in_array($page['template'], ['properties', 'closing']))
                <div class="submission"><span>تاريخ تقديم الطلب</span> <span class="dotted data-value">{{ $value('submission_date') }}</span></div>
            @endif
            @if(in_array($page['template'], ['organization', 'auction', 'property', 'declaration']))
                <h1>نموذج طلب إقامة مزاد عقاري</h1>
            @elseif($page['template'] === 'agents')
                <h1>جدول الوكلاء</h1>
            @elseif($page['template'] === 'properties')
                <h1>جدول البيان لمعلومات العقارات محل المزاد العقاري</h1>
                <p class="header-note">يمكن تكرار البيان عند زيادة العقارات محل المزاد العقاري</p>
            @else
                <div class="closing-dates">
                    <div>تاريخ إقفال المزاد <span class="dotted data-value">{{ $value('closing.date') }}</span></div>
                    <div>رقم الموافقة على إقامة المزاد <span class="dotted data-value">{{ $value('closing.approval_number') }}</span></div>
                </div>
                <h1>نموذج إقفال المزاد العقاري</h1>
                <p class="header-note">جدول البيان لنموذج إقفال المزاد العقاري</p>
            @endif
        </header>
        @include('tenant.auctions.regulatory-forms.'.$page['template'])
        @if($type === 'establishment')
            <footer class="page-number" dir="ltr">{{ $loop->iteration }}/{{ count($pages) }}</footer>
        @endif
    </article>
@endforeach
<script data-regulatory-layout>{!! $layout !!}</script>
</body>
</html>
