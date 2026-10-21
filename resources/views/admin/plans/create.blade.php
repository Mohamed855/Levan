@extends('layouts.dashboard')
@section('title', 'إنشاء باقة')
@section('dashboard-scope', 'admin')
@section('content')
<form method="POST" action="{{ route('admin.plans.store') }}" data-wizard-resource="admin.plans.create" data-wizard-url="{{ route('admin.wizard-drafts.index') }}">
    @csrf
    <input type="hidden" name="_wizard_draft_id" data-wizard-draft-id>
    <input type="hidden" name="_wizard_resource_key" value="admin.plans.create">
    @include('admin.plans.partials.form')
</form>
<x-shared.wizard.draft-modal resource-key="admin.plans.create" />
@endsection

@push('dashboard-scripts')
    @vite('resources/assets/js/shared/wizard-draft.js')
@endpush
