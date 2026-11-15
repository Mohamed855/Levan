<div class="row g-4">
    @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
        @php
            $existing = $data['attachments'][$type][0] ?? null;
            $existingFile = $existing
                ? [
                    'name' => $existing['name'] ?? $schema->attachmentTypes()[$type],
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => $type,
                    ]),
                    'path' => $existing['path'] ?? '',
                ]
                : null;
        @endphp
        <div class="col-md-6">
            <x-form.controls.file :name="'attachments[' . $type . ']'" :label="$schema->attachmentTypes()[$type]" :value="$existingFile" accept=".pdf,.png,.jpg,.jpeg"
                :required="!$existing" col="col-12" />
        </div>
    @endforeach
</div>
