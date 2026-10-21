@extends('layouts.dashboard')
@section('title', 'إنشاء قالب مزاد')
@section('dashboard-scope', 'admin')
@section('head')@parent @vite(['resources/assets/scss/shared/template-designer.scss', 'resources/assets/js/admin/auction-templates/designer.js'])@endsection
@section('content')<form method="POST" action="{{ route('admin.auction-templates.store') }}" data-wizard-resource="admin.auction-templates.create" data-wizard-url="{{ route('admin.wizard-drafts.index') }}">@csrf<input type="hidden" name="_wizard_draft_id" data-wizard-draft-id><input type="hidden" name="_wizard_resource_key" value="admin.auction-templates.create">@include('admin.auction-templates.partials.form')</form><x-shared.wizard.draft-modal resource-key="admin.auction-templates.create" />@endsection
@push('dashboard-scripts')@vite('resources/assets/js/shared/wizard-draft.js')@endpush
