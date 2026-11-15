@props([
    'id' => null,
    'url' => null,
    'title' => 'استعادة',
    'permission' => null,
    'icon' => 'ti ti-rotate',
    'class' => 'btn-icon btn-label-primary',
])

@if (!$permission)
    <button type="button"
        @if($id !== null) data-id="{{ $id }}" @endif
        @if($url !== null) data-url="{{ $url }}" @endif
        {{ $attributes->merge(['class' => 'btn ' . $class . ' js-restore-btn js-restore', 'title' => $title, 'aria-label' => $title]) }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </button>
@else
    @can($permission)
        <button type="button"
            @if($id !== null) data-id="{{ $id }}" @endif
            @if($url !== null) data-url="{{ $url }}" @endif
            {{ $attributes->merge(['class' => 'btn ' . $class . ' js-restore-btn js-restore', 'title' => $title, 'aria-label' => $title]) }}>
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </button>
    @endcan
@endif
