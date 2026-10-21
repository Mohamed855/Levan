@extends('layouts.dashboard')
@section('title', 'إنشاء مزاد')
@section('dashboard-scope', 'tenant')
@section('head')@parent @vite('resources/assets/js/tenant/auctions/form.js')@endsection
@section('content')<form method="POST" action="{{ route('tenant.auctions.store') }}" enctype="multipart/form-data" data-wizard-resource="tenant.auctions.create" data-wizard-url="{{ route('tenant.wizard-drafts.index') }}">@csrf<input type="hidden" name="_wizard_draft_id" data-wizard-draft-id><input type="hidden" name="_wizard_resource_key" value="tenant.auctions.create">@include('tenant.auctions.partials.form')</form><x-shared.wizard.draft-modal resource-key="tenant.auctions.create" />@endsection
@push('dashboard-scripts')@vite('resources/assets/js/shared/wizard-draft.js')@endpush