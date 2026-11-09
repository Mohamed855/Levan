<div class="row g-4">
    {{-- شهادات اجتياز دورة المزادات العقارية --}}
    <div class="col-12">
        <h6 class="fw-bold mb-1"><i class="ti ti-certificate me-1"></i>شهادات اجتياز دورة المزادات العقارية</h6>
        <p class="text-muted small mb-0">يجب إرفاق شهادات اجتياز الدورة لكل من مدير المزاد ومحرّج المزاد.</p>
    </div>

    @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
        @php
            $existing = $data['attachments'][$type][0] ?? null;
        @endphp
        <div class="col-md-6">
            <x-form.controls.file :name="'attachments[' . $type . ']'"
                :label="$schema->attachmentTypes()[$type]"
                accept=".pdf,.png,.jpg,.jpeg"
                :required="!$existing"
                col="col-12" />
            @if ($existing)
                <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                    <span class="text-truncate small"><i class="ti ti-file-check text-success me-1"></i>{{ $existing['name'] }}</span>
                    <a href="{{ tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type]) }}" target="_blank" class="btn btn-xs btn-outline-primary">معاينة</a>
                </div>
            @endif
        </div>
    @endforeach

    {{-- مرفقات عقارات المزاد --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-1"><i class="ti ti-files me-1"></i>مستندات ومرفقات العقارات المرتبطة</h6>
        <p class="text-muted small mb-0">وثائق الملكية ونماذج التقييم العقاري ومستندات التفويض والوكالات</p>
    </div>

    @foreach ($data['properties'] as $index => $propertyData)
        @php
            $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
        @endphp
        <div class="col-12 {{ !$loop->first ? 'border-top pt-3' : '' }}">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fw-semibold"><i class="ti ti-building me-1"></i>{{ $property?->name ?? 'عقار' }} — {{ $property?->property_number ?? '-' }}</span>
                <span class="badge bg-label-info">{{ $property?->type_label ?? 'عقار' }}</span>
            </div>
            <div class="row g-4">
                @foreach (['deed', 'valuation', 'representative_authority'] as $type)
                    @php
                        $existing = $data['attachments'][$type][$propertyData['property_id']] ?? null;
                    @endphp
                    <div class="col-md-4">
                        <x-form.controls.file :name="'attachments[' . $type . '][' . $propertyData['property_id'] . ']'"
                            :label="$schema->attachmentTypes()[$type]"
                            accept=".pdf,.png,.jpg,.jpeg"
                            :required="$type === 'deed' && !$existing"
                            :help="$type === 'valuation' ? 'مطلوب عند وجود مبلغ دخول.' : ($type === 'representative_authority' ? 'مطلوب عند وجود وكيل أو معتمد عقد غير المالك.' : null)"
                            col="col-12" />
                        @if ($existing)
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="text-truncate small"><i class="ti ti-file-check text-success me-1"></i>{{ $existing['name'] }}</span>
                                <a href="{{ tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type, 'property' => $propertyData['property_id']]) }}" target="_blank" class="btn btn-xs btn-outline-primary">معاينة</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
