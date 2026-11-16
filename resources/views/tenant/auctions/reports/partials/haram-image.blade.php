@php
    $fit = in_array($image['fit'] ?? null, ['contain', 'cover'], true)
        ? $image['fit']
        : (in_array($image['kind'] ?? null, ['photo', 'artwork'], true) ? 'cover' : 'contain');
    $focalX = max(0, min(100, (float) ($image['focal_x'] ?? 50)));
    $focalY = max(0, min(100, (float) ($image['focal_y'] ?? 50)));
@endphp
@if (str_starts_with($image['uri'] ?? '', 'data:image/'))
    <img class="{{ $imageClass ?? '' }}" src="{{ $image['uri'] }}" alt="{{ $label }}"
        data-report-attachment="{{ $image['id'] ?? '' }}"
        style="object-fit:{{ $fit }};object-position:{{ $focalX }}% {{ $focalY }}%">
@endif
