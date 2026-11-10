@props([
    'id',
    'title' => null,
    'icon' => null,
    'size' => null,
    'centered' => true,
    'scrollable' => false,
    'staticBackdrop' => false,
    'dismissible' => true,
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
    @if (! $dismissible) data-modal-dismissible="false" @endif
    @if ($staticBackdrop) data-bs-backdrop="static" data-bs-keyboard="false" @endif
    {{ $attributes->merge(['class' => 'modal fade']) }}>
    <div class="{{ $dialogClasses }}">
        <div class="modal-content">
            @if (isset($header))
                <div class="modal-header {{ $headerClass }}">
                    {{ $header }}
                    @if ($dismissible)
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    @endif
                </div>
            @elseif ($title)
                <div class="modal-header {{ $headerClass }}">
                    <h5 id="{{ $titleId }}" class="modal-title d-flex align-items-center">
                        @if ($icon)
                            @php
                                $modalIcon = trim($icon);
                                if (!str_contains($modalIcon, 'ti-') && !str_starts_with($modalIcon, 'ti')) {
                                    $modalIcon = 'ti ti-' . $modalIcon;
                                } elseif (str_starts_with($modalIcon, 'ti-')) {
                                    $modalIcon = 'ti ' . $modalIcon;
                                } elseif (!str_starts_with($modalIcon, 'ti ') && !str_starts_with($modalIcon, 'ti-')) {
                                    $modalIcon = 'ti ' . $modalIcon;
                                }
                            @endphp
                            <i class="{{ $modalIcon }} me-2 text-primary"></i>
                        @endif
                        <span>{{ $title }}</span>
                    </h5>
                    @if ($dismissible)
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    @endif
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
