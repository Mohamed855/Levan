@props([
    'name',
    'label',
    'options' => [],
    'id' => null,
    'value' => null,
    'placeholder' => 'اختر خيارًا',
    'required' => false,
    'multiple' => false,
    'col' => 'col-sm-6',
])

@php
    $fieldId = $id ?: $name;
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $selectedValue = old($name, $value);
    $selectedValues = collect(is_array($selectedValue) ? $selectedValue : [$selectedValue])->map(fn ($item) => (string) $item);
@endphp

<div class="{{ $col }}">
    <label class="form-label" for="{{ $fieldId }}">
        {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
    </label>

    <select id="{{ $fieldId }}" name="{{ $fieldName }}" @if ($multiple) multiple @endif @required($required)
        {{ $attributes->class(['form-control select2', 'is-invalid' => $errors->has($name)])->merge(['data-placeholder' => $placeholder]) }}>
        @if (! $multiple)
            <option value=""></option>
        @endif
        @foreach ($options as $key => $option)
            @php
                $optionValue = is_object($option) ? ($option->id ?? $option->value ?? $key) : (is_array($option) ? ($option['value'] ?? $option['id'] ?? $key) : (is_numeric($key) ? $option : $key));
                $optionLabel = is_object($option) ? ($option->name ?? $option->label ?? $optionValue) : (is_array($option) ? ($option['label'] ?? $option['name'] ?? $optionValue) : $option);
            @endphp
            <option value="{{ $optionValue }}" @selected($selectedValues->contains((string) $optionValue))>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
