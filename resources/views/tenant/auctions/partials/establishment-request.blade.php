@can('auction_establishment_requests.view')
    @php($establishment = $auction->establishmentRequest)
    <div class="card mb-4 shadow-sm border-primary" id="auction-establishment-card">
        <div class="card-header text-primary py-3 d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0 fw-bold">
                <i class="ti ti-file-description me-1"></i>طلب إقامة المزاد
            </h6>
            <div>
                @if ($establishment?->isApproved())
                    <span class="badge bg-success">معتمد</span>
                @elseif ($establishment)
                    <span class="badge bg-warning">قيد المراجعة</span>
                @else
                    <span class="badge bg-secondary">لم يقدم بعد</span>
                @endif
            </div>
        </div>
        <div class="card-body pt-3">
            @if ($establishment)
                <div class="p-3 mb-3 border rounded bg-light-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">تاريخ تقديم الطلب:</span>
                        <span
                            class="fw-semibold small">{{ $establishment->submitted_at?->format('Y/m/d H:i') ?? '-' }}</span>
                    </div>
                    @if ($establishment->isApproved())
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">رقم الموافقة:</span>
                            <span class="badge bg-label-success fw-bold">{{ $establishment->approval_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">تاريخ الاعتماد:</span>
                            <span
                                class="small text-muted">{{ $establishment->approved_at?->format('Y/m/d H:i') ?? '-' }}</span>
                        </div>
                    @else
                        <div class="alert alert-warning p-2 small mb-0 mt-2">
                            <i class="ti ti-clock-exclamation me-1"></i>بانتظار المراجعة وإدخال رقم الموافقة على إقامة
                            المزاد.
                        </div>
                    @endif
                </div>
            @else
                <div class="alert alert-info p-2 small mb-3">
                    <i class="ti ti-info-circle me-1"></i>استكمل طلب إقامة المزاد لحفظ بيانات النموذج وإرساله للمراجعة.
                </div>
            @endif

            @if ($establishment && !empty($establishment->data['attachments']))
                <div class="mt-3 pt-3 border-top">
                    <span class="text-muted small fw-semibold d-block mb-2">مرفقات الطلب:</span>
                    @foreach ($establishment->data['attachments'] ?? [] as $type => $files)
                        @foreach ($files as $propertyId => $file)
                            <a class="d-flex align-items-center justify-content-between p-2 mb-2 border rounded bg-light-subtle text-body text-decoration-none"
                                href="{{ tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type, 'property' => $propertyId ?: null]) }}">
                                <span class="small"><i
                                        class="ti ti-paperclip me-1 text-primary"></i>{{ app(\App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData::class)->attachmentTypes()[$type] ?? $type }}
                                    — {{ $file['name'] }}</span>
                                <i class="ti ti-download text-muted small"></i>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @if ($establishment && !$establishment->isApproved() && !$auction->isClosed())
        @can('auction_establishment_requests.approve')
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
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success">اعتماد الطلب</button>
                    </div>
                </form>
            </x-bootstrap.modal>
        @endcan
    @endif
@endcan
