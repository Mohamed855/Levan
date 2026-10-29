@props([
    'title' => 'العقارات المحددة',
    'count' => 0,
    'properties' => collect([]),
    'fallbackImage' => asset('static/brand/logos/logo.svg'),
    'id' => 'selected-properties-cards',
    'countId' => 'selected-properties-count',
])

<section class="assignment-selection mt-4" aria-live="polite">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0 fw-bold">{{ $title }}</h6>
        <span class="badge bg-primary">
            <span id="{{ $countId }}">{{ $count }}</span> عقار
        </span>
    </div>
    <div id="{{ $id }}" class="assignment-selection__items">
        @if ($properties && $properties->isNotEmpty())
            @foreach ($properties as $prop)
                <article class="assignment-property-card" data-property-card="{{ $prop->id }}">
                    <img class="assignment-property-card__image"
                        src="{{ $prop->main_image ? tasset($prop->main_image) : $fallbackImage }}"
                        alt="{{ $prop->name }}"
                        onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                    <div class="assignment-property-card__content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <code class="copyable-code">{{ $prop->property_number ?? '-' }}</code>
                            @if (!empty($prop->type_label))
                                <span class="badge bg-info">{{ $prop->type_label }}</span>
                            @endif
                        </div>
                        <h6 class="mb-1 text-body text-truncate" title="{{ $prop->name }}">{{ $prop->name }}</h6>
                        <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>{{ $prop->city ?? '-' }}</p>
                    </div>
                    <button type="button" class="btn btn-sm remove-property-card-btn"
                        data-remove-property="{{ $prop->id }}"
                        data-remove-auction-property="{{ $prop->id }}"
                        aria-label="إزالة {{ $prop->name }}">
                        <i class="ti ti-x"></i>
                    </button>
                </article>
            @endforeach
        @else
            <p class="text-muted mb-0" data-empty {{ $count > 0 ? 'hidden' : '' }}>لم يتم اختيار عقارات بعد.</p>
        @endif
    </div>
</section>
