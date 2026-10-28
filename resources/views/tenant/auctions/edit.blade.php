@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل المزاد - ' . $auction->name)

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/select2/select2.js',
    ])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/auctions/create.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('wizard-validation-form');
            if (form) {
                const initialData = {
                    assignment_id: '{{ $auction->assignment_id }}',
                    property_ids: @json($auction->properties->pluck('id')->map(fn($id) => (string)$id)->all())
                };
                $(form).trigger({
                    type: 'wizardDraftRestored',
                    detail: { data: initialData }
                });
            }
        });
    </script>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية والإسناد'],
            ['id' => 'step-assignment', 'title' => 'المنصة والمشروع'],
            ['id' => 'step-properties', 'title' => 'العقارات المرتبطة'],
            ['id' => 'step-settings', 'title' => 'النتائج والملفات'],
            ['id' => 'step-review', 'title' => 'المراجعة والحفظ'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
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

                <form id="wizard-validation-form" action="{{ tenant_route_name('auctions.update', $auction->id) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-assignment-properties-url="{{ tenant_route_name('assignments.properties', ':id') }}"
                    data-property-image-fallback="{{ asset('static/brand/logos/logo.svg') }}">
                    @csrf
                    @method('PUT')

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
                            <x-form.wizard.previous-button :href="tenant_route_name('auctions.index')" text="العودة" />
                            <div class="d-flex gap-2">
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
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 4: النتائج والتسويق والملفات --}}
                    <x-form.wizard.step id="step-settings" :fields="['report_image', 'cover_image', 'property_results', 'marketing', 'attachments', 'marketing_evidence']">
                        @include('tenant.auctions.partials.steps.step4-settings', ['auction' => $auction])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المراجعة والحفظ --}}
                    <x-form.wizard.step id="step-review" :fields="['status']">
                        @include('tenant.auctions.partials.steps.step5-review', ['auction' => $auction])

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <button type="submit" name="status" value="{{ $auction->status->value ?? 'under_review' }}" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> حفظ التعديلات
                                </button>
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
