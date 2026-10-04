@props([
    'text' => 'السابق',
    'icon' => 'ti ti-arrow-left me-1',
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'back' => null,
])

@php
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) || $disabled === '' || $disabled === 'disabled' || $disabled === true;
    $defaultClasses = 'btn btn-secondary btn-prev waves-effect waves-light';

    $backBool = is_bool($back) ? $back : ($back !== null ? filter_var($back, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null);

    $defaultOnClick = null;
    if (!$attributes->has('onclick') && !$isDisabled) {
        if ($backBool === true) {
            $defaultOnClick = 'window.history.back();';
        } elseif ($backBool !== false) {
            $defaultOnClick = "const c=this.closest('.content'),s=this.closest('.bs-stepper');const all=s?s.querySelectorAll('.content'):[];if(!c||!s||all[0]===c){window.history.back();}";
        }
    }
@endphp

@if(!empty($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $defaultClasses]) }}>
        @if($icon)<i class="{{ $icon }}"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $defaultClasses, 'onclick' => $defaultOnClick]) }} @if($isDisabled) disabled @endif>
        @if($icon)<i class="{{ $icon }}"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </button>
@endif
