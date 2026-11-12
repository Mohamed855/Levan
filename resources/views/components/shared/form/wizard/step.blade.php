@props([
    'id',
    'fields' => [],
    'active' => false,
])

<div id="{{ $id }}" class="content{{ $active ? ' active' : '' }}" data-form-step data-validation-fields="{{ json_encode(array_values($fields)) }}">
    {{ $slot }}
</div>
