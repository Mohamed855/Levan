<div class="row g-4">
    {{-- شهادات اجتياز دورة المزادات العقارية --}}
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-certificate fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">شهادات اجتياز دورة المزادات العقارية</h6>
        </div>
        <p class="text-muted small mb-0">يجب إرفاق شهادات اجتياز الدورة المعتمدة لكل من مدير المزاد ومحرّج المزاد.</p>
    </div>

    @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
        @php
            $existing = $data['attachments'][$type][0] ?? null;
            $existingFile = $existing ? [
                'name' => $existing['name'] ?? $schema->attachmentTypes()[$type],
                'url' => tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type]),
                'path' => $existing['path'] ?? '',
            ] : null;
        @endphp
        <div class="col-md-6">
            <x-form.controls.file :name="'attachments[' . $type . ']'"
                :label="$schema->attachmentTypes()[$type]"
                :value="$existingFile"
                accept=".pdf,.png,.jpg,.jpeg"
                :required="!$existing"
                col="col-12" />
        </div>
    @endforeach
</div>
