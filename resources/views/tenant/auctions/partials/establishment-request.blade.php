@can('auction_establishment_requests.view')
    @php
        $establishment = $auction->establishmentRequest;
        $canEditEstablishment =
            !$establishment?->isApproved() &&
            !$auction->isClosed() &&
            auth('tenant')
                ->user()
                ?->can(
                    $establishment ? 'auction_establishment_requests.edit' : 'auction_establishment_requests.create',
                );
        $canApproveEstablishment =
            $establishment &&
            !$establishment->isApproved() &&
            !$auction->isClosed() &&
            auth('tenant')->user()?->can('auction_establishment_requests.approve');

        $establishmentFileGroups = [];
        if ($establishment && !empty($establishment->data['attachments'])) {
            $attachmentTypeLabels = app(
                \App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData::class,
            )->attachmentTypes();
            $attachmentIcons = [
                'manager_certificate' => 'ti-certificate',
                'auctioneer_certificate' => 'ti-certificate-2',
                'deed' => 'ti-file-certificate',
                'valuation' => 'ti-report-money',
                'representative_authority' => 'ti-user-check',
            ];
            $rawAttachments = $establishment->data['attachments'];
            $orderedTypes = array_unique(array_merge(array_keys($attachmentTypeLabels), array_keys($rawAttachments)));

            foreach ($orderedTypes as $type) {
                $files = $rawAttachments[$type] ?? [];
                if (empty($files)) {
                    continue;
                }
                $items = [];
                foreach ($files as $propertyId => $file) {
                    $attachmentUrl = tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => $type,
                        'property' => $propertyId ?: null,
                    ]);
                    $propertyName = $propertyId
                        ? $auction->properties->firstWhere('id', (int) $propertyId)?->name
                        : null;
                    $items[] = [
                        'name' => $file['name'] ?? ($attachmentTypeLabels[$type] ?? $type),
                        'url' => $attachmentUrl,
                        'downloadUrl' => $attachmentUrl,
                        'meta' => $propertyName ?: $attachmentTypeLabels[$type] ?? null,
                    ];
                }
                if (!empty($items)) {
                    $establishmentFileGroups[] = [
                        'label' => $attachmentTypeLabels[$type] ?? $type,
                        'icon' => $attachmentIcons[$type] ?? 'ti-paperclip',
                        'items' => $items,
                    ];
                }
            }
        }
    @endphp

    <div class="card mb-4" id="auction-establishment-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="card-title mb-0">
                <i class="ti ti-file-description text-primary me-2"></i>طلب إقامة المزاد
            </h5>
            <div class="d-flex align-items-center flex-wrap gap-2">

                @if ($canEditEstablishment)
                    <x-actions.edit :url="tenant_route_name('auction-establishment-requests.edit', $auction)" :title="$establishment ? 'تعديل طلب إقامة المزاد' : 'تقديم طلب إقامة المزاد'" :icon="$establishment ? 'ti ti-edit' : 'ti ti-file-plus'" />
                @endif

                @if ($canApproveEstablishment)
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                        data-bs-target="#approveEstablishmentModal">
                        <i class="ti ti-check me-1"></i>اعتماد الطلب
                    </button>
                @endif
            </div>
        </div>

        @if ($establishment)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الحالة</th>
                            <th>تاريخ تقديم الطلب</th>
                            <th>رقم الموافقة</th>
                            <th>تاريخ الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                @if ($establishment->isApproved())
                                    <span class="badge bg-label-success">معتمد</span>
                                @else
                                    <span class="badge bg-label-warning">قيد المراجعة</span>
                                @endif
                            </td>
                            <td>@datetime($establishment->submitted_at)</td>
                            <td>
                                @if ($establishment->isApproved())
                                    <span
                                        class="badge bg-label-success fw-bold">{{ $establishment->approval_number }}</span>
                                @else
                                    <span class="text-muted">بانتظار المراجعة</span>
                                @endif
                            </td>
                            <td>
                                @if ($establishment->isApproved())
                                    @datetime($establishment->approved_at)
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if (!empty($establishmentFileGroups))
                <div class="card-body border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-paperclip text-primary me-2"></i>مرفقات الطلب
                        </h6>
                        <span
                            class="badge bg-label-primary">{{ collect($establishmentFileGroups)->sum(fn($g) => count($g['items'])) }}</span>
                    </div>
                    <x-ui.file-groups :groups="$establishmentFileGroups" />
                </div>
            @endif
        @else
            <div class="card-body text-muted">
                لم يُقدّم طلب إقامة المزاد بعد. استكمل الطلب لحفظ بيانات النموذج وإرساله للمراجعة.
            </div>
        @endif
    </div>

    @if ($canApproveEstablishment)
        <x-bootstrap.modal id="approveEstablishmentModal" title="اعتماد طلب إقامة المزاد" icon="ti-check">
            <form method="POST" data-form-validate
                action="{{ tenant_route_name('auction-establishment-requests.approve', $auction) }}">
                @csrf
                <p class="text-muted">يحفظ الاعتماد بيانات الطلب ويمنع تعديلها.</p>
                <div class="row g-4">
                    <x-form.controls.input name="approval_number" label="رقم الموافقة على إقامة المزاد" :value="old('approval_number')"
                        required maxlength="255" col="col-12" />
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">اعتماد الطلب</button>
                </div>
            </form>
        </x-bootstrap.modal>
    @endif
@endcan
