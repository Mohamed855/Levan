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
            {!! $artwork->render($design, $content['index'], $content['values'], $content['tables'], $content['images'], $page['page_number'], $content['table_layouts'] ?? []) !!}
        </section>
    @endforeach
</body>
</html>
