@props([
    'tableId' => null,
    'tableClass' => 'table table-striped table-bordered w-100',
    'responsive' => true,
    'cardId' => null,
])

@if (isset($stats) && ! $stats->isEmpty())
    <div class="row g-4 mb-4">
        {{ $stats }}
    </div>
@endif

@if (isset($filters) && ! $filters->isEmpty())
    {{ $filters }}
@endif

@if (isset($beforeTable) && ! $beforeTable->isEmpty())
    {{ $beforeTable }}
@endif

<div @if ($cardId) id="{{ $cardId }}" @endif class="card">
    <div class="card-body">
        @if (isset($table) && ! $table->isEmpty())
            {{ $table }}
        @else
            {{ $slot }}
        @endif
    </div>
</div>
