@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'primary',
    'col' => 'col-sm-4 col-lg-6 col-xxl-2',
    'valueClass' => null,
    'avatarClass' => 'rounded',
])

<x-ui.stat-card {{ $attributes }} :title="$title" :value="$value" :subtitle="$subtitle" :icon="$icon" :color="$color"
    :col="$col" :value-class="$valueClass" :avatar-class="$avatarClass">
    {{ $slot }}
</x-ui.stat-card>
