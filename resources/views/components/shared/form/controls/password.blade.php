@props([
    'name' => 'password',
    'label' => 'كلمة المرور',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'autocomplete' => 'new-password',
    'icon' => null,
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-shared.form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help"
    :col="$col">
    <div @class([
        'input-group input-group-merge',
        'is-invalid' => $errors->has($name),
    ])>
        <input id="{{ $fieldId }}" name="{{ $name }}" type="password" autocomplete="{{ $autocomplete }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
            data-form-control aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
        <button type="button" @class([
            'input-group-text cursor-pointer',
            'is-invalid' => $errors->has($name),
        ]) data-password-toggle="{{ $fieldId }}"
            aria-label="إظهار كلمة المرور">
            <i class="ti ti-eye-off" aria-hidden="true"></i>
        </button>
    </div>
</x-shared.form.layout.field>
