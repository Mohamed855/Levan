@props([
    'items' => [],
    'color' => 'primary',
    'size' => null, // e.g. 'sm', 'lg'
    'vertical' => false,
    'ariaLabel' => 'التبويبات',
    'groupClass' => null,
])

@php
    $sizeClass = $size ? "btn-group-{$size}" : '';
    $defaultGroupClass = $vertical ? 'w-100 gap-2' : '';
    $containerClass = $vertical
        ? "btn-group-vertical {$sizeClass} " . ($groupClass ?? $defaultGroupClass)
        : "btn-group {$sizeClass} " . ($groupClass ?? '');
    $alignClass = $vertical ? 'justify-content-start text-start py-3 px-4' : 'justify-content-center';
    $badgeClass = $vertical ? 'notification-filter__count ui-tab__count ms-auto' : 'notification-filter__count ui-tab__count';
@endphp

<div {{ $attributes->merge(['class' => trim($containerClass), 'role' => 'group', 'aria-label' => $ariaLabel]) }}>
    @if (!empty($items))
        @foreach ($items as $item)
            @php
                $isActive = (bool) ($item['active'] ?? false);
                $itemColor = $item['color'] ?? $color;
                $activeClass = $isActive ? "btn-{$itemColor}" : "btn-outline-{$itemColor}";
                $target = $item['target'] ?? null;
                $url = $item['url'] ?? null;
                $icon = $item['icon'] ?? null;
                $label = $item['label'] ?? '';
                $count = $item['count'] ?? null;
                $badge = $item['badge'] ?? null;
                $displayCount = $count ?? $badge;
                $countAttributes = $item['countAttributes'] ?? $item['countAttr'] ?? [];
                $customItemClass = $item['class'] ?? '';
                $itemId = $item['id'] ?? null;
            @endphp

            @if ($url)
                <a href="{{ $url }}"
                    @if ($itemId) id="{{ $itemId }}" @endif
                    class="btn d-inline-flex align-items-center gap-1 {{ $alignClass }} {{ $activeClass }} {{ $customItemClass }}"
                    @if (!empty($item['attributes']) && is_array($item['attributes']))
                        @foreach ($item['attributes'] as $attrKey => $attrVal)
                            {{ $attrKey }}="{{ $attrVal }}"
                        @endforeach
                    @endif>
                    @if ($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    <span>{{ $label }}</span>
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
                    @if ($itemId) id="{{ $itemId }}" @endif
                    class="btn d-inline-flex align-items-center gap-1 {{ $alignClass }} {{ $activeClass }} {{ $isActive ? 'active' : '' }} {{ $customItemClass }}"
                    @if ($target)
                        data-bs-toggle="tab"
                        data-bs-target="{{ str_starts_with($target, '#') ? $target : '#' . $target }}"
                        role="tab"
                        aria-controls="{{ ltrim($target, '#') }}"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    @endif
                    @if (!empty($item['attributes']) && is_array($item['attributes']))
                        @foreach ($item['attributes'] as $attrKey => $attrVal)
                            {{ $attrKey }}="{{ $attrVal }}"
                        @endforeach
                    @endif>
                    @if ($icon)
                        <i class="{{ $icon }}"></i>
                    @endif
                    <span>{{ $label }}</span>
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
        @endforeach
    @endif

    {{ $slot }}
</div>
