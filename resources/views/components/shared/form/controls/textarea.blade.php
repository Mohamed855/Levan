@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'maxlength' => 1000,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $counterId = str_replace(['.', '[', ']'], '-', $fieldId) . '-counter';
    $isRequired = filter_var($required, FILTER_VALIDATE_BOOLEAN);

    $rawMaxlength = $attributes->get('maxlength', $maxlength);
    $requestedMaxlength = is_numeric($rawMaxlength) ? (int) $rawMaxlength : 1000;
    $resolvedMaxlength = $requestedMaxlength > 0 ? min($requestedMaxlength, 1000) : 1000;

    $resolvedValue = old($name, $value);
    $resolvedValueString = is_scalar($resolvedValue) ? (string) $resolvedValue : '';
    $initialLength = mb_strlen(str_replace(["\r\n", "\r"], "\n", $resolvedValueString));

    $attributes = $attributes->except('maxlength')->merge([
        'aria-required' => $isRequired ? 'true' : 'false',
    ]);

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
        maxlength="{{ $resolvedMaxlength }}"
        @required($isRequired)
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        data-form-control
        data-textarea-counter="{{ $counterId }}"
        data-textarea-maxlength="{{ $resolvedMaxlength }}"
        aria-describedby="{{ $errorId }} {{ $counterId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >{{ $resolvedValueString }}</textarea>
    <div class="d-flex justify-content-end mt-1">
        <small
            id="{{ $counterId }}"
            class="form-text m-0 textarea-char-counter {{ $initialLength >= $resolvedMaxlength ? 'text-danger fw-medium' : 'text-muted' }}"
            data-textarea-counter-display
            dir="ltr"
            aria-live="polite"
        >{{ $initialLength }}/{{ $resolvedMaxlength }}</small>
    </div>
</x-form.layout.field>
