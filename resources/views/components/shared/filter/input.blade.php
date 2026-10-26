@props([
    'id',
    'label',
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'name' => null,
    'filterKey' => null,
    'table' => null,
    'target' => null,
    'col' => 'col-md-6 col-lg-3',
    'wrapperClass' => '',
    'inputClass' => '',
    'disabled' => false,
    'required' => false,
])

@php
    $customClass = $attributes->get('class', '');
    $hasColInClass = $customClass && preg_match('/(^|\s)col(-[a-z0-9]+)?(-\d+)?(\s|$)/', $customClass);
    $columnClass = trim(($hasColInClass ? $customClass : "$col $wrapperClass"));
    $remainingAttributes = $hasColInClass ? $attributes->except('class') : $attributes;

    $targetTable = $table ?? $target;
    if ($targetTable && !str_starts_with($targetTable, '#') && !str_starts_with($targetTable, '.')) {
        $targetTable = '#' . $targetTable;
    }

    $filterDataAttributes = [
        'class' => trim('form-control ' . $inputClass),
        'data-datatable-filter' => 'true',
    ];

    if ($filterKey) {
        $filterDataAttributes['data-filter-key'] = $filterKey;
    }
    if ($targetTable) {
        $filterDataAttributes['data-datatable-target'] = $targetTable;
    }
    if ($placeholder) {
        $filterDataAttributes['placeholder'] = $placeholder;
    }
@endphp

<div class="{{ $columnClass }}">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <input type="{{ $type }}"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($disabled) disabled @endif
        @if ($required) required @endif
        {{ $remainingAttributes->merge($filterDataAttributes) }} />
</div>
