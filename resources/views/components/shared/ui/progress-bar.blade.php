@props([
    'value' => 0,
    'total' => 0,
    'percentage' => null,
    'color' => null,
    'height' => '6px',
    'showLabel' => true,
])

@php
    $safeTotal = max(0, (int) $total);
    $safeValue = max(0, (int) $value);
    $calcPct = $safeTotal > 0 ? min(100, round(($safeValue / $safeTotal) * 100, 2)) : 0;
    $pct = $percentage !== null ? (float) $percentage : $calcPct;

    $autoColor = match (true) {
        $pct <= 33.33 => 'bg-warning',
        $pct <= 66.66 => 'bg-info',
        default => 'bg-success',
    };

    $barColorClass = $color ? (str_starts_with($color, 'bg-') ? $color : 'bg-' . $color) : $autoColor;
@endphp

<div {{ $attributes->merge(['class' => 'd-flex flex-column align-items-center justify-content-center gap-1']) }} style="min-width: 75px; max-width: 110px; margin: 0 auto;">
    @if ($showLabel)
        <span class="small fw-semibold text-heading">{{ $safeValue }}/{{ $safeTotal }}</span>
    @endif
    <div class="progress w-100" style="height: {{ $height }};" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar {{ $barColorClass }}" style="width: {{ $pct }}%;"></div>
    </div>
</div>

