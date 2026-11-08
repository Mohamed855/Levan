@extends('tenant.layouts.layoutMaster')

@section('title', $campaign->exists ? 'تعديل حملة تسويقية' : 'إضافة حملة تسويقية')

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
    <x-ui.validation-errors />
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $campaign->exists ? 'تعديل بيانات الحملة' : 'بيانات الحملة التسويقية' }}</h5></div>
                <div class="card-body">
                    <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                        action="{{ $campaign->exists ? tenant_route_name('marketing-campaigns.update', $campaign) : tenant_route_name('marketing-campaigns.store') }}"
                        data-campaign-metrics='@json(config("auction-marketing-campaigns"))'
                        data-campaign-fields='@json(config("auction-marketing-fields"))'
                        data-campaign-properties='@json($propertyOptions)'>
                        @csrf
                        @if($campaign->exists) @method('PUT') @endif
                        <div class="row g-4">
                            <x-form.controls.select name="auction_id" id="campaign-auction" label="المزاد" :options="$auctionOptions" :value="old('auction_id', $campaign->auction_id)" required col="col-md-6" />
                            <x-form.controls.select name="channel" id="campaign-channel" label="نوع الحملة" :options="collect(__('auction_reports.channels', [], 'ar'))->only(array_keys(config('auction-marketing-campaigns')))->all()" :value="old('channel', $campaign->channel)" required col="col-md-6" />
                            <x-form.controls.input name="campaign_date" label="تاريخ الحملة" type="date" :value="old('campaign_date', $campaign->campaign_date?->format('Y-m-d'))" col="col-md-6" />
                            <x-form.controls.input name="url" type="url" label="رابط الحملة" :value="old('url', $campaign->url)" col="col-md-6" />
                            <x-form.controls.select name="metrics[property_id]" label="العقار" :options="collect($propertyOptions)->collapse()->all()" :value="old('metrics.property_id', $campaign->metrics['property_id'] ?? null)" required data-campaign-metric="property_id" col="col-md-6" />
                            <div class="col-12"><p class="text-muted mb-0">أدخل البيانات المتاحة. الصفر قيمة مسجلة؛ اترك المؤشر غير المتاح فارغًا.</p></div>
                            @foreach(array_unique(array_merge(...array_values(config('auction-marketing-campaigns')))) as $metric)
                                @continue($metric === 'property_id')
                                @if(array_key_exists($metric, config('auction-marketing-fields.text')))
                                    <x-form.controls.textarea :name="'metrics['.$metric.']'" :label="config('auction-marketing-fields.text.'.$metric)" :value="old('metrics.'.$metric, $campaign->metrics[$metric] ?? null)" :data-campaign-metric="$metric" col="col-12" rows="3" />
                                @else
                                <x-form.controls.input :name="'metrics['.$metric.']'" :label="$metric === 'views' ? 'عدد المشاهدات' : __('auction_reports.metrics.'.$metric, [], 'ar')" type="number"
                                    :value="old('metrics.'.$metric, $campaign->metrics[$metric] ?? null)" :data-campaign-metric="$metric" col="col-md-4"
                                    :step="in_array($metric, config('auction-report-inputs.integer_metrics')) ? '1' : 'any'" :min="in_array($metric, config('auction-report-inputs.signed_metrics')) ? null : 0" />
                                @endif
                            @endforeach
                            <x-form.controls.file name="evidence[]" label="أدلة الحملة" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" help="صور أو PDF، حتى 10MB للملف" col="col-12" />
                            @foreach($campaign->evidence as $file)
                                <div class="col-md-6">
                                    <x-ui.file-card :file="$file" />
                                    <x-form.controls.checkbox name="remove_evidence[]" :id="'remove-evidence-'.$file->id" :value="$file->id" label="حذف هذا الدليل عند الحفظ" col="mt-3" />
                                </div>
                            @endforeach
                        </div>
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('marketing-campaigns.index')" text="العودة" />
                            <x-form.wizard.save-button />
                        </x-form.wizard.actions>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
