@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل النشاط')

@section('content')
    @include('shared.activity-logs._details', [
        'backUrl' => tenant_route_name('activity-logs.index'),
    ])
@endsection
