@can('marketing_campaigns.view')
    @php
        $channelIcons = [
            'planning' => 'ti ti-clipboard-list text-primary',
            'promotion' => 'ti ti-speakerphone text-info',
            'brochure' => 'ti ti-book text-warning',
            'platform' => 'ti ti-device-laptop text-primary',
            'board_before' => 'ti ti-ad-2 text-danger',
            'brokers' => 'ti ti-user text-success',
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
    <div class="card {{ $hasFollowingCard ?? false ? 'mb-4' : '' }}">
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
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">الشعار</th>
                            <th>نوع الحملة</th>
                            <th class="text-center">التاريخ</th>
                            <th class="text-center">عدد المرفقات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($auction->marketingCampaigns as $campaign)
                            @php
                                $channelLabel = $campaign->channel_label;
                                $channelIcon = $channelIcons[$campaign->channel] ?? 'ti ti-speakerphone text-primary';
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <i class="{{ $channelIcon }} fs-4"></i>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $channelLabel }}</span>
                                </td>
                                <td class="text-center">
                                    {{ system_date($campaign->campaign_date) }}
                                </td>
                                <td class="text-center">
                                    @if ($campaign->evidence->count() > 0)
                                        <span
                                            class="badge bg-label-info">{{ $campaign->evidence->count() . ' مرفق' }}</span>
                                    @else
                                        <span>-</span>
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
