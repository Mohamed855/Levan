@props([
    'name',
    'label' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-sm-6',
    'icon' => null,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col">
    @if ($icon)
        <div class="input-group input-group-merge">
            <span class="input-group-text">
                <i class="{{ $icon }}" aria-hidden="true"></i>
            </span>
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                @required($required)
                data-form-control
                aria-describedby="{{ $errorId }}"
                aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
                {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
            >
        </div>
    @else
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @required($required)
            data-form-control
            aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
        >
    @endif
</x-form.layout.field>
