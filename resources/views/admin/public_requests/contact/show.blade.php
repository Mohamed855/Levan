@extends('admin.layouts.layoutMaster')
@section('title', 'تفاصيل طلب التواصل')
@section('content')
    <div class="row g-4"><div class="col-lg-8"><div class="card"><div class="card-header"><h5 class="mb-0">{{ $contactRequest->customer_name }}</h5></div><div class="card-body"><p><strong>البريد:</strong> {{ $contactRequest->customer_email }}</p><p class="mb-0">{{ $contactRequest->message }}</p></div></div></div><div class="col-lg-4"><div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.contact-requests.reply', $contactRequest) }}" data-form-validate>@csrf<x-form.controls.textarea name="reply" label="الرد" rows="7" required :disabled="$contactRequest->status->value !== 'pending'" /><button class="btn btn-primary" {{ $contactRequest->status->value !== 'pending' ? 'disabled' : '' }}>إرسال الرد</button></form></div></div></div></div>
@endsection
