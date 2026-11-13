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
            $attachmentUrl = $existing ? tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type]) : null;
        @endphp
        <div class="col-md-6">
            <x-form.controls.file :name="'attachments[' . $type . ']'"
                :label="$schema->attachmentTypes()[$type]"
                accept=".pdf,.png,.jpg,.jpeg"
                :required="!$existing"
                col="col-12" />
            @if ($existing)
                <div class="mt-2 assignment-file-list">
                    <x-ui.file-card :name="$existing['name']" :url="$attachmentUrl" meta="مستند معتمد مرفوع مسبقاً" />
                </div>
            @endif
        </div>
    @endforeach

    {{-- مرفقات عقارات المزاد --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-files fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">مستندات ومرفقات العقارات المرتبطة</h6>
        </div>
        <p class="text-muted small mb-3">وثائق الملكية ونماذج التقييم العقاري ومستندات التفويض والوكالات لكل عقار مدرج في المزاد</p>
    </div>

    <div class="col-12">
        @foreach ($data['properties'] as $index => $propertyData)
            @php
                $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
            @endphp
            <div class="card border shadow-none mb-4">
                <div class="card-header bg-light-subtle py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-building text-primary fs-5"></i>
                        <span class="fw-semibold text-body">{{ $property?->name ?? 'عقار' }}</span>
                        <code class="copyable-code">{{ $property?->property_number ?? '-' }}</code>
                    </div>
                    <span class="badge bg-label-info">{{ $property?->type_label ?? 'عقار' }}</span>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-4">
                        @foreach (['deed', 'valuation', 'representative_authority'] as $type)
                            @php
                                $existing = $data['attachments'][$type][$propertyData['property_id']] ?? null;
                                $attachmentUrl = $existing ? tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type, 'property' => $propertyData['property_id']]) : null;
                            @endphp
                            <div class="col-md-4">
                                <x-form.controls.file :name="'attachments[' . $type . '][' . $propertyData['property_id'] . ']'"
                                    :label="$schema->attachmentTypes()[$type]"
                                    accept=".pdf,.png,.jpg,.jpeg"
                                    :required="$type === 'deed' && !$existing"
                                    :help="$type === 'valuation' ? 'مطلوب عند وجود مبلغ دخول.' : ($type === 'representative_authority' ? 'مطلوب عند وجود وكيل أو معتمد عقد غير المالك.' : null)"
                                    col="col-12" />
                                @if ($existing)
                                    <div class="mt-2 assignment-file-list">
                                        <x-ui.file-card :name="$existing['name']" :url="$attachmentUrl" meta="مرفوع مسبقاً" />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
