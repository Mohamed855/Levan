@props([
    'label',
    'tone' => 'secondary',
    'pill' => false,
])

<span {{ $attributes->class(['badge', 'bg-label-' . $tone, 'rounded-pill' => $pill]) }}>{{ $label }}</span>
