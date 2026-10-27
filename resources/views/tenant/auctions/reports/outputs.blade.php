<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="auction-report-design" content="haram">
    <title>مخرجات المزاد - {{ $data['auction']['name'] }}</title>
    <style>
        {!! $artwork->fontCss() !!}
        @page { size: 595.5pt 842.25pt; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .auction-print-page { width: 595.5pt; height: 842.25pt; position: relative; break-after: page; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .auction-print-page:last-child { break-after: auto; }
        .auction-print-page > svg { display: block; width: 100%; height: 100%; }
    </style>
</head>
<body>
    @foreach ($pages as $page)
        @php($content = $haramData->page($data, $page))
        <section class="auction-print-page" data-page-type="{{ $page['page_type'] }}">
            {!! $artwork->render('haram', $content['index'], $content['values'], $content['tables'], $content['images'], $page['page_number']) !!}
        </section>
    @endforeach
</body>
</html>
