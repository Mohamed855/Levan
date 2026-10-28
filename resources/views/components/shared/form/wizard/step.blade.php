@props([
    'id',
    'fields' => [],
])

<div id="{{ $id }}" class="content active" data-form-step data-validation-fields="{{ e(json_encode(array_values($fields))) }}">
    {{ $slot }}
</div>
