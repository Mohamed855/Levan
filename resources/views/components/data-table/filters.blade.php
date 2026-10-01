@props([
    'resetId' => 'btn-reset-filters',
    'resetLabel' => 'إعادة تعيين',
    'showReset' => true,
    'cardClass' => 'mb-4',
])

<div {{ $attributes->class(['card', $cardClass]) }}>
    <div class="card-body">
        <div class="row g-4">
            {{ $slot }}
        </div>

        @if ($showReset)
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="{{ $resetId }}" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i>{{ $resetLabel }}
                </button>
            </div>
        @endif
    </div>
</div>
