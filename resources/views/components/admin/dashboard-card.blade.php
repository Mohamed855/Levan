@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'primary',
    'col' => 'col-sm-4 col-lg-6 col-xxl-2',
    'valueClass' => null,
    'avatarClass' => 'rounded',
])

@php
    $bgClass = str_starts_with($color, 'bg-') ? $color : 'bg-label-' . $color;
    $iconClass = str_contains($icon ?? '', 'ti ') ? $icon : 'ti ' . ($icon ?? '') . ' ti-26px';
    $isCircle = in_array($avatarClass, ['circle', 'rounded-circle']);
@endphp

<div class="{{ $col }}">
    <div {{ $attributes->merge(['class' => 'card h-100']) }}>
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between h-100">
                <div class="content-left d-flex flex-column justify-content-between h-100">
                    <span class="text-heading">{{ $title }}</span>
                    <div class="d-flex align-items-center my-1">
                        <h4 class="mb-0 me-2 {{ $valueClass }}">{{ $value }}</h4>
                    </div>
                    @if($subtitle)
                        <small class="mb-0">{{ $subtitle }}</small>
                    @endif
                </div>
                @if($icon)
                    <div class="avatar {{ $isCircle ? 'circle' : '' }}">
                        <span class="avatar-initial {{ $isCircle ? 'rounded-circle' : 'rounded' }} {{ $bgClass }}">
                            <i class="{{ $iconClass }}"></i>
                        </span>
                    </div>
                @endif
            </div>
            @if (trim($slot))
                {{ $slot }}
            @endif
        </div>
    </div>
</div>
