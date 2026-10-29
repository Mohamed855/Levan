@extends('tenant.layouts.layoutMaster')

@section('title', 'إنشاء مزاد جديد')

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
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            @include('tenant.auctions.partials._form', [
                'is_edit' => false,
                'auction' => null,
                'assignments' => $assignments,
                'cityOptions' => $cityOptions,
            ])
        </div>
    </div>
@endsection
