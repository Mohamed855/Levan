<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: dejavusans, sans-serif; direction: rtl; }
        .page { position: relative; overflow: hidden; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .element { position: absolute; overflow: hidden; box-sizing: border-box; padding: 4px; }
        .element img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>
@php
    $schema = $output['template']['schema'];
    $canvas = $schema['canvas'];
@endphp
@foreach($schema['pages'] as $page)
    <div class="page" style="width: {{ $canvas['width'] }}px; height: {{ $canvas['height'] }}px; background-color: {{ $page['background']['color'] ?? '#ffffff' }}; @if(!empty($page['background']['image'])) background-image: url('{{ $page['background']['image'] }}'); background-size: cover; @endif">
        @foreach($page['elements'] as $element)
            @php
                $style = $element['style'] ?? [];
                $value = $element['type'] === 'text'
                    ? ($element['content'] ?? '')
                    : ($output['bindings'][$element['binding'] ?? ''] ?? '');
            @endphp
            <div class="element" style="left: {{ $element['x'] }}px; top: {{ $element['y'] }}px; width: {{ $element['width'] }}px; height: {{ $element['height'] }}px; color: {{ $style['color'] ?? '#173f35' }}; background-color: {{ $style['background_color'] ?? 'transparent' }}; font-size: {{ $style['font_size'] ?? 18 }}px; font-weight: {{ $style['font_weight'] ?? 'normal' }}; text-align: {{ $style['text_align'] ?? 'right' }}; border-radius: {{ $style['border_radius'] ?? 0 }}px;">
                @if($element['type'] === 'image' && filled($value))
                    <img src="{{ $value }}" alt="">
                @elseif($element['type'] !== 'image')
                    {{ $value }}
                @endif
            </div>
        @endforeach
    </div>
@endforeach
</body>
</html>
