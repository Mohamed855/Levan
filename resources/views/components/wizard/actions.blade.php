@props([
    'justify' => 'between',
])

@php
    $justifyClass = match($justify) {
        'end' => 'justify-content-end',
        'start' => 'justify-content-start',
        'center' => 'justify-content-center',
        default => 'justify-content-between',
    };
@endphp

<div {{ $attributes->merge(['class' => "mt-4 d-flex {$justifyClass} align-items-center"]) }}>
    {{ $slot }}
</div>
