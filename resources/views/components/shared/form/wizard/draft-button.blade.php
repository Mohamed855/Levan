@props([
    'text' => 'حفظ كمسودة',
    'icon' => 'ti ti-device-floppy me-1',
    'step' => null,
    'type' => 'button',
])

<button type="{{ $type }}" data-wizard-draft data-save-draft @if($step !== null) data-step="{{ $step }}" @endif {{ $attributes->class(['btn', 'btn-secondary', 'btn-save-draft', 'waves-effect', 'waves-light']) }}>
    @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    {{ $slot->isNotEmpty() ? $slot : $text }}
</button>
