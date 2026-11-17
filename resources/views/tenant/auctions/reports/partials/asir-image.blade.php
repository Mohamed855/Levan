@php
    $fit = in_array($image['fit'] ?? null, ['contain', 'cover'], true) ? $image['fit'] : (in_array($image['kind'] ?? null, ['photo', 'artwork'], true) ? 'cover' : 'contain');
    $focalX = max(0, min(100, (float) ($image['focal_x'] ?? 50)));
    $focalY = max(0, min(100, (float) ($image['focal_y'] ?? 50)));
    $caption = (string) ($captionOverride ?? ($image['display_name'] ?? ''));
    if (!isset($captionOverride) && isset($image['document_page'])) $caption .= ' - '.(int) $image['document_page'];
@endphp
<figure class="{{ $frameClass ?? 'asir-evidence-frame' }}" data-asir-item>
    @if (str_starts_with($image['uri'] ?? '', 'data:image/'))
        <img src="{{ $image['uri'] }}" alt="{{ $caption }}" data-report-attachment="{{ $image['id'] ?? '' }}" style="object-fit:{{ $fit }};object-position:{{ $focalX }}% {{ $focalY }}%">
    @endif
    @if ($caption !== '' && ($showCaption ?? isset($image['document_page'])))
        <figcaption>{{ $caption }}</figcaption>
    @endif
</figure>
