@props([
    'title',
    'subtitle' => null,
    'desc' => null,
    'align' => 'center',
])

<div {{ $attributes->merge(['class' => 'section-header scroll-revealed text-' . $align]) }}>
    @if ($subtitle)
        <span class="section-eyebrow">{{ $subtitle }}</span>
    @endif
    <h2 class="section-title">{{ $title }}</h2>
    @if ($desc)
        <p class="section-desc">{{ $desc }}</p>
    @endif
</div>
