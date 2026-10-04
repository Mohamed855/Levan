@props([
    'text' => 'حفظ',
    'icon' => 'ti ti-check me-1',
    'type' => 'button',
])

@php
    $defaultClasses = 'btn btn-primary btn-next btn-submit waves-effect waves-light';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $defaultClasses]) }}>
    @if($icon)<i class="{{ $icon }}"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
</button>
