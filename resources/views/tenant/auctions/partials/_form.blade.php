@php
    $is_edit = $is_edit ?? false;
    $auction = $auction ?? null;
    $steps = [
        ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية والإسناد'],
        ['id' => 'step-assignment', 'title' => 'المنصة والمشروع'],
        ['id' => 'step-properties', 'title' => 'العقارات المرتبطة'],
        ['id' => 'step-reports-attachments', 'title' => 'التقارير والمرفقات'],
        ['id' => 'step-results-boards', 'title' => 'النتائج واللوحات'],
        ['id' => 'step-campaigns', 'title' => 'الحملات التسويقية'],
        ['id' => 'step-review', 'title' => 'المراجعة والإرسال'],
    ];
@endphp

<x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="{{ $is_edit ? 'auction-edit-form' : 'wizard-validation-form' }}"
        action="{{ $is_edit ? tenant_route_name('auctions.update', $auction->id) : tenant_route_name('auctions.store') }}"
        method="POST"
        enctype="multipart/form-data"
        autocomplete="off"
        data-form-validate
        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
        @if (! $is_edit)
            data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
            data-draft-resource-key="tenant.auction-create"
        @endif
        data-assignment-properties-url="{{ tenant_route_name('assignments.properties', ':id') }}"
        data-property-image-fallback="{{ asset('static/brand/logos/logo.svg') }}">
        @csrf
        @if ($is_edit)
            @method('PUT')
        @endif

        {{-- الخطوة 1: البيانات الأساسية والإسناد --}}
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
        ]">
            @include('tenant.auctions.partials.steps.step1-basic-info', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button :href="tenant_route_name('auctions.index')" />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 2: المنصة وإدارة المشروع --}}
        <x-form.wizard.step id="step-assignment" :fields="['platform_name', 'platform_url', 'project_manager_name', 'notes']">
            @include('tenant.auctions.partials.steps.step2-assignment', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 3: العقارات المرتبطة --}}
        <x-form.wizard.step id="step-properties" :fields="['property_ids']">
            @include('tenant.auctions.partials.steps.step3-properties', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 4: التقارير والمرفقات --}}
        <x-form.wizard.step id="step-reports-attachments" :fields="['marketing.introduction', 'marketing.contacts.phone', 'marketing.contacts.unified_phone', 'marketing.contacts.social', 'marketing.whatsapp_date', 'report_image', 'cover_image', 'attachments']">
            @include('tenant.auctions.partials.steps.step4-reports-attachments', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 5: النتائج واللوحات --}}
        <x-form.wizard.step id="step-results-boards" :fields="['property_results', 'board_images_before', 'board_images_after']">
            @include('tenant.auctions.partials.steps.step5-results-boards', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 6: الحملات التسويقية --}}
        <x-form.wizard.step id="step-campaigns" :fields="['marketing.campaigns', 'marketing.links', 'marketing_evidence']">
            @include('tenant.auctions.partials.steps.step6-campaigns', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>

        {{-- الخطوة 7: المراجعة والإرسال --}}
        <x-form.wizard.step id="step-review" :fields="[]">
            @include('tenant.auctions.partials.steps.step7-review', ['auction' => $auction])

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (! $is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.save-button />
                </div>
            </x-form.wizard.actions>
        </x-form.wizard.step>
    </form>
</x-form.wizard.stepper>
