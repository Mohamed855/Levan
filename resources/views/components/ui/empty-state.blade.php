@props([
    'title' => 'لا توجد بيانات',
    'description' => null,
    'icon' => 'ti ti-inbox',
])

<div {{ $attributes->class(['text-center py-5']) }}>
    <i class="{{ $icon }} ti-32px text-muted d-block mb-2" aria-hidden="true"></i>
    <h6 class="mb-1">{{ $title }}</h6>
    @if ($description)
        <p class="text-muted mb-0">{{ $description }}</p>
    @endif
    @if (isset($action) && ! $action->isEmpty())
        <div class="mt-3">{{ $action }}</div>
    @endif
</div>
