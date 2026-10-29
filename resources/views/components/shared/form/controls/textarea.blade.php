@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';

    if ($placeholder === null && $label) {
        $cleanedLabel = trim(preg_replace(['/\s*\(?\s*(اختياري|اختباري)\s*\)?\s*/u', '/\s*\*+\s*$/u'], ['', ''], (string) $label));
        $cleanedLabel = trim(str_replace([':', '..'], '', $cleanedLabel));
        $placeholder = 'أدخل ' . $cleanedLabel . '...';
    }
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col">
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        data-form-control
        aria-describedby="{{ $errorId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >{{ old($name, $value) }}</textarea>
</x-form.layout.field>
