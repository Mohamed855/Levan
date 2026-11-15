@props([
    'src' => null,
    'alt' => '',
    'size' => 44,
    'icon' => 'ti-users',
    'rounded' => 'rounded-circle',
])

@php
    $cleanIcon = $icon ? (str_starts_with($icon, 'ti') ? (str_starts_with($icon, 'ti ') ? $icon : 'ti ' . $icon) : 'ti ti-' . $icon) : 'ti ti-users';
    $hasImage = filled($src);
@endphp

@if ($hasImage)
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        width="{{ $size }}"
        height="{{ $size }}"
        loading="lazy"
        class="{{ $rounded }} border bg-white object-fit-cover"
        onerror="this.style.display='none'; if(this.nextElementSibling){ this.nextElementSibling.classList.remove('d-none'); this.nextElementSibling.classList.add('d-inline-flex'); }"
    >
    <div
        class="{{ $rounded }} border d-none align-items-center justify-content-center bg-label-dark text-dark flex-shrink-0"
        style="width: {{ $size }}px; height: {{ $size }}px; font-size: 18px;"
        title="{{ $alt }}"
    >
        <i class="{{ $cleanIcon }}"></i>
    </div>
@else
    <div
        class="{{ $rounded }} border d-inline-flex align-items-center justify-content-center bg-label-dark text-dark flex-shrink-0"
        style="width: {{ $size }}px; height: {{ $size }}px; font-size: 18px;"
        title="{{ $alt }}"
    >
        <i class="{{ $cleanIcon }}"></i>
    </div>
@endif
