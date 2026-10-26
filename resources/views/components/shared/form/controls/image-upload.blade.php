@props([
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/webp',
    'hint' => null,
    'help' => null,
    'required' => false,
    'col' => 'col-md-6',
    'title' => 'انقر أو اسحب الصورة هنا',
    'desc' => null,
    'maxSize' => 5,
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
    :col="$col"
    :title="$title"
    :desc="$desc"
    :max-size="$maxSize"
    :compact="$compact"
    {{ $attributes }}
>
    {{ $slot }}
</x-form.controls.file>
