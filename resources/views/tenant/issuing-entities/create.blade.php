@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة جهة مسندة جديدة')


@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('tenant.issuing-entities.partials._form', [
        'is_edit' => false,
        'issuingEntity' => $issuingEntity ?? new \App\Models\Tenants\Assignments\IssuingEntity(['is_active' => true]),
    ])
@endsection
