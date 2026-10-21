@props([
    'name',
    'label' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'inputGroup' => false,
    'wrapperId' => null,
    'wrapperStyle' => null,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-shared.form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help"
    :col="$col" :wrapper-id="$wrapperId" :wrapper-style="$wrapperStyle">
    @if ($inputGroup)
        <div class="input-group">
            <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif data-form-control
                aria-describedby="{{ $errorId }}" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
                {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
            {{ $slot }}
        </div>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif data-form-control
            aria-describedby="{{ $errorId }}" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
        {{ $slot }}
    @endif
</x-shared.form.layout.field>
