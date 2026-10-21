@props([
    'label',
    'value',
    'icon' => 'ti-chart-bar',
    'tone' => 'primary',
])

<article {{ $attributes->class(['card h-100']) }}>
    <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar avatar-lg">
            <span class="avatar-initial rounded bg-label-{{ $tone }}">
                <i class="ti {{ $icon }}"></i>
            </span>
        </span>
        <div>
            <p class="mb-1 text-muted">{{ $label }}</p>
            <h4 class="mb-0">{{ number_format((int) $value) }}</h4>
        </div>
    </div>
</article>
