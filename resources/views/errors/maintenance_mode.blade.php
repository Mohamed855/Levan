@extends('layouts.error')

@section('title', 'تحت الصيانة | 503')
@section('code', '503')
@section('badge')
    <i class="ti ti-barrier-block"></i>
    <span>{{ __('أعمال صيانة') }}</span>
@endsection
@section('heading', 'نقوم حالياً بأعمال صيانة 🚧')
@section('message', 'نعتذر عن الإزعاج، نحن نعمل على تحسين وتطوير النظام في الوقت الحالي. يرجى المحاولة لاحقاً.')
