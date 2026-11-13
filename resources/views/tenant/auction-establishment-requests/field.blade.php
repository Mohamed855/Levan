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
    $fieldCol = $col ?? 'col-md-6';
    $isTextarea = ($field['type'] ?? '') === 'textarea'
        || in_array(basename(str_replace('.', '/', $path)), ['safekeeping', 'disclosure', 'real_rights_details', 'dispute_details', 'value_information_details', 'off_plan_details', 'contribution_details'], true);
    if ($isTextarea && !isset($col)) {
        $fieldCol = 'col-12';
    }
@endphp
@if ($field['type'] === 'select')
    @if ($readonly)
        <input type="hidden" name="{{ $inputName }}" value="{{ $value }}">
    @endif
    <x-form.controls.select :name="$inputName" :label="$field['label']" :options="$field['options']" :value="$value" :required="$field['required']" :disabled="$readonly" :attributes="$attributeBag" :col="$fieldCol" />
@elseif ($isTextarea)
    <x-form.controls.textarea :name="$inputName" :label="$field['label']" :value="$value" :required="$field['required']" :readonly="$readonly" :placeholder="$placeholder" :attributes="$attributeBag" :col="$fieldCol" rows="3" />
@else
    <x-form.controls.input :name="$inputName" :label="$field['label']" :type="$field['type']" :value="$value" :required="$field['required']" :readonly="$readonly" :placeholder="$placeholder" :attributes="$attributeBag" :col="$fieldCol" />
@endif
