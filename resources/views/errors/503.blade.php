@extends('layouts.error')

@section('title', 'الخدمة غير متوفرة | 503')
@section('code', '503')
@section('badge')
    <i class="ti ti-tools"></i>
    <span>{{ __('أعمال صيانة') }}</span>
@endsection
@section('heading', 'الخدمة غير متوفرة حالياً')
@section('message', 'النظام متوقف مؤقتاً لإجراء بعض التحسينات وأعمال الصيانة المجدولة، سنعود للعمل قريباً.')
