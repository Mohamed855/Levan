@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الإسناد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @php
        $steps = [
            [
                'id' => 'assignment-info-validation',
                'title' => 'البيانات الأساسية',
            ],
            [
                'id' => 'assignment-period-validation',
                'title' => 'المدة والملاحظات',
            ],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="wizard-validation-form" action="{{ tenant_route_name('assignments.update', $assignment->id) }}"
                    method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="assignment-info-validation" :fields="['assignment_number', 'name', 'issuing_entity', 'city', 'district', 'category', 'assignment_image']">
                        <div class="row g-6">
                            <x-form.controls.input name="assignment_number" label="رقم الإسناد"
                                :value="$assignment->assignment_number" required col="col-md-6"
                                class="font-monospace text-uppercase" />

                            <x-form.controls.input name="name" label="اسم الإسناد" :value="$assignment->name" required
                                col="col-md-6" />

                            <x-form.controls.select name="category" label="تصنيف الإسناد" :value="$assignment->category" required
                                col="col-md-6" :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />

                            <x-form.controls.select name="status" label="حالة الإسناد" :value="$assignment->status" required
                                col="col-md-6" :enum="\App\Enums\Tenants\Assignments\AssignmentStatus::class" />

                            <x-form.controls.input name="issuing_entity" label="الجهة المسندة" :value="$assignment->issuing_entity" required
                                col="col-md-6" />

                            <x-form.controls.select name="city" label="المدينة" :value="$assignment->city" col="col-md-6"
                                :options="$cityOptions" />

                            <x-form.controls.input name="district" label="النطاق/الحي" :value="$assignment->district" col="col-md-6" />

                            <x-form.controls.image-upload name="assignment_image" label="صورة الإسناد"
                                :value="$assignment->assignmentImage ? tasset($assignment->assignmentImage->file_path) : null" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                hint="JPG، PNG أو WebP أو SVG. الحد الأقصى 2MB" col="col-md-6" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button :href="tenant_route_name('assignments.index')" text="العودة" />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="assignment-period-validation" :fields="['start_date', 'end_date', 'notes']">
                        <div class="row g-6">
                            <x-form.controls.input name="start_date" type="date" label="تاريخ البداية"
                                :value="$assignment->start_date?->format('Y-m-d')" col="col-md-6" />
                            <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية"
                                :value="$assignment->end_date?->format('Y-m-d')" col="col-md-6" />
                            <x-form.controls.textarea name="notes" label="وصف الإسناد" :value="$assignment->notes"
                                col="col-md-6" rows="4" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.save-button text="تحديث الإسناد" />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
