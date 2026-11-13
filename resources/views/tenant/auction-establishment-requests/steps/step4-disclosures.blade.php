<div class="row g-4">
    {{-- الإفصاحات --}}
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-shield-check fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">{{ $sections['disclosures']['label'] }}</h6>
        </div>
        <p class="text-muted small mb-0">الإفصاحات النظامية والقانونية الخاصة بالمزاد وعقاراته</p>
    </div>

    <x-form.controls.select name="data[disclosures][disposition_rights][]" label="حقوق التصرف"
        :options="$schema->dispositionRights()"
        :value="old('data.disclosures.disposition_rights', $data['disclosures']['disposition_rights'] ?? [])"
        required multiple col="col-12" />

    @foreach ($sections['disclosures']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'disclosures.' . $key,
            'readonly' => false,
        ])
    @endforeach

    {{-- الإقرار والتعهد --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-file-text fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">{{ $sections['declaration']['label'] }}</h6>
        </div>
        <p class="text-muted small mb-0">بيانات الممثل النظامي للمنشأة ومصادقة الطلب</p>
    </div>

    @foreach ($sections['declaration']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'declaration.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
