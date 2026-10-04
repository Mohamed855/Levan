@props([
    'name',
    'label' => null,
    'id' => null,
    'accept' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-sm-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col">
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="file"
        @if ($accept) accept="{{ $accept }}" @endif
        @required($required)
        data-form-control
        aria-describedby="{{ $errorId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >
</x-form.layout.field>
