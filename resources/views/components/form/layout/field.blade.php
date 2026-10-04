@props([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-sm-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->class([$col, 'is-invalid' => $hasError]) }} data-form-field data-field-name="{{ $name }}">
    @if ($label)
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    <div id="{{ $errorId }}" class="invalid-feedback d-block" data-form-error role="alert" @if (! $hasError) hidden @endif>
        {{ $errors->first($name) }}
    </div>
</div>
