@props([
    'name',
    'label',
    'id' => null,
    'checked' => false,
    'value' => '1',
    'help' => null,
    'col' => 'col-sm-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $isChecked = old($name, $checked);
@endphp

<x-shared.form.layout.field :name="$name" :id="$fieldId" :help="$help" :col="$col">
    <div class="card border p-3 h-100">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
                <label class="form-label mb-0 fw-semibold" for="{{ $fieldId }}">{{ $label }}</label>
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
</x-shared.form.layout.field>
