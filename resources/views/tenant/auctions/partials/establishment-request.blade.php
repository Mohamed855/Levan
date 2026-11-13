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
            </div>
        </div>

        @if ($establishment)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>تاريخ تقديم الطلب</th>
                            <th>رقم الموافقة</th>
                            <th>الحالة</th>
                            <th>تاريخ الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>@datetime($establishment->submitted_at)</td>
                            <td>
                                @if ($establishment->isApproved() && filled($establishment->approval_number))
                                    <div class="d-inline-flex align-items-center gap-1 copyable-code-wrapper"
                                        data-code="{{ $establishment->approval_number }}" role="button" tabindex="0"
                                        title="اضغط لنسخ الرقم">
                                        <code class="copyable-code">{{ $establishment->approval_number }}</code>
                                        <i class="ti ti-copy text-muted"></i>
                                    </div>
                                @elseif ($establishment->isApproved())
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="text-muted">بانتظار المراجعة</span>
                                @endif
                            </td>
                            <td>
                                @if ($establishment->isApproved())
                                    <span class="badge bg-label-success">معتمد</span>
                                @else
                                    <span class="badge bg-label-warning">قيد المراجعة</span>
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
        @else
            <div class="card-body text-muted">
                لم يُقدّم طلب إقامة المزاد بعد. استكمل الطلب لحفظ بيانات النموذج وإرساله للمراجعة.
            </div>
        @endif
    </div>
@endcan
