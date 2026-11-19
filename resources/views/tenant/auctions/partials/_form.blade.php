@php
    $is_edit = $is_edit ?? false;
    $auction = $auction ?? null;
    $steps = [
        ['id' => 'step-basic-info', 'title' => 'الأساسيات'],
        ['id' => 'step-assignment', 'title' => 'المنصة والأموال'],
        ['id' => 'step-properties', 'title' => 'العقارات'],
        ['id' => 'step-marketing', 'title' => 'التسويق'],
        ['id' => 'step-reports-attachments', 'title' => 'المرفقات والإفصاحات'],
        ['id' => 'step-review', 'title' => 'المراجعة'],
    ];
@endphp

<x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
    <form id="{{ $is_edit ? 'auction-edit-form' : 'wizard-validation-form' }}"
        action="{{ $is_edit ? tenant_route_name('auctions.update', $auction->id) : tenant_route_name('auctions.store') }}"
        method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
        @if (!$is_edit) data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
            data-draft-resource-key="tenant.auction-create" @endif
        data-assignment-properties-url="{{ tenant_route_name('assignments.properties', ':id') }}"
        data-property-image-fallback="{{ asset('static/brand/logos/logo.svg') }}">
        @csrf
        @if ($is_edit)
            @method('PUT')
        @endif

        {{-- الخطوة 1: الأساسيات --}}
        <x-form.wizard.step id="step-basic-info" :fields="[
            'name',
            'description',
            'assignment_id',
            'supervising_entity',
            'start_date',
            'start_time',
            'end_date',
            'end_time',
            'type',
            'category',
            'city',
            'expected_properties_count',
            'auctioneer',
        ]">
            @include('tenant.auctions.partials.steps.step1-basic-info', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button :href="tenant_route_name('auctions.index')" />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 2: المنصة والأموال --}}
        <x-form.wizard.step id="step-assignment" :fields="[
            'platform_entity_id',
            'platform_url',
            'project_manager_name',
            'award_period',
            'venue',
            'funds',
            'notes',
        ]">
            @include('tenant.auctions.partials.steps.step2-assignment', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 3: العقارات --}}
        <x-form.wizard.step id="step-properties" :fields="['property_ids', 'properties_data']">
            @include('tenant.auctions.partials.steps.step3-properties', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button :disabled="!($is_edit && $auction->properties && $auction->properties->isNotEmpty())" />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 4: التسويق --}}
        <x-form.wizard.step id="step-marketing" :fields="[
            'marketing.city_context',
            'marketing.planning_methodology',
            'marketing.brochure_methodology',
            'marketing.audience_methodology',
            'marketing.messages_methodology',
            'marketing.brochure_url',
            'marketing.brochure_context',
            'brochure_evidence',
            'platform_evidence',
            'marketing.board_url',
            'marketing.board_context',
            'marketing.contract_text',
            'marketing.brokers',
            'broker_evidence',
            'channels',
            'campaigns',
        ]">
            @include('tenant.auctions.partials.steps.step4-marketing', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 5: المرفقات والإفصاحات --}}
        <x-form.wizard.step id="step-reports-attachments" :fields="[
            'marketing.introduction',
            'marketing.report_summary',
            'report_image',
            'cover_image',
            'attachments',
            'disclosures',
        ]">
            @include('tenant.auctions.partials.steps.step4-reports-attachments', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 6: المراجعة --}}
        <x-form.wizard.step id="step-review" :fields="[]">
            @include('tenant.auctions.partials.steps.step7-review', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.save-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>
    </form>
</x-form.wizard.stepper>
