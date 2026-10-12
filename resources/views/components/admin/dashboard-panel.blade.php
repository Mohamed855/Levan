@props([
    'title',
    'icon' => null,
    'iconColor' => 'primary',
    'bodyClass' => null,
])

<x-ui.dashboard-panel {{ $attributes }} :title="$title" :icon="$icon" :icon-color="$iconColor" :body-class="$bodyClass">
    @isset($headerAction)
        <x-slot:headerAction>{{ $headerAction }}</x-slot:headerAction>
    @endisset
    {{ $slot }}
</x-ui.dashboard-panel>
