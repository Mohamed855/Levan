@extends('tenant.layouts.layoutMaster')

@section('title', 'طلب إقامة مزاد: ' . $auction->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/auction-establishment-requests/edit.js'])
@endsection

@section('content')

    @php
        $sections = $schema->sections();
        $steps = [
            ['id' => 'step-organization', 'title' => 'المنشأة ومسؤولو المزاد'],
            ['id' => 'step-auction-funds', 'title' => 'تفاصيل المزاد والأموال'],
            ['id' => 'step-establishment-properties', 'title' => 'بيانات العقارات'],
            ['id' => 'step-disclosures', 'title' => 'الإفصاحات والتعهدات'],
            ['id' => 'step-attachments', 'title' => 'المرفقات والمستندات'],
            ['id' => 'step-review', 'title' => 'المراجعة'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                <form id="establishment-request-form"
                    action="{{ tenant_route_name('auction-establishment-requests.update', $auction) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                    data-draft-resource-key="tenant.auction-establishment-request.{{ $auction->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_wizard_resource_key" value="tenant.auction-establishment-request.{{ $auction->id }}">
                    <input type="hidden" name="resource_key" value="tenant.auction-establishment-request.{{ $auction->id }}">
                    <input type="hidden" name="target_path" value="{{ parse_url(tenant_route_name('auction-establishment-requests.update', $auction), PHP_URL_PATH) }}">

                    {{-- الخطوة 1: المنشأة ومسؤولو المزاد --}}
                    <x-form.wizard.step id="step-organization" :fields="['data.organization', 'data.manager', 'data.auctioneer']">
                        @include('tenant.auction-establishment-requests.steps.step1-organization', [
                            'sections' => $sections,
                            'data' => $data,
                            'auction' => $auction,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 2: تفاصيل إقامة المزاد والأموال --}}
                    <x-form.wizard.step id="step-auction-funds" :fields="['data.auction', 'data.funds']">
                        @include('tenant.auction-establishment-requests.steps.step2-auction-funds', [
                            'sections' => $sections,
                            'schema' => $schema,
                            'auction' => $auction,
                            'data' => $data,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 3: بيانات العقارات --}}
                    <x-form.wizard.step id="step-establishment-properties" :fields="['data.properties']">
                        @include('tenant.auction-establishment-requests.steps.step3-properties', [
                            'auction' => $auction,
                            'schema' => $schema,
                            'data' => $data,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button :disabled="$auction->properties->isEmpty()" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 4: الإفصاحات والتعهدات --}}
                    <x-form.wizard.step id="step-disclosures" :fields="['data.disclosures', 'data.declaration']">
                        @include('tenant.auction-establishment-requests.steps.step4-disclosures', [
                            'sections' => $sections,
                            'schema' => $schema,
                            'data' => $data,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المرفقات والمستندات (مستقلة بالكامل) --}}
                    <x-form.wizard.step id="step-attachments" :fields="['attachments']">
                        @include('tenant.auction-establishment-requests.steps.step5-attachments', [
                            'auction' => $auction,
                            'schema' => $schema,
                            'data' => $data,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 6: المراجعة --}}
                    <x-form.wizard.step id="step-review" :fields="['declaration_accepted']">
                        @include('tenant.auction-establishment-requests.steps.step6-review', [
                            'auction' => $auction,
                            'schema' => $schema,
                            'data' => $data,
                        ])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>

                {{-- نافذة منبثقة موحدة لاستكمال وتعديل بيانات ومرفقات العقار (خارج النموذج لمنع إرسال مدخلاتها المؤقتة مع مسودة النموذج) --}}
                @include('tenant.auction-establishment-requests.steps._property-modal', [
                    'schema' => $schema,
                ])
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
