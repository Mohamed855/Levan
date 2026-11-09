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
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">الحملات التسويقية ({{ $auction->marketingCampaigns->count() }})</h5>
            @if (!$isLocked && auth('tenant')->user()?->can('marketing_campaigns.edit'))
                <x-actions.edit :url="tenant_route_name('marketing-campaigns.edit', $auction->id)" :title="$hasCampaigns ? 'تعديل الحملات التسويقية' : 'إضافة حملات تسويقية'" />
            @endif
        </div>
        @if ($hasCampaigns)
            <div class="table-responsive">
                <table class="table table-hover">
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
                                        <small class="text-muted"><i
                                                class="ti ti-calendar me-1"></i>{{ $campaign->campaign_date->format('Y-m-d') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-label-info">{{ $campaign->evidence->count() }} مرفق</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-muted pt-3">لم تُضف حملات تسويقية لهذا المزاد بعد.</div>
        @endif
    </div>
@endcan
@can('auction_closings.view')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">إقفال المزاد</h5>
            @if (
                !$auction->isClosed() && $auction->establishmentRequest?->isApproved() &&
                    auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'))
                <x-actions.edit :url="tenant_route_name('auction-closings.edit', $auction)" :title="$auction->closing ? 'تعديل الإقفال' : 'إضافة الإقفال'" />
            @endif
        </div>
        @if ($auction->closing)
            <div class="card-body"><span>تاريخ الإقفال: {{ $auction->closing->closing_date?->format('Y-m-d') }}</span>
                @if ($auction->establishmentRequest?->isApproved())
                    <span class="ms-3">رقم الاعتماد: {{ $auction->establishmentRequest->approval_number }}</span>
                @endif
                @if ($auction->isClosed())
                    <span class="badge bg-success ms-3">منتهي</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>العقار</th>
                            <th>النتيجة</th>
                            <th>المشتري</th>
                            <th>قيمة الترسية</th>
                            <th>سبب عدم البيع</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($auction->properties as $property)
                            <tr>
                                <td>{{ $property->name }}</td>
                                <td>{{ $property->pivot->result_status === 'sold' ? 'مباع' : 'غير مباع' }}</td>
                                <td>{{ $property->pivot->buyer_name ?: '—' }}</td>
                                <td>{{ $property->pivot->sale_price ?? '—' }}</td>
                                <td>{{ $property->pivot->unsold_reason ?: '—' }}</td>
                                <td>{{ $property->pivot->result_notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-muted pt-3">لم تُسجل بيانات الإقفال بعد.</div>
        @endif
    </div>
@endcan
