@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة دور جديد')

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-automatic-gearbox text-warning me-2"></i>
                        إضافة دور جديد
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.roles.store') }}" method="POST" data-form-validate
                        data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                        data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                        data-draft-resource-key="admin.role-create">
                        @csrf
                        @include('admin.roles.partials.form', ['role' => null])
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
