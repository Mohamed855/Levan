@can('marketing_campaigns.view')
    @php
        $channelIcons = [
            'planning' => 'ti ti-clipboard-list text-primary',
            'promotion' => 'ti ti-speakerphone text-info',
            'brochure' => 'ti ti-book text-warning',
            'platform' => 'ti ti-device-laptop text-primary',
            'board_before' => 'ti ti-ad-2 text-danger',
            'brokers' => 'ti ti-users text-success',
            'youtube' => 'ti ti-brand-youtube text-danger',
            'x' => 'ti ti-brand-x text-dark',
            'snapchat' => 'ti ti-brand-snapchat text-warning',
            'tiktok' => 'ti ti-brand-tiktok text-dark',
            'whatsapp' => 'ti ti-brand-whatsapp text-success',
            'google' => 'ti ti-brand-google text-primary',
            'marketing_evidence' => 'ti ti-link text-info',
        ];
        $isClosed = $auction->isClosed();
        $isLocked = $isClosed;
        $hasCampaigns = $auction->marketingCampaigns->isNotEmpty();
    @endphp
    <div
        class="card {{ auth('tenant')->user()?->can('auction_closings.view') || ($hasFollowingCard ?? false) ? 'mb-4' : '' }}">
        <div class="card-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">
                <i class="ti ti-speakerphone text-primary me-2"></i>الحملات التسويقية
                ({{ $auction->marketingCampaigns->count() }})
            </h5>
            <div class="d-flex align-items-center gap-2">
                @if (!$isLocked && auth('tenant')->user()?->can('marketing_campaigns.edit'))
                    <x-actions.edit :url="tenant_route_name('marketing-campaigns.edit', $auction->id)" :title="$hasCampaigns ? 'تعديل الحملات التسويقية' : 'إضافة حملات تسويقية'" />
                @endif
            </div>
        </div>
        @if ($hasCampaigns)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>نوع الحملة</th>
                            <th>عدد المرفقات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($auction->marketingCampaigns as $campaign)
                            @php
                                $channelLabel = $campaign->channel_label;
                                $channelIcon = $channelIcons[$campaign->channel] ?? 'ti ti-speakerphone text-primary';
                            @endphp
                            <tr>
                                <td>
                                    {{ $channelLabel }}
                                    @if ($campaign->campaign_date)
                                        <small class="text-muted"><i class="ti ti-calendar me-1"></i>@date($campaign->campaign_date)</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($campaign->evidence->count() > 0)
                                        <span class="badge bg-label-info">{{ $campaign->evidence->count() . ' مرفق' }}</span>
                                    @else
                                        <span>لا يوجد</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-muted">لم تُضف حملات تسويقية لهذا المزاد بعد.</div>
        @endif
    </div>
@endcan
@can('auction_closings.view')
    @php
        $fallbackImage = asset('static/brand/logos/logo.svg');
        $totalProps = $auction->properties->count();
        $soldCount = $auction->properties->where('pivot.result_status', 'sold')->count();
        $unsoldCount = $auction->properties->where('pivot.result_status', 'unsold')->count();
        $completedCount = $soldCount + $unsoldCount;
    @endphp
    <div class="card {{ $hasFollowingCard ?? false ? 'mb-4' : '' }}">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="card-title mb-0">
                <i class="ti ti-lock-check text-primary me-2"></i>إقفال المزاد
            </h5>
            <div class="d-flex align-items-center flex-wrap gap-2">
                @if ($totalProps > 0)
                    <span class="badge bg-primary p-2">المحددة: {{ $completedCount }} / {{ $totalProps }}</span>
                    <span class="badge bg-success p-2">تمت الترسية: {{ $soldCount }}</span>
                    <span class="badge bg-danger p-2">لم تتم الترسية: {{ $unsoldCount }}</span>
                @endif

                @if (
                    !$auction->isClosed() &&
                        $auction->establishmentRequest?->isApproved() &&
                        auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'))
                    <x-actions.edit :url="tenant_route_name('auction-closings.edit', $auction)" :title="$auction->closing ? 'تعديل الإقفال' : 'إضافة الإقفال'" />
                @endif
            </div>
        </div>

        @if ($auction->closing)
            <div class="card-body py-2 px-4 bg-light-subtle border-bottom">
                <div class="row g-2 small">
                    <div class="col-md-4">
                        <span class="text-muted">تاريخ الإقفال:</span>
                        <strong class="text-body ms-1">@date($auction->closing->closing_date)</strong>
                    </div>
                    @if (!empty($auction->marketing_data['board_removal_date']))
                        <div class="col-md-4">
                            <span class="text-muted">تاريخ فك اللوحات:</span>
                            <strong class="text-body ms-1">{{ system_date($auction->marketing_data['board_removal_date']) }}</strong>
                        </div>
                    @endif
                    @if (!empty($auction->marketing_data['results_narrative']))
                        <div class="col-12 mt-1">
                            <span class="text-muted">تفاصيل نتائج المزاد:</span>
                            <span class="text-body ms-1">{{ $auction->marketing_data['results_narrative'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="card-body py-2 px-4 bg-light-subtle text-muted small border-bottom">
                <i class="ti ti-info-circle me-1 text-primary"></i>
                لم تُسجل بيانات الإقفال بعد. تظهر عقارات المزاد أدناه بحالتها الحالية حتى يتم إقفال المزاد.
            </div>
        @endif

        @if ($totalProps > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="closing-properties-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 3.5rem;" class="text-center" title="تسجيل واكتمال نتيجة العقار">
                                <i class="ti ti-checkbox fs-5" aria-hidden="true"></i>
                            </th>
                            <th style="width: 110px;">رقم الأصل</th>
                            <th style="width: 60px;" class="text-center">الصورة</th>
                            <th>اسم العقار</th>
                            <th>نوع العقار</th>
                            <th>المدينة / الحي</th>
                            <th>المساحة</th>
                            <th>سعر الافتتاح / القيمة</th>
                            <th class="text-center" style="width: 170px;">نتيجة العقار</th>
                            <th class="text-center" style="width: 100px;">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($auction->properties as $property)
                            @php
                                $image = $property->main_image_url ?? ($property->image_url ?? $fallbackImage);
                                $statusVal = $property->pivot?->result_status;
                                $isDecided = in_array($statusVal, ['sold', 'unsold'], true);
                                $propClosingUrl = tenant_route_name('auction-closings.edit', $auction);
                            @endphp
                            <tr class="closing-property-row {{ $statusVal === 'sold' ? 'table-success-subtle' : ($statusVal === 'unsold' ? 'table-danger-subtle' : '') }}">
                                <td class="text-center">
                                    <input type="checkbox"
                                        class="form-check-input closing-property-check"
                                        {{ $isDecided ? 'checked' : '' }} disabled tabindex="-1"
                                        style="pointer-events: none; cursor: default;"
                                        aria-label="اكتمال نتيجة {{ $property->name }}">
                                </td>
                                <td>
                                    <code class="copyable-code">{{ $property->property_number ?? '-' }}</code>
                                </td>
                                <td class="text-center">
                                    <img src="{{ $image }}" class="rounded"
                                        style="width:36px;height:36px;object-fit:cover" alt=""
                                        onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                </td>
                                <td>
                                    <span class="fw-semibold text-body">{{ $property->name }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-info">{{ $property->type_label ?? '-' }}</span>
                                </td>
                                <td>
                                    {{ $property->city ?? '-' }}{{ $property->district ? ' - ' . $property->district : '' }}
                                </td>
                                <td>{{ number_format((float) ($property->area ?? 0)) }} م²</td>
                                <td>
                                    @if ($property->pivot?->opening_price)
                                        {{ number_format((float) $property->pivot->opening_price) }} ر.س
                                    @elseif ($property->estimated_value)
                                        {{ number_format((float) $property->estimated_value) }} ر.س
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($statusVal === 'sold')
                                        <span class="badge bg-label-success closing-status-badge">مباع / تمت الترسية</span>
                                        @if ($property->pivot?->sale_price)
                                            <div class="small fw-semibold text-success mt-1">
                                                {{ number_format((float) $property->pivot->sale_price) }} ر.س
                                            </div>
                                        @endif
                                        @if ($property->pivot?->buyer_name)
                                            <div class="small text-muted text-truncate" title="{{ $property->pivot->buyer_name }}">
                                                {{ $property->pivot->buyer_name }}
                                            </div>
                                        @endif
                                    @elseif ($statusVal === 'unsold')
                                        <span class="badge bg-label-danger closing-status-badge">غير مباعة</span>
                                        @if ($property->pivot?->unsold_reason)
                                            <div class="small text-muted text-truncate" title="{{ $property->pivot->unsold_reason }}">
                                                {{ $property->pivot->unsold_reason }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-label-secondary closing-status-badge">غير محددة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (
                                        !$auction->isClosed() &&
                                            $auction->establishmentRequest?->isApproved() &&
                                            auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'))
                                        <a href="{{ $propClosingUrl }}" class="btn btn-icon btn-label-primary btn-sm"
                                            title="تعديل في نموذج الإقفال" aria-label="تعديل">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-muted">لا توجد عقارات مرتبطة بهذا المزاد.</div>
        @endif
    </div>
@endcan
