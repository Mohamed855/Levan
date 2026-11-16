@props([
    'dark' => true,
    'white' => false,
])

@php
    $isDark = filter_var($dark, FILTER_VALIDATE_BOOLEAN) && !filter_var($white, FILTER_VALIDATE_BOOLEAN);
@endphp

<span {{ $attributes->class([
    'icon-saudi_riyal',
    'icon-saudi_riyal--dark' => $isDark,
    'icon-saudi_riyal--white' => !$isDark,
]) }} title="ريال سعودي" aria-label="ريال سعودي"></span>
