<div class="row g-4">
    @foreach ($sections['auction']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'auction.' . $key,
            'readonly' => filled($schema->auctionSource($auction)[$key] ?? null),
        ])
    @endforeach

    {{-- استلام وحفظ وإعادة أموال المتنافسين --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-cash fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">{{ $sections['funds']['label'] }}</h6>
        </div>
        <p class="text-muted small mb-0">آليات وشروط استلام وحفظ وإعادة أموال المتنافسين ومبالغ الدخول</p>
    </div>

    @foreach ($sections['funds']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'funds.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
