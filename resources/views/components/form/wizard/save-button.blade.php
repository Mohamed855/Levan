@props([
    'text' => 'حفظ',
    'icon' => 'ti ti-check me-1',
    'type' => 'submit',
])

<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-primary', 'btn-submit', 'waves-effect', 'waves-light']) }}>
    @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
</button>
