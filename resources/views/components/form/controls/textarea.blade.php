@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-sm-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col">
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @required($required)
        data-form-control
        aria-describedby="{{ $errorId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >{{ old($name, $value) }}</textarea>
</x-form.layout.field>
