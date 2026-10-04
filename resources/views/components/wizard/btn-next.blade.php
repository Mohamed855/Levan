@props([
    'text' => 'التالي',
    'icon' => 'ti ti-arrow-right ms-2',
    'iconPosition' => 'end',
    'disabled' => false,
    'type' => 'button',
])

@php
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) || $disabled === '' || $disabled === 'disabled' || $disabled === true;
    $defaultClasses = 'btn btn-primary btn-next waves-effect waves-light';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $defaultClasses]) }} @if($isDisabled) disabled @endif>
    @if($icon && $iconPosition === 'start')<i class="{{ $icon }}"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
    @if($icon && $iconPosition === 'end')<i class="{{ $icon }}"></i>@endif
</button>
