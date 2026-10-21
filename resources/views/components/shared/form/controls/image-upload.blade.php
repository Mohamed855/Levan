@props([
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/gif',
    'hint' => 'JPG? PNG ?? GIF.',
    'required' => false,
    'col' => 'col-md-6',
])

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-shared.form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :col="$col">
    <div class="d-flex align-items-start align-items-sm-center gap-4 pb-4 border-bottom">
        <img src="{{ $value ?: asset('static/brand/logos/logo.svg') }}" alt="{{ $label }}" class="d-block w-px-100 h-px-100 rounded object-fit-cover"
            data-image-preview="{{ $fieldId }}">
        <div class="button-wrapper">
            <label for="{{ $fieldId }}" class="btn btn-primary mb-2" tabindex="0">
                <span class="d-none d-sm-block">تحديد صورة</span>
                <i class="ti ti-upload d-block d-sm-none" aria-hidden="true"></i>
            </label>
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="file"
                class="d-none"
                accept="{{ $accept }}"
                data-form-control
                data-image-input="{{ $fieldId }}"
                aria-describedby="{{ $errorId }}"
                aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
                {{ $attributes }}
            >
            @if ($hint)
                <div class="text-muted small">{{ $hint }}</div>
            @endif
        </div>
    </div>
</x-shared.form.layout.field>
