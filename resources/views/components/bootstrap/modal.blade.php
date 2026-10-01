@props([
    'id',
    'title',
    'size' => null,
    'centered' => false,
    'scrollable' => false,
    'staticBackdrop' => false,
    'labelledBy' => null,
])

@php
    $titleId = $labelledBy ?: $id . '-title';
    $dialogClasses = trim('modal-dialog' . ($size ? ' modal-' . $size : '') . ($centered ? ' modal-dialog-centered' : '') . ($scrollable ? ' modal-dialog-scrollable' : ''));
@endphp

<div id="{{ $id }}" class="modal fade" tabindex="-1" aria-labelledby="{{ $titleId }}" aria-hidden="true"
    @if ($staticBackdrop) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
    <div class="{{ $dialogClasses }}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="{{ $titleId }}" class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>

            @if (isset($footer) && ! $footer->isEmpty())
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
