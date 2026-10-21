@props([
    'name',
    'label' => null,
    'id' => null,
    'accept' => null,
    'required' => false,
    'multiple' => false,
    'help' => null,
    'col' => 'col-md-6',
    'wrapperId' => null,
    'wrapperStyle' => null,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-shared.form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col" :wrapper-id="$wrapperId" :wrapper-style="$wrapperStyle">
    <input
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        type="file"
        @if ($multiple) multiple @endif
        @if ($accept) accept="{{ $accept }}" @endif
        data-form-control
        aria-describedby="{{ $errorId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >
    {{ $slot }}
</x-shared.form.layout.field>
