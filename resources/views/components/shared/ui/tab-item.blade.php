@props([
    'active' => false,
    'color' => 'primary',
    'url' => null,
    'target' => null,
    'icon' => null,
    'label' => null,
    'count' => null,
    'badge' => null,
    'countAttributes' => [],
    'id' => null,
    'vertical' => false,
])

@php
    $displayCount = $count ?? $badge;
    $activeClass = $active ? "btn-{$color}" : "btn-outline-{$color}";
    $alignClass = $vertical ? 'justify-content-start text-start py-3' : 'justify-content-center';
    $badgeClass = $vertical ? 'notification-filter__count ui-tab__count ms-auto' : 'notification-filter__count ui-tab__count';
@endphp

@if ($url)
    <a href="{{ $url }}"
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->merge(['class' => "btn d-inline-flex align-items-center gap-1 {$alignClass} {$activeClass}"]) }}>
        @if ($icon)
            <i class="{{ $icon }}"></i>
        @endif
        @if ($label)
            <span>{{ $label }}</span>
        @endif
        {{ $slot }}
        @if ($displayCount !== null)
            <span class="{{ $badgeClass }}"
                @if (is_array($countAttributes))
                    @foreach ($countAttributes as $attrKey => $attrVal)
                        @if (is_bool($attrVal))
                            @if ($attrVal) {{ $attrKey }} @endif
                        @else
                            {{ $attrKey }}="{{ $attrVal }}"
                        @endif
                    @endforeach
                @elseif (is_string($countAttributes))
                    {{ $countAttributes }}
                @endif>
                {{ $displayCount }}
            </span>
        @endif
    </a>
@else
    <button type="button"
        @if ($id) id="{{ $id }}" @endif
        {{ $attributes->merge(['class' => "btn d-inline-flex align-items-center gap-1 {$alignClass} {$activeClass}" . ($active ? ' active' : '')]) }}
        @if ($target)
            data-bs-toggle="tab"
            data-bs-target="{{ str_starts_with($target, '#') ? $target : '#' . $target }}"
            role="tab"
            aria-controls="{{ ltrim($target, '#') }}"
            aria-selected="{{ $active ? 'true' : 'false' }}"
        @endif>
        @if ($icon)
            <i class="{{ $icon }}"></i>
        @endif
        @if ($label)
            <span>{{ $label }}</span>
        @endif
        {{ $slot }}
        @if ($displayCount !== null)
            <span class="{{ $badgeClass }}"
                @if (is_array($countAttributes))
                    @foreach ($countAttributes as $attrKey => $attrVal)
                        @if (is_bool($attrVal))
                            @if ($attrVal) {{ $attrKey }} @endif
                        @else
                            {{ $attrKey }}="{{ $attrVal }}"
                        @endif
                    @endforeach
                @elseif (is_string($countAttributes))
                    {{ $countAttributes }}
                @endif>
                {{ $displayCount }}
            </span>
        @endif
    </button>
@endif
