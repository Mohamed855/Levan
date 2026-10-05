@props([
    'table' => null,
    'target' => null,
    'name' => null,
    'resetId' => 'btn-reset-filters',
    'resetLabel' => 'إعادة تعيين',
    'showReset' => true,
    'cardClass' => 'mb-4',
])

@php
    $targetTable = $table ?? $target;
    if ($targetTable && !str_starts_with($targetTable, '#') && !str_starts_with($targetTable, '.')) {
        $targetTable = '#' . $targetTable;
    }
@endphp

<div {{ $attributes->class(['card', 'datatable-filters-card', $cardClass]) }}
    @if ($targetTable) data-datatable-target="{{ $targetTable }}" @endif
    @if ($name) data-datatable-filter-group="{{ $name }}" @endif
>
    <div class="card-body">
        <div class="row g-4">
            {{ $slot }}
        </div>

        @if ($showReset)
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="{{ $resetId }}" class="btn btn-secondary btn-reset-datatable-filters"
                    data-datatable-filter-reset="true"
                    @if ($targetTable) data-datatable-target="{{ $targetTable }}" @endif>
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i>{{ $resetLabel }}
                </button>
            </div>
        @endif
    </div>
</div>

