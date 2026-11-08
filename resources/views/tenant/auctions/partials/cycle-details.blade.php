@can('marketing_campaigns.view')
    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">الحملات التسويقية ({{ $auction->marketingCampaigns->count() }})</h5></div>
        <div class="table-responsive">
            <table class="table"><thead><tr><th>#</th><th>نوع الحملة</th><th>المؤشرات</th><th>الأدلة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                @forelse($auction->marketingCampaigns as $campaign)
                    <tr>
                        <td>{{ $campaign->id }}</td>
                        <td>{{ $campaign->platform_label ?: __('auction_reports.channels.'.$campaign->channel) }}</td>
                        <td>
                            @foreach($campaign->metrics ?? [] as $metric => $value)
                                @continue($metric === 'property_id')
                                @if($value !== null)<div>{{ $metric === 'views' ? 'عدد المشاهدات' : (config('auction-marketing-fields.text.'.$metric) ?? __('auction_reports.metrics.'.$metric)) }}: {{ $value }}</div>@endif
                            @endforeach
                            @if($campaign->url)<a href="{{ $campaign->url }}" target="_blank" rel="noopener noreferrer">رابط الحملة</a>@endif
                        </td>
                        <td>{{ $campaign->evidence->count() }}</td>
                        <td>@include('tenant.marketing-campaigns.action', ['campaign' => $campaign])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">لم تُضف حملات تسويقية لهذا المزاد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcan
@can('auction_closings.view')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">إقفال المزاد</h5>
            @if(!$auction->closing?->finalized_snapshot && auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'))
                <x-actions.edit :url="tenant_route_name('auction-closings.edit', $auction)" :title="$auction->closing ? 'تعديل الإقفال' : 'إضافة الإقفال'" />
            @endif
        </div>
        @if($auction->closing)
            <div class="card-body"><span>تاريخ الإقفال: {{ $auction->closing->closing_date?->format('Y-m-d') }}</span>
                @if($auction->closing->approval_number)<span class="ms-3">رقم الاعتماد: {{ $auction->closing->approval_number }}</span>@endif
                @if($auction->closing->finalized_snapshot)<span class="badge bg-success ms-3">منتهي</span>@endif
            </div>
            <div class="table-responsive"><table class="table"><thead><tr><th>العقار</th><th>النتيجة</th><th>المشتري</th><th>قيمة الترسية</th><th>سبب عدم البيع</th><th>ملاحظات</th></tr></thead><tbody>
                @foreach($auction->properties as $property)
                    <tr><td>{{ $property->name }}</td><td>{{ $property->pivot->result_status === 'sold' ? 'مباع' : 'غير مباع' }}</td><td>{{ $property->pivot->buyer_name ?: '—' }}</td><td>{{ $property->pivot->sale_price ?? '—' }}</td><td>{{ $property->pivot->unsold_reason ?: '—' }}</td><td>{{ $property->pivot->result_notes ?: '—' }}</td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="card-body text-muted">لم تُسجل بيانات الإقفال بعد.</div>
        @endif
    </div>
@endcan
