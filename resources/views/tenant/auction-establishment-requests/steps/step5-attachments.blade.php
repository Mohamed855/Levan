<div class="row g-4">
    @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
        @php
            $removalName = 'remove_attachments_' . $type;
            $existing = old($removalName) ? null : ($data['attachments'][$type][0] ?? null);
            $existingFile = $existing
                ? [
                    'name' => $existing['name'] ?? $schema->attachmentTypes()[$type],
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => $type,
                    ]),
                    'path' => $existing['path'] ?? '',
                    'mime' => $existing['mime'] ?? '',
                ]
                : null;
        @endphp
        <div class="col-md-6">
            <x-form.controls.file :name="'attachments[' . $type . ']'" :label="$schema->attachmentTypes()[$type]" :value="$existingFile" accept=".pdf,.png,.jpg,.jpeg"
                required col="col-12" />
            <input type="hidden" name="{{ $removalName }}" value="{{ old($removalName, '0') }}"
                data-certificate-removal="{{ $type }}">
        </div>
    @endforeach
</div>
