@isset($pageConfigs)
{!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = App\Helpers\Helpers::appClasses();

/* Display elements */
$customizerHidden = ($customizerHidden ?? '');

@endphp

@extends('tenants/layouts/commonMaster' )

@section('layoutContent')

<!-- Content -->
@yield('content')
<!--/ Content -->

@endsection
