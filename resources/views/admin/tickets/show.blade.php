@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/tickets-show.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
    @include('shared.tickets._details', [
        'isAdmin' => true,
        'backUrl' => route('admin.tickets.index'),
        'replyAction' => route('admin.tickets.reply', $ticket->ticket_number),
        'sectionName' => $ticket->section?->name ?? 'الدعم الفني',
    ])
@endsection

@section('page-script')
    @include('shared.tickets._attachment-script')
@endsection
