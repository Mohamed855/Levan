@props([
    'url',
    'title' => 'عرض التفاصيل',
    'permission' => null,
    'icon' => 'ti ti-eye',
    'class' => 'btn-icon btn-label-primary',
])

@if (!$permission)
    <a href="{{ $url }}"
        {{ $attributes->merge(['class' => 'btn ' . $class, 'title' => $title, 'aria-label' => $title]) }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </a>
@else
    @can($permission)
        <a href="{{ $url }}"
            {{ $attributes->merge(['class' => 'btn ' . $class, 'title' => $title, 'aria-label' => $title]) }}>
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </a>
    @endcan
@endif
