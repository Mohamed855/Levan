<div class="row g-4">


    @foreach ($sections['organization']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'organization.' . $key,
            'readonly' => in_array($key, ['name', 'commercial_registration']),
        ])
    @endforeach

    {{-- مدير المزاد --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-user-check fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">{{ $sections['manager']['label'] }}</h6>
        </div>
        <p class="text-muted small mb-0">بيانات وهوية مدير المزاد ومعلومات التواصل</p>
    </div>

    @foreach ($sections['manager']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'manager.' . $key,
            'readonly' => false,
        ])
    @endforeach

    {{-- محرّج المزاد --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-gavel fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">{{ $sections['auctioneer']['label'] }}</h6>
        </div>
        <p class="text-muted small mb-0">بيانات وهوية محرّج المزاد ومعلومات التواصل</p>
    </div>

    @foreach ($sections['auctioneer']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'auctioneer.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
