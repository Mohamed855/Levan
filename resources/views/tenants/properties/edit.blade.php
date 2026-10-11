@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل العقار')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
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
            ['id' => 'property-basic-validation', 'title' => 'بيانات العقار والإسناد', 'subtitle' => 'الموقع والنوع والإسناد التابع'],
            ['id' => 'property-specs-validation', 'title' => 'المواصفات والبيانات المالية', 'subtitle' => 'المساحة والصك والقيمة'],
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

                <form id="wizard-validation-form" action="{{ tenant_route_name('properties.update', $property->id) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="property-basic-validation" :fields="['assignment_id', 'property_number', 'name', 'type', 'city', 'district', 'main_image']">
                        <div class="row g-6">
                            <x-form.controls.select name="assignment_id" label="الإسناد التابع له" required col="col-md-6"
                                :value="$property->assignment_id"
                                :options="$assignments->map(fn($a) => ['value' => $a->id, 'label' => $a->name . ' (' . $a->assignment_number . ')'])->toArray()" />

                            <x-form.controls.input name="property_number" label="رقم العقار" :value="$property->property_number" required col="col-md-6" />

                            <x-form.controls.input name="name" label="اسم العقار" :value="$property->name" required col="col-md-6" />

                            <x-form.controls.select name="type" label="نوع العقار" :value="$property->type" required col="col-md-6" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />

                            <x-form.controls.select name="city" label="المدينة" :value="$property->city" required col="col-md-6" :options="$cityOptions" />

                            <x-form.controls.input name="district" label="الحي" :value="$property->district" col="col-md-6" />

                            <x-form.controls.image-upload name="main_image" label="صورة العقار"
                                :value="$property->main_image ? tasset($property->main_image) : null"
                                accept="image/jpeg,image/png,image/webp"
                                hint="JPG، PNG أو WebP. الحد الأقصى 4MB" col="col-12" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="property-specs-validation" :fields="['area', 'deed_number', 'estimated_value', 'data_status', 'notes']">
                        <div class="row g-6">
                            <x-form.controls.input name="area" type="number" step="0.01" label="المساحة (م²)" :value="$property->area" required col="col-md-6" />

                            <x-form.controls.input name="deed_number" label="رقم الصك" :value="$property->deed_number" col="col-md-6" />

                            <x-form.controls.input name="estimated_value" type="number" step="0.01" label="القيمة التقديرية (ر.س)" :value="$property->estimated_value" col="col-md-6" />

                            <x-form.controls.select name="data_status" label="حالة اكتمال البيانات" :value="$property->data_status" col="col-md-6" :enum="\App\Enums\Tenants\Properties\PropertyDataStatus::class" />

                            <x-form.controls.textarea name="notes" label="ملاحظات العقار" :value="$property->notes" col="col-12" rows="3" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.save-button text="تحديث العقار" />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
