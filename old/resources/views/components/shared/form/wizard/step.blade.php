@props([
    'id',
    'fields' => [],
])

<div id="{{ $id }}" class="content" data-form-step data-validation-fields="{{ e(json_encode(array_values($fields))) }}">
    {{ $slot }}
</div>
