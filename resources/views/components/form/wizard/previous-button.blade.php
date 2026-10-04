@props([
    'text' => 'السابق',
    'icon' => 'ti ti-arrow-left me-1',
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'back' => null,
])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['btn', 'btn-secondary', 'btn-prev', 'waves-effect', 'waves-light']) }}>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </a>
@else
    <button type="{{ $type }}" data-wizard-prev data-wizard-back="{{ $back === true ? 'true' : 'false' }}"
        {{ $attributes->class(['btn', 'btn-secondary', 'btn-prev', 'waves-effect', 'waves-light']) }} @disabled($disabled)>
        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot->isNotEmpty() ? $slot : $text }}
    </button>
@endif
