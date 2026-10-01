@props(['class' => 'justify-content-center gap-1'])

<div {{ $attributes->merge(['class' => 'd-flex ' . $class]) }}>
    {{ $slot }}
</div>
