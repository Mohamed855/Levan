@props([
    'id',
    'label',
    'options' => [],
    'placeholder' => 'الكل',
    'value' => null,
    'name' => null,
    'filterKey' => null,
    'table' => null,
    'target' => null,
    'col' => 'col-md-6',
    'wrapperClass' => '',
    'selectClass' => '',
    'multiple' => false,
    'disabled' => false,
    'required' => false,
    'allowClear' => true,
])

@php
    $customClass = $attributes->get('class', '');
    $hasColInClass = $customClass && preg_match('/(^|\s)col(-[a-z0-9]+)?(-\d+)?(\s|$)/', $customClass);
    $columnClass = trim(($hasColInClass ? $customClass : "$col $wrapperClass"));
    $remainingAttributes = $hasColInClass ? $attributes->except('class') : $attributes;

    $selectName = $name;
    if ($selectName && $multiple && !str_ends_with($selectName, '[]')) {
        $selectName .= '[]';
    }

    $targetTable = $table ?? $target;
    if ($targetTable && !str_starts_with($targetTable, '#') && !str_starts_with($targetTable, '.')) {
        $targetTable = '#' . $targetTable;
    }

    $filterDataAttributes = [
        'class' => trim('form-control select2 ' . $selectClass),
        'data-placeholder' => $placeholder,
        'data-allow-clear' => $allowClear ? 'true' : 'false',
        'data-datatable-filter' => 'true',
    ];

    if ($filterKey) {
        $filterDataAttributes['data-filter-key'] = $filterKey;
    }
    if ($targetTable) {
        $filterDataAttributes['data-datatable-target'] = $targetTable;
    }
@endphp

<div class="{{ $columnClass }}">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}"
        @if ($selectName) name="{{ $selectName }}" @endif
        @if ($multiple) multiple @endif
        @if ($disabled) disabled @endif
        @if ($required) required @endif
        {{ $remainingAttributes->merge($filterDataAttributes) }}>
        @if (!$multiple)
            <option value=""></option>
        @endif
        @foreach ($options as $key => $option)
            @php
                if (is_array($option)) {
                    $optionValue = $option['value'] ?? $option['id'] ?? $key;
                    $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                    $optionDisabled = !empty($option['disabled']);
                } elseif (is_object($option)) {
                    $optionValue = $option->value ?? $option->id ?? $key;
                    $optionLabel = $option->label ?? $option->name ?? $option->title ?? (string) $optionValue;
                    $optionDisabled = !empty($option->disabled);
                } else {
                    $optionValue = is_numeric($key) ? $option : $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                }

                $isSelected = is_array($value)
                    ? in_array((string) $optionValue, array_map('strval', $value), true)
                    : ($value !== null && $value !== '' && (string) $value === (string) $optionValue);
            @endphp
            <option value="{{ $optionValue }}" @selected($isSelected) @disabled($optionDisabled)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>
