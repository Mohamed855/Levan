@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل امتداد دومين')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تعديل امتداد دومين</h5>
                    <a href="{{ route('admin.settings.domains.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.domains.update', $domain->id) }}" method="POST"
                        data-form-validate>
                        @csrf
                        @method('PUT')
                        @include('admin.settings.domains.partials.form', ['domain' => $domain])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
