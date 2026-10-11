@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل الإسناد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
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
                'subtitle' => 'معلومات الإسناد والجهة',
            ],
            [
                'id' => 'assignment-period-validation',
                'title' => 'المدة والملاحظات',
                'subtitle' => 'الفترة الزمنية والملاحظات',
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

                    <x-form.wizard.step id="assignment-info-validation" :fields="['assignment_number', 'name', 'issuing_entity', 'city', 'category', 'entity_logo']">
                        <div class="row g-6">
                            <div class="col-md-6" data-form-field data-field-name="assignment_number">
                                <label for="assignment_number" class="form-label">رقم الإسناد <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" id="assignment_number"
                                    name="assignment_number"
                                    value="{{ old('assignment_number', $assignment->assignment_number) }}"
                                    style="text-transform: uppercase;" data-form-control required>
                                <div id="assignment_number-error" class="invalid-feedback d-block" data-form-error
                                    role="alert" hidden></div>
                            </div>

                            <x-form.controls.input name="name" label="اسم الإسناد" :value="$assignment->name" required
                                col="col-md-6" />

                            <x-form.controls.select name="category" label="نوع الإسناد" :value="$assignment->category" required
                                col="col-md-6" :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />

                            <x-form.controls.select name="status" label="حالة الإسناد" :value="$assignment->status" required
                                col="col-md-6" :enum="\App\Enums\Shared\ActivityStatus::class" />

                            <x-form.controls.input name="issuing_entity" label="الجهة المسندة" :value="$assignment->issuing_entity" required
                                col="col-md-6" />

                            <x-form.controls.select name="city" label="المدينة الرئيسية" :value="$assignment->city" col="col-md-6"
                                :options="$cityOptions" />

                            <x-form.controls.image-upload name="entity_logo" label="شعار الجهة أو صورة الإسناد"
                                :value="$assignment->entity_logo ? tasset($assignment->entity_logo) : null" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                hint="JPG، PNG أو WebP أو SVG. الحد الأقصى 2MB" col="col-md-6" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="assignment-period-validation" :fields="['start_date', 'end_date', 'notes']">
                        <div class="row g-6">
                            <x-form.controls.input name="start_date" type="date" label="تاريخ بداية الإسناد"
                                :value="$assignment->start_date?->format('Y-m-d')" col="col-md-6" />
                            <x-form.controls.input name="end_date" type="date" label="تاريخ نهاية الإسناد"
                                :value="$assignment->end_date?->format('Y-m-d')" col="col-md-6" />
                            <x-form.controls.textarea name="notes" label="الملاحظات والتفاصيل" :value="$assignment->notes"
                                col="col-12" rows="4" />

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
