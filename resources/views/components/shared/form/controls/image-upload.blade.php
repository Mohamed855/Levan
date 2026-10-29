@props([
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/webp',
    'hint' => null,
    'help' => null,
    'required' => false,
    'multiple' => false,
    'col' => 'col-md-6',
    'title' => null,
    'desc' => null,
    'maxSize' => 10,
    'compact' => false,
])

<x-form.controls.file
    :name="$name"
    :label="$label"
    :id="$id"
    :value="$value"
    :accept="$accept"
    :help="$help ?? $hint"
    :required="$required"
    :multiple="$multiple"
    :col="$col"
    :title="$title"
    :desc="$desc"
    :max-size="$maxSize"
    :compact="$compact"
    {{ $attributes }}
>
    {{ $slot }}
</x-form.controls.file>
