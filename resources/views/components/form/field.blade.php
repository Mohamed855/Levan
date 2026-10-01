@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-sm-6',
    'as' => 'input',
])

@php
    $fieldId = $id ?: $name;
    $fieldValue = old($name, $value);
    $isTextarea = $as === 'textarea';
@endphp

<div class="{{ $col }}">
    <label class="form-label" for="{{ $fieldId }}">
        {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
    </label>

    @if ($isTextarea)
        <textarea id="{{ $fieldId }}" name="{{ $name }}" @required($required)
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ $fieldValue }}</textarea>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $fieldValue }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
