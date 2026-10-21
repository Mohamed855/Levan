@props([
    'src' => null,
    'alt' => '',
    'size' => 44,
])

@php
    $platformLogo = asset('static/brand/logos/logo.svg');
    $image = filled($src) ? $src : $platformLogo;
@endphp

<img
    src="{{ $image }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
    class="rounded-circle border bg-white object-fit-cover"
    onerror="this.onerror=null;this.src='{{ $platformLogo }}';"
>
