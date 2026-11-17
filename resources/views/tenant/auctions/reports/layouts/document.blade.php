<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="auction-report-design" content="{{ $design }}">
    <title>{{ $title }} - {{ $data['auction']['name'] }}</title>
    @include('tenant.auctions.reports.partials.styles')
    @if ($design === 'haram')
        @include('tenant.auctions.reports.partials.haram-styles')
        @include('tenant.auctions.reports.partials.haram-index-styles')
        @include('tenant.auctions.reports.partials.haram-content-styles')
    @endif
    @if ($design === 'asir')
        @include('tenant.auctions.reports.partials.asir-styles')
    @endif
</head>
<body>
    @foreach ($pages as $page)
        @php($content = $page['content'])
        <section class="auction-print-page {{ isset($content['native_template']) ? $design.'-native-page '.$design.'-native-'.$content['native_template'] : '' }}" data-page-type="{{ $page['page_type'] }}" @if ($design === 'asir' && isset($content['native_template'])) data-asir-index="{{ $content['index'] }}" @endif>
            @if (isset($content['native_template']))
                @if ($design === 'asir')
                    @if ($content['background'])<img class="asir-landscape" src="{{ $content['background'] }}" alt="">@endif
                    <div class="asir-rear-panel" aria-hidden="true"></div>
                    <div class="asir-panel-outline" aria-hidden="true"></div>
                @endif
                @include('tenant.auctions.reports.partials.'.$design.'-'.$content['native_template'])
            @else
            {!! $artwork->render($design, $content['index'], $content['values'], $content['tables'], $content['images'], $page['page_number'], $content['table_layouts'] ?? [], $content['field_layouts'] ?? [], $content['artwork_overrides'] ?? []) !!}
            @if ($design === 'haram')
                @include('tenant.auctions.reports.partials.haram-overlays')
            @endif
            @foreach ($content['links'] ?? [] as $role => $link)
                @php([$left, $top, $right, $bottom] = $link['box'])
                <a class="auction-report-link" data-link-role="{{ $role }}" href="{{ $link['url'] }}" aria-label="{{ $role }}" style="left:{{ $left }}pt;top:{{ $top }}pt;width:{{ $right - $left }}pt;height:{{ $bottom - $top }}pt"></a>
            @endforeach
            @endif
        </section>
    @endforeach
    @if ($design === 'haram')
        <script data-haram-layout>{!! file_get_contents(resource_path('auction-reports/haram/layout.js')) !!}</script>
    @endif
    @if ($design === 'asir')
        <script data-asir-layout>{!! file_get_contents(resource_path('auction-reports/asir/layout.js')) !!}</script>
    @endif
</body>
</html>
