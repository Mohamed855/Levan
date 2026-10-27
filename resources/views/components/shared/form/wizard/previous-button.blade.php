@props([
    'text' => 'السابق',
    'icon' => 'ti ti-arrow-right me-1',
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'back' => null,
])

@php
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) || $disabled === '' || $disabled === 'disabled' || $disabled === true;
    $backBool = is_bool($back) ? $back : ($back !== null ? filter_var($back, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null);

    $defaultOnClick = null;
    if (!$attributes->has('onclick') && !$isDisabled && !$href) {
        if ($backBool === true) {
            $defaultOnClick = "window.location.href = window.location.pathname.replace(/\\/create\\/?$/, '').replace(/\\/[^\\/]+\\/edit\\/?$/, '') || '/';";
        } elseif ($backBool !== false) {
            $defaultOnClick = "const c=this.closest('.content'),s=this.closest('.bs-stepper');const all=s?s.querySelectorAll('.content'):[];if(!c||!s||all[0]===c){window.location.href=window.location.pathname.replace(/\\/create\\/?$/, '').replace(/\\/[^\\/]+\\/edit\\/?$/, '') || '/';}";
        }
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['btn', 'btn-secondary', 'btn-prev']) }}>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </a>
@else
    <button type="{{ $type }}" data-wizard-prev data-wizard-back="{{ $back === true ? 'true' : 'false' }}"
        {{ $attributes->class(['btn', 'btn-secondary', 'btn-prev']) }}
        @if($defaultOnClick) onclick="{{ $defaultOnClick }}" @endif
        @disabled($isDisabled)>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </button>
@endif
