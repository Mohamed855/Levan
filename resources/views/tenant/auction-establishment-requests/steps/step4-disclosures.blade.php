<div class="row g-4">
    {{-- الإفصاحات --}}
    <div class="col-12">
        <h6 class="fw-bold mb-1"><i class="ti ti-shield-check me-1"></i>{{ $sections['disclosures']['label'] }}</h6>
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
        <h6 class="fw-bold mb-1"><i class="ti ti-file-text me-1"></i>{{ $sections['declaration']['label'] }}</h6>
        <p class="text-muted small mb-0">بيانات الممثل النظامي للمنشأة ومصادقة الطلب</p>
    </div>

    @foreach ($sections['declaration']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'declaration.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
