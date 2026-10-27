@props([
    'text' => 'التالي',
    'icon' => 'ti ti-arrow-left ms-2',
    'iconPosition' => 'end',
    'disabled' => false,
    'type' => 'button',
    'loadingText' => null,
])

<button type="{{ $type }}" data-wizard-next @if($loadingText) data-loading-text="{{ $loadingText }}" @endif {{ $attributes->class(['btn', 'btn-primary', 'btn-next']) }}>
    @if ($icon && $iconPosition === 'start')
        <span class="spinner-border spinner-border-sm me-1 d-none btn-spinner" role="status" aria-hidden="true"></span>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    <span class="btn-text">{{ $slot->isNotEmpty() ? $slot : $text }}</span>
    @if ($icon && $iconPosition === 'end')
        <span class="spinner-border spinner-border-sm ms-2 d-none btn-spinner" role="status" aria-hidden="true"></span>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @elseif (!$icon)
        <span class="spinner-border spinner-border-sm ms-2 d-none btn-spinner" role="status" aria-hidden="true"></span>
    @endif
</button>
