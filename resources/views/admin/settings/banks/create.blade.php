@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة بنك جديد')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i class="ti ti-building-bank me-2"></i>
                إضافة بنك جديد
            </h4>
            <a href="{{ route('admin.settings.banks.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i>
                العودة للقائمة
            </a>
        </div>

        <!-- Form Card -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.banks.store') }}" method="POST" enctype="multipart/form-data"
                    data-form-validate data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-file-url="{{ route('admin.wizard-drafts.file.destroy') }}"
                    data-draft-resource-key="admin.bank-create">
                    @csrf
                    @include('admin.settings.banks.partials.form', ['bank' => null])
                </form>
            </div>
        </div>
    </div>
@endsection
