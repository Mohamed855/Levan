@props(['class' => 'justify-content-center gap-2'])

<div {{ $attributes->merge(['class' => 'd-flex ' . $class]) }}>
    {{ $slot }}
</div>
