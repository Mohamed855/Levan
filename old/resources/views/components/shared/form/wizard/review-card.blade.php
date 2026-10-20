@props(['title', 'step', 'bodyId' => null])

<article {{ $attributes->class(['card', 'wizard-review-card']) }}>
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <h5 class="mb-0">{{ $title }}</h5>
        <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-edit-step="{{ $step }}"
            aria-label="الانتقال إلى خطوة {{ $title }} للتعديل" title="تعديل {{ $title }}">
            <i class="ti ti-edit" aria-hidden="true"></i>
        </button>
    </div>
    <div @if ($bodyId) id="{{ $bodyId }}" @endif
        class="card-body d-flex flex-column gap-3">
        {{ $slot }}
    </div>
</article>
