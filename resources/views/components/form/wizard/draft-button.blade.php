@props([
    'text' => 'حفظ كمسودة',
    'icon' => 'ti ti-device-floppy me-1',
])

<button type="button" data-wizard-draft {{ $attributes->class(['btn', 'btn-secondary', 'waves-effect', 'waves-light']) }}>
    @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
</button>
