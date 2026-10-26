@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الدور')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <h5 class="card-header">تعديل الدور</h5>
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

                    <form action="{{ tenant_route_name('roles.update', $role->id) }}" method="POST" novalidate
                        data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                        @csrf
                        @method('PUT')
                        @include('tenant.user-management.roles.partials.form', ['role' => $role])
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
