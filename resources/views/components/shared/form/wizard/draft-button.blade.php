@props([
    'text' => 'حفظ كمسودة',
    'icon' => 'ti ti-device-floppy me-1',
    'step' => null,
    'type' => 'button',
    'loadingText' => 'جاري حفظ المسودة...',
])

<button type="{{ $type }}" data-wizard-draft data-save-draft data-loading-text="{{ $loadingText }}" @if($step !== null) data-step="{{ $step }}" @endif {{ $attributes->class(['btn', 'btn-secondary', 'btn-save-draft']) }}>
    <span class="spinner-border spinner-border-sm me-1 d-none btn-spinner" role="status" aria-hidden="true"></span>
    @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    <span class="btn-text">{{ $slot->isNotEmpty() ? $slot : $text }}</span>
</button>
