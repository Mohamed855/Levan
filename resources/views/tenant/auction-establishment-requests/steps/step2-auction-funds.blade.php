<div class="row g-4">
    {{-- تفاصيل إقامة المزاد --}}
    <div class="col-12">
        <h6 class="fw-bold mb-1"><i class="ti ti-calendar-event me-1"></i>{{ $sections['auction']['label'] }}</h6>
        <p class="text-muted small mb-0">المواعيد ومقر وطريقة إقامة المزاد وتفاصيل الترسية</p>
    </div>

    @foreach ($sections['auction']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'auction.' . $key,
            'readonly' => filled($schema->auctionSource($auction)[$key] ?? null),
        ])
    @endforeach

    {{-- استلام وحفظ وإعادة أموال المتنافسين --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-1"><i class="ti ti-cash me-1"></i>{{ $sections['funds']['label'] }}</h6>
        <p class="text-muted small mb-0">آليات وشروط استلام وحفظ وإعادة أموال المتنافسين ومبالغ الدخول</p>
    </div>

    @foreach ($sections['funds']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'funds.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
