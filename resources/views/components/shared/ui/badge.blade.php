@props([
    'variant' => 'primary',
    'pill' => false,
    'outline' => false,
    'solid' => false,
    'size' => null,
    'icon' => null,
    'href' => null,
    'dot' => false,
])

@php
    $normalizedVariant = str_replace(['bg-label-', 'bg-', 'badge-', 'text-bg-'], '', (string) $variant);
    $normalizedVariant = $normalizedVariant ?: 'primary';

    $classes = ['badge'];

    if ($outline) {
        $classes[] = 'badge-outline-' . $normalizedVariant;
    } elseif ($solid) {
        $classes[] = 'badge-solid-' . $normalizedVariant;
    } else {
        $classes[] = 'bg-' . $normalizedVariant;
    }

    if ($pill) {
        $classes[] = 'rounded-pill';
    }

    if ($size === 'sm') {
        $classes[] = 'badge-sm';
    } elseif ($size === 'lg') {
        $classes[] = 'badge-lg';
    }

    $tag = $href ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => implode(' ', $classes)]) }}
>
    @if ($dot)
        <span class="badge-dot-indicator" aria-hidden="true"></span>
    @endif
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
