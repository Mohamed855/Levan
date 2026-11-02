@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الجهة المسندة')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('tenant.issuing-entities.partials._form', [
        'is_edit' => true,
        'issuingEntity' => $issuingEntity,
    ])
@endsection
