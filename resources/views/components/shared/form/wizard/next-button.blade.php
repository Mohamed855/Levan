@props([
    'text' => 'التالي',
    'icon' => 'ti ti-arrow-right ms-2',
    'iconPosition' => 'end',
    'disabled' => true,
    'type' => 'button',
])

<button type="{{ $type }}" data-wizard-next {{ $attributes->class(['btn', 'btn-primary', 'btn-next', 'waves-effect', 'waves-light']) }} @disabled($disabled)>
    @if ($icon && $iconPosition === 'start')<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
    @if ($icon && $iconPosition === 'end')<i class="{{ $icon }}" aria-hidden="true"></i>@endif
</button>
