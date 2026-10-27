@props([
    'text' => 'حفظ',
    'icon' => 'ti ti-check me-1',
    'type' => 'submit',
    'loadingText' => null,
])

<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-primary', 'btn-next', 'btn-submit'])->merge([
    'data-save-button' => 'true',
    'data-loading-text' => $loadingText,
]) }}>
    <span class="spinner-border spinner-border-sm me-1 d-none btn-spinner" role="status" aria-hidden="true"></span>
    @if ($icon)
        <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
    <span class="btn-text">{{ $slot->isNotEmpty() ? $slot : $text }}</span>
</button>
