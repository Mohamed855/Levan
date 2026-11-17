@php
    $brokerRows = old('campaigns.brokers.metrics.brokers', app(\App\Services\Tenants\MarketingCampaigns\MarketingCampaignBrokers::class)->rows($campaign));
    $brokerRows = is_array($brokerRows) && $brokerRows !== [] ? $brokerRows : [['key' => 'broker-1', 'name' => '', 'evidence_id' => null]];
@endphp
<div class="col-12" data-broker-repeater data-next-index="{{ max(array_map('intval', array_keys($brokerRows))) + 1 }}">
    <div data-broker-rows>
        @foreach ($brokerRows as $brokerIndex => $broker)
            @php
                $broker = array_replace(['key' => 'broker-' . $brokerIndex, 'name' => '', 'evidence_id' => null], is_array($broker) ? $broker : []);
            @endphp
            @include('tenant.marketing-campaigns.partials.broker-row', [
                'broker' => $broker,
                'brokerIndex' => $brokerIndex,
                'brokerFile' => $campaign?->evidence->firstWhere('id', $broker['evidence_id'] ?? null),
            ])
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary" data-add-broker>
        <i class="ti ti-plus me-1"></i> إضافة وسيط
    </button>
    <template data-broker-template>
        @include('tenant.marketing-campaigns.partials.broker-row', [
            'broker' => ['key' => '__KEY__', 'name' => '', 'evidence_id' => null],
            'brokerIndex' => '__INDEX__',
            'brokerFile' => null,
        ])
    </template>
</div>
