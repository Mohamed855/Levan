@props([
    'title' => null,
    'label' => null,
    'value' => 0,
    'subtitle' => null,
    'icon' => 'ti-chart-bar',
    'color' => null,
    'tone' => null,
    'col' => null,
    'valueClass' => null,
    'avatarClass' => 'rounded',
])

@php
    $displayTitle = $title ?? $label ?? '';
    $themeColor = $color ?? $tone ?? 'primary';
    $bgClass = str_starts_with($themeColor, 'bg-') ? $themeColor : 'bg-label-' . $themeColor;
    $iconClass = str_contains($icon ?? '', 'ti ') ? $icon : 'ti ' . ($icon ?? 'ti-chart-bar') . ' ti-26px';
    $isCircle = in_array($avatarClass, ['circle', 'rounded-circle'], true);
    $formattedValue = is_numeric($value) ? number_format((float) $value) : $value;
@endphp

@if ($col)
<div class="{{ $col }}">
@endif
    <div {{ $attributes->merge(['class' => 'card h-100']) }}>
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between h-100">
                <div class="content-left d-flex flex-column justify-content-between h-100">
                    <span class="text-heading">{{ $displayTitle }}</span>
                    <div class="d-flex align-items-center my-1">
                        <h4 class="mb-0 me-2 {{ $valueClass }}">{{ $formattedValue }}</h4>
                    </div>
                    @if ($subtitle)
                        <small class="mb-0 text-muted">{{ $subtitle }}</small>
                    @endif
                </div>
                @if ($icon)
                    <div class="avatar {{ $isCircle ? 'circle' : '' }}">
                        <span class="avatar-initial {{ $isCircle ? 'rounded-circle' : 'rounded' }} {{ $bgClass }}">
                            <i class="{{ $iconClass }}" aria-hidden="true"></i>
                        </span>
                    </div>
                @endif
            </div>
            @if (trim($slot))
                {{ $slot }}
            @endif
        </div>
    </div>
@if ($col)
</div>
@endif
