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

    if ($placeholder === null) {
        if ($type === 'date') {
            $placeholder = 'اختر التاريخ';
        } elseif ($type === 'datetime-local') {
            $placeholder = 'اختر التاريخ والوقت';
        } elseif ($type === 'time') {
            $placeholder = 'اختر الوقت';
        } elseif ($type === 'email') {
            $placeholder = 'example@domain.com';
        } elseif ($type === 'password') {
            $placeholder = '············';
        } elseif ($label) {
            $cleanedLabel = trim(preg_replace(['/\s*\(?\s*(اختياري|اختباري)\s*\)?\s*/u', '/\s*\*+\s*$/u'], ['', ''], (string) $label));
            $cleanedLabel = trim(str_replace([':', '..'], '', $cleanedLabel));
            $placeholder = 'أدخل ' . $cleanedLabel;
        }
    }

    $errorsBag = $errors ?? view()->shared('errors') ?? new \Illuminate\Support\ViewErrorBag;
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help"
    :col="$col" :wrapper-id="$wrapperId" :wrapper-style="$wrapperStyle">
    @if ($inputGroup)
        <div @class([
            'input-group input-group-merge',
            'is-invalid' => $errorsBag->has($name),
        ])>
            <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif data-form-control
                aria-describedby="{{ $errorId }}" aria-invalid="{{ $errorsBag->has($name) ? 'true' : 'false' }}"
                {{ $attributes->class(['form-control', 'is-invalid' => $errorsBag->has($name)]) }}>
            {{ $slot }}
        </div>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif data-form-control
            aria-describedby="{{ $errorId }}" aria-invalid="{{ $errorsBag->has($name) ? 'true' : 'false' }}"
            {{ $attributes->class(['form-control', 'is-invalid' => $errorsBag->has($name)]) }}>
        {{ $slot }}
    @endif
</x-form.layout.field>
