@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل النشاط')

@section('content')
    @include('shared.activity-logs._details', [
        'backUrl' => route('admin.activity-logs.index'),
    ])
@endsection
