@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل العقار - ' . $property->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    <script>
        window.routes = window.routes || {};
        window.propertyUnitStatuses = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitStatus::cases()));
        window.propertyUnitTypes = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitType::cases()));
        window.propertyUnitUsages = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitUsage::cases()));
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/properties/create.js'])
@endsection

@section('content')
    @include('tenant.properties.partials._form', [
        'is_edit' => true,
        'property' => $property,
        'generatedPropertyNumber' => $property->property_number,
        'assignments' => $assignments,
        'cityOptions' => $cityOptions,
    ])
@endsection
