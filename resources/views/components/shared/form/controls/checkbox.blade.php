@props([
    'name',
    'label',
    'id' => null,
    'checked' => false,
    'value' => '1',
    'help' => null,
    'col' => 'col-sm-6',
    'required' => false,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $isChecked = old($name, $checked);
    $isRequired = filter_var($required, FILTER_VALIDATE_BOOLEAN) || $required === true || $required === 1 || $required === '1';
    $cleanLabel = $label !== null ? trim(preg_replace(['/\s*\(?\s*(اختياري|اختباري)\s*\)?\s*/u', '/\s*\*+\s*$/u'], ['', ''], (string) $label)) : null;
@endphp

<x-form.layout.field :name="$name" :id="$fieldId" :help="$help" :col="$col">
    <div class="card border p-3 h-100">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
                <label class="form-label mb-0 fw-semibold" for="{{ $fieldId }}">
                    {{ $cleanLabel }}@if ($isRequired)<span class="text-danger" aria-hidden="true"> *</span>@endif
                </label>
                @if ($slot->isNotEmpty())
                    <div class="text-muted small mt-1">{{ $slot }}</div>
                @endif
            </div>
            <div class="form-check form-switch mb-0">
                <input
                    id="{{ $fieldId }}"
                    name="{{ $name }}"
                    type="checkbox"
                    value="{{ $value }}"
                    class="form-check-input {{ $errors->has($name) ? 'is-invalid' : '' }}"
                    @checked($isChecked)
                    data-form-control
                    aria-describedby="{{ $errorId }}"
                    aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
                    {{ $attributes }}
                >
            </div>
        </div>
    </div>
</x-form.layout.field>
