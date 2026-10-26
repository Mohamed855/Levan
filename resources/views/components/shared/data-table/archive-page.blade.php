@props([
    'cardId' => null,
])

<x-ui.validation-errors />

@if (isset($filters) && ! $filters->isEmpty())
    {{ $filters }}
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
