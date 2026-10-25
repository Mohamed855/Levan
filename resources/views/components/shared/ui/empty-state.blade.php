@props([
    'icon' => 'ti ti-folder-off',
    'title' => 'لا توجد بيانات متاحة',
    'description' => null,
    'actionText' => null,
    'actionUrl' => null,
    'actionModal' => null,
    'actionIcon' => 'ti ti-plus',
    'actionColor' => 'primary',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-state-icon">
        @if (str_starts_with($icon, 'ti ') || str_starts_with($icon, 'bx ') || str_starts_with($icon, 'fa'))
            <i class="{{ $icon }}"></i>
        @else
            {!! $icon !!}
        @endif
    </div>

    @if ($title)
        <h5 class="empty-state-title">{{ $title }}</h5>
    @endif

    @if ($description)
        <p class="empty-state-description">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="empty-state-action">
            {{ $slot }}
        </div>
    @elseif ($actionText)
        <div class="empty-state-action">
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="btn btn-{{ $actionColor }}">
                    @if ($actionIcon)
                        <i class="{{ $actionIcon }} me-1"></i>
                    @endif
                    {{ $actionText }}
                </a>
            @elseif ($actionModal)
                <button type="button" class="btn btn-{{ $actionColor }}" data-bs-toggle="modal" data-bs-target="{{ $actionModal }}">
                    @if ($actionIcon)
                        <i class="{{ $actionIcon }} me-1"></i>
                    @endif
                    {{ $actionText }}
                </button>
            @endif
        </div>
    @endif
</div>
