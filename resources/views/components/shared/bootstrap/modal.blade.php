@props([
    'id',
    'title' => null,
    'icon' => null,
    'size' => null,
    'centered' => true,
    'scrollable' => false,
    'staticBackdrop' => false,
    'labelledBy' => null,
    'dialogClass' => null,
    'bodyClass' => null,
    'headerClass' => null,
    'footerClass' => null,
])

@php
    $titleId = $labelledBy ?: $id . 'Label';
    $dialogClasses = trim('modal-dialog' . ($size ? ' modal-' . $size : '') . ($centered ? ' modal-dialog-centered' : '') . ($scrollable ? ' modal-dialog-scrollable' : '') . ($dialogClass ? ' ' . $dialogClass : ''));
@endphp

<div id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $titleId }}" aria-hidden="true"
    @if ($staticBackdrop) data-bs-backdrop="static" data-bs-keyboard="false" @endif
    {{ $attributes->merge(['class' => 'modal fade']) }}>
    <div class="{{ $dialogClasses }}">
        <div class="modal-content">
            @if (isset($header))
                <div class="modal-header {{ $headerClass }}">
                    {{ $header }}
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
            @elseif ($title)
                <div class="modal-header {{ $headerClass }}">
                    <h5 id="{{ $titleId }}" class="modal-title d-flex align-items-center">
                        @if ($icon)
                            <i class="ti {{ $icon }} me-2 text-primary"></i>
                        @endif
                        <span>{{ $title }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
            @endif

            <div class="modal-body {{ $bodyClass }}">
                {{ $slot }}
            </div>

            @if (isset($footer) && ! $footer->isEmpty())
                <div class="modal-footer {{ $footerClass }}">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
