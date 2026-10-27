@props([
    'text' => 'التالي',
    'icon' => 'ti ti-arrow-left ms-2',
    'iconPosition' => 'end',
    'disabled' => false,
    'type' => 'button',
])

<button type="{{ $type }}" data-wizard-next {{ $attributes->class(['btn', 'btn-primary', 'btn-next']) }}>
    @if ($icon && $iconPosition === 'start')
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
    @if ($icon && $iconPosition === 'end')
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
</button>
