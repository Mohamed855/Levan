<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="auction-report-design" content="{{ $design }}">
    <title>{{ $title }} - {{ $data['auction']['name'] }}</title>
    @include('tenant.auctions.reports.partials.styles')
</head>
<body>
    @foreach ($pages as $page)
        @php($content = $page['content'])
        <section class="auction-print-page" data-page-type="{{ $page['page_type'] }}">
            {!! $artwork->render($design, $content['index'], $content['values'], $content['tables'], $content['images'], $page['page_number'], $content['table_layouts'] ?? [], $content['field_layouts'] ?? [], $content['artwork_overrides'] ?? []) !!}
            @foreach ($content['links'] ?? [] as $role => $link)
                @php([$left, $top, $right, $bottom] = $link['box'])
                <a class="auction-report-link" data-link-role="{{ $role }}" href="{{ $link['url'] }}" aria-label="{{ $role }}" style="left:{{ $left }}pt;top:{{ $top }}pt;width:{{ $right - $left }}pt;height:{{ $bottom - $top }}pt"></a>
            @endforeach
        </section>
    @endforeach
</body>
</html>
