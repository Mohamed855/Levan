<div class="row g-4">
    <x-form.controls.select name="data[disclosures][disposition_rights][]" label="حقوق التصرف" :options="$schema->dispositionRights()"
        :value="old('data.disclosures.disposition_rights', $data['disclosures']['disposition_rights'] ?? [])" required multiple col="col-md-6" />

    @foreach ($sections['declaration']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'declaration.' . $key,
            'readonly' => false,
        ])
    @endforeach

    @foreach ($sections['disclosures']['fields'] as $key => $field)
        @include('tenant.auction-establishment-requests.field', [
            'path' => 'disclosures.' . $key,
            'readonly' => false,
        ])
    @endforeach
</div>
