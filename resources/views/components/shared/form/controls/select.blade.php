@props([
    'name',
    'label' => null,
    'options' => [],
    'enum' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'اختر خيارًا',
    'required' => false,
    'multiple' => false,
    'select2' => true,
    'help' => null,
    'col' => 'col-sm-6',
])

@php
    if ($enum) {
        $options = \App\Support\SelectOptions::fromEnum($enum);
    }
@endphp

@php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $selectedValue = old($name, $value);
    $selectedValues = collect(is_array($selectedValue) ? $selectedValue : [$selectedValue])
        ->map(static fn ($item) => (string) $item);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col">
    <select
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        @if ($multiple) multiple @endif
        data-form-control
        data-placeholder="{{ $placeholder }}"
        aria-describedby="{{ $errorId }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        {{ $attributes->class(['form-control', 'select2' => $select2, 'is-invalid' => $errors->has($name)]) }}
    >
        @if (! $multiple)
            <option value=""></option>
        @endif
        @foreach ($options as $key => $option)
            @php
                if ($enum) {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } elseif (is_object($option)) {
                    $optionValue = $option->id ?? $option->value ?? $key;
                    $optionLabel = $option->name ?? $option->label ?? $option->title ?? (string) $optionValue;
                    $optionDisabled = (bool) ($option->disabled ?? false);
                } elseif (is_array($option)) {
                    $optionValue = $option['value'] ?? $option['id'] ?? $key;
                    $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                    $optionDisabled = (bool) ($option['disabled'] ?? false);
                } elseif (is_array($options) && array_is_list($options)) {
                    $optionValue = $option;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } else {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                }
            @endphp
            <option value="{{ $optionValue }}" @selected($selectedValues->contains((string) $optionValue)) @disabled($optionDisabled)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</x-form.layout.field>
