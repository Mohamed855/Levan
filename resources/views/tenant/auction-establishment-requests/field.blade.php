@php
    $inputName = 'data[' . str_replace('.', '][', $path) . ']';
    $value = old('data.' . $path, data_get($data, $path));
    $readonly = $readonly ?? false;
@endphp
@if ($field['type'] === 'select')
    @if ($readonly)
        <input type="hidden" name="{{ $inputName }}" value="{{ $value }}">
    @endif
    <x-form.controls.select :name="$inputName" :label="$field['label']" :options="$field['options']" :value="$value" :required="$field['required']" :disabled="$readonly" col="col-md-6 col-xl-4" />
@else
    <x-form.controls.input :name="$inputName" :label="$field['label']" :type="$field['type']" :value="$value" :required="$field['required']" :readonly="$readonly" :step="$field['type'] === 'number' ? 'any' : null" col="col-md-6 col-xl-4" />
@endif
