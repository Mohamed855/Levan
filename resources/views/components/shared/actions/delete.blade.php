@props([
    'id',
    'url',
    'title' => 'حذف',
    'permission' => null,
    'icon' => 'ti ti-trash',
    'class' => 'btn-icon btn-primary bg-primary',
])

@if (! $permission)
    <button type="button" data-id="{{ $id }}"
        {{ $attributes->merge(['class' => 'btn ' . $class . ' js-delete-btn', 'title' => $title, 'aria-label' => $title]) }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </button>

    <form id="delete-form-{{ $id }}" action="{{ $url }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@else
    @can($permission)
        <button type="button" data-id="{{ $id }}"
            {{ $attributes->merge(['class' => 'btn ' . $class . ' js-delete-btn', 'title' => $title, 'aria-label' => $title]) }}>
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </button>

        <form id="delete-form-{{ $id }}" action="{{ $url }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endcan
@endif
