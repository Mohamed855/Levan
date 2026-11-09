@php
    $inputName = 'data[' . str_replace('.', '][', $path) . ']';
    $value = old('data.' . $path, data_get($data, $path));
    $readonly = $readonly ?? false;
    $attrs = $field['attrs'] ?? [];
    $placeholder = $attrs['placeholder'] ?? null;
    unset($attrs['placeholder']);
    if ($field['type'] === 'number' && !isset($attrs['step'])) {
        $attrs['step'] = 'any';
    }
    $attributeBag = new \Illuminate\View\ComponentAttributeBag($attrs);
@endphp
@if ($field['type'] === 'select')
    @if ($readonly)
        <input type="hidden" name="{{ $inputName }}" value="{{ $value }}">
    @endif
    <x-form.controls.select :name="$inputName" :label="$field['label']" :options="$field['options']" :value="$value" :required="$field['required']" :disabled="$readonly" :attributes="$attributeBag" col="col-md-6 col-xl-4" />
@else
    <x-form.controls.input :name="$inputName" :label="$field['label']" :type="$field['type']" :value="$value" :required="$field['required']" :readonly="$readonly" :placeholder="$placeholder" :attributes="$attributeBag" col="col-md-6 col-xl-4" />
@endif
