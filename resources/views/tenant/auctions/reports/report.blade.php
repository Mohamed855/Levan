<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="auction-report-design" content="asir">
    <title>تقرير المزاد - {{ $data['auction']['name'] }}</title>
    <style>
        {!! $artwork->fontCss() !!}
        @page { size: 595.276pt 841.890pt; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .auction-print-page { width: 595.276pt; height: 841.890pt; position: relative; break-after: page; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .auction-print-page:last-child { break-after: auto; }
        .auction-print-page > svg { display: block; width: 100%; height: 100%; }
    </style>
</head>
<body>
    @foreach ($pages as $page)
        @php($content = $asirData->page($data, $page))
        <section class="auction-print-page" data-page-type="{{ $page['page_type'] }}">
            {!! $artwork->render('asir', $content['index'], $content['values'], $content['tables'], $content['images'], $page['page_number']) !!}
        </section>
    @endforeach
</body>
</html>
