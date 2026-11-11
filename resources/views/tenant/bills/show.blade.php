@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل الفاتورة #' . $bill->bill_number)

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    @include('shared.bills._details', [
        'isAdmin' => false,
        'backUrl' => tenant_route_name('bills.index'),
    ])
@endsection
