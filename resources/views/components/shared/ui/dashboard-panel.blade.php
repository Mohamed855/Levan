@props([
    'title',
    'icon' => null,
    'iconColor' => 'primary',
    'bodyClass' => null,
])

<div {{ $attributes->class(['card', 'dashboard-chart-card']) }}>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            @if ($icon)
                <i class="ti {{ $icon }} me-1 text-{{ $iconColor }}" aria-hidden="true"></i>
            @endif
            {{ $title }}
        </h5>

        @if (isset($headerAction) && ! $headerAction->isEmpty())
            {{ $headerAction }}
        @endif
    </div>

    <div @class(['card-body', $bodyClass])>
        {{ $slot }}
    </div>
</div>
