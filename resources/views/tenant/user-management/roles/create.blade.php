@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة دور جديد')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <h5 class="card-header">إضافة دور جديد</h5>
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

                    <form action="{{ tenant_route_name('roles.store') }}" method="POST" novalidate data-form-validate
                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-resource-key="tenant.role-create">
                        @csrf
                        @include('tenant.user-management.roles.partials.form', ['role' => null])
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
