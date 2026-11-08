@extends('tenant.layouts.layoutMaster')

@php
    $is_edit = $campaign->exists;
    $steps = [
        [
            'id' => 'campaign-info',
            'title' => 'بيانات الحملة التسويقية',
        ],
    ];
@endphp

@section('title', $is_edit ? 'تعديل حملة تسويقية' : 'إضافة حملة تسويقية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/auctions/cycle-forms.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="campaign-wizard" :steps="$steps">
                <x-ui.validation-errors />

                <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    @if (!$is_edit) data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                        data-draft-resource-key="tenant.marketing-campaign-create" @endif
                    action="{{ $is_edit ? tenant_route_name('marketing-campaigns.update', $campaign) : tenant_route_name('marketing-campaigns.store') }}"
                    data-campaign-metrics='@json(config('auction-marketing-campaigns'))' data-campaign-fields='@json(config('auction-marketing-fields'))'
                    data-campaign-properties='@json($propertyOptions)'>
                    @csrf
                    @if ($is_edit)
                        @method('PUT')
                    @endif

                    <x-form.wizard.step id="campaign-info" :fields="['auction_id', 'channel', 'campaign_date', 'url', 'evidence']">
                        <div class="row g-4">
                            <x-form.controls.select name="auction_id" id="campaign-auction" label="المزاد" :options="$auctionOptions"
                                :value="old('auction_id', $campaign->auction_id)" required col="col-md-6" />
                            <x-form.controls.select name="channel" id="campaign-channel" label="نوع الحملة"
                                :options="collect(__('auction_reports.channels', [], 'ar'))
                                    ->only(array_keys(config('auction-marketing-campaigns')))
                                    ->all()" :value="old('channel', $campaign->channel)" required col="col-md-6" />
                            <x-form.controls.input name="campaign_date" label="تاريخ الحملة" type="date"
                                :value="old('campaign_date', $campaign->campaign_date?->format('Y-m-d'))" col="col-md-6" />
                            <x-form.controls.input name="url" type="url" label="رابط الحملة" :value="old('url', $campaign->url)"
                                col="col-md-6" />
                            <x-form.controls.select name="metrics[property_id]" label="العقار" :options="collect($propertyOptions)->collapse()->all()"
                                :value="old('metrics.property_id', $campaign->metrics['property_id'] ?? null)" required data-campaign-metric="property_id" col="col-md-6" />
                            @foreach (array_unique(array_merge(...array_values(config('auction-marketing-campaigns')))) as $metric)
                                @continue($metric === 'property_id')
                                @if (array_key_exists($metric, config('auction-marketing-fields.text')))
                                    <x-form.controls.textarea :name="'metrics[' . $metric . ']'" :label="config('auction-marketing-fields.text.' . $metric)" :value="old('metrics.' . $metric, $campaign->metrics[$metric] ?? null)"
                                        :data-campaign-metric="$metric" col="col-12" rows="3" />
                                @else
                                    <x-form.controls.input :name="'metrics[' . $metric . ']'" :label="$metric === 'views' ? 'عدد المشاهدات' : __('auction_reports.metrics.'.$metric, [], 'ar')" type="number"
                                        :value="old('metrics.' . $metric, $campaign->metrics[$metric] ?? null)" :data-campaign-metric="$metric" col="col-md-4" :step="in_array($metric, config('auction-report-inputs.integer_metrics'))
                                            ? '1'
                                            : 'any'"
                                        :min="in_array($metric, config('auction-report-inputs.signed_metrics'))
                                            ? null
                                            : 0" />
                                @endif
                            @endforeach
                            <x-form.controls.file name="evidence[]" label="أدلة الحملة" multiple
                                accept=".pdf,.png,.jpg,.jpeg,.webp" help="صور أو PDF، حتى 10MB للملف" col="col-12" />
                            @foreach ($campaign->evidence as $file)
                                <div class="col-md-6">
                                    <x-ui.file-card :file="$file" />
                                    <x-form.controls.checkbox name="remove_evidence[]" :id="'remove-evidence-' . $file->id" :value="$file->id"
                                        label="حذف هذا الدليل عند الحفظ" col="mt-3" />
                                </div>
                            @endforeach
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('marketing-campaigns.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                @if (!$is_edit)
                                    <x-form.wizard.draft-button step="1" />
                                @endif
                                <x-form.wizard.save-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
