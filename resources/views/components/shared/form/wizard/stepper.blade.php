@props([
    'id',
    'steps' => [],
    'contentClass' => 'bs-stepper-content',
])

@php
    $hasMultipleSteps = count($steps) > 1;
@endphp

@if ($hasMultipleSteps)
    <div id="{{ $id }}" {{ $attributes->class(['bs-stepper']) }} data-form-stepper>
        <div class="bs-stepper-header">
            @foreach ($steps as $index => $step)
                @php
                    $stepId = $step['id'];
                    $title = $step['title'] ?? '';
                    $subtitle = $step['subtitle'] ?? null;
                @endphp
                <div class="step" data-target="#{{ $stepId }}">
                    <button type="button" class="step-trigger" aria-controls="{{ $stepId }}">
                        <span class="bs-stepper-circle">{{ $index + 1 }}</span>
                        <span class="bs-stepper-label{{ $index === 0 ? ' mt-1' : '' }}">
                            <span class="bs-stepper-title">{{ $title }}</span>
                            @if ($subtitle)
                                <span class="bs-stepper-subtitle">{{ $subtitle }}</span>
                            @endif
                        </span>
                    </button>
                </div>
                @if (! $loop->last)
                    <div class="line"><i class="ti ti-chevron-right" aria-hidden="true"></i></div>
                @endif
            @endforeach
        </div>

        <div class="{{ $contentClass }}">
            {{ $slot }}
        </div>
    </div>
@else
    <div id="{{ $id }}" {{ $attributes->class(['card']) }}>
        <div class="{{ $contentClass === 'bs-stepper-content' ? 'card-body' : $contentClass }}">
            {{ $slot }}
        </div>
    </div>
@endif
