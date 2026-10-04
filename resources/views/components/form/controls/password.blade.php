@props([
    'name' => 'password',
    'label' => 'كلمة المرور',
    'id' => null,
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
    <div class="input-group input-group-merge">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="password"
            autocomplete="new-password"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @required($required)
            data-form-control
            aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
        >
        <button type="button" class="input-group-text cursor-pointer" data-password-toggle="{{ $fieldId }}" aria-label="إظهار كلمة المرور">
            <i class="ti ti-eye-off" aria-hidden="true"></i>
        </button>
    </div>
</x-form.layout.field>
