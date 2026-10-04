@props([
    'text' => 'حفظ كمسودة',
    'icon' => 'ti ti-device-floppy me-1',
    'step' => null,
    'type' => 'button',
])

@php
    $defaultClasses = 'btn btn-secondary btn-save-draft waves-effect waves-light';
@endphp

<button type="{{ $type }}" @if($step !== null) data-step="{{ $step }}" @endif {{ $attributes->merge(['class' => $defaultClasses]) }}>
    @if($icon)<i class="{{ $icon }}"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
</button>
