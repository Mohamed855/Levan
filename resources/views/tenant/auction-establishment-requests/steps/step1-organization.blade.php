<div class="row g-4">
    {{-- المنشأة العقارية --}}
    <div class="col-12">
        <h6 class="fw-bold mb-1"><i class="ti ti-building me-1"></i>{{ $sections['organization']['label'] }}</h6>
        <p class="text-muted small mb-0">بيانات المنشأة وترخيص فال للمزادات العقارية</p>
    </div>

    @foreach ($sections['organization']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'organization.' . $key,
            'readonly' => in_array($key, ['name', 'commercial_registration']),
        ])
    @endforeach

    {{-- مدير المزاد --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-1"><i class="ti ti-user-check me-1"></i>{{ $sections['manager']['label'] }}</h6>
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
        <h6 class="fw-bold mb-1"><i class="ti ti-gavel me-1"></i>{{ $sections['auctioneer']['label'] }}</h6>
        <p class="text-muted small mb-0">بيانات وهوية محرّج المزاد ومعلومات التواصل</p>
    </div>

    @foreach ($sections['auctioneer']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'auctioneer.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
