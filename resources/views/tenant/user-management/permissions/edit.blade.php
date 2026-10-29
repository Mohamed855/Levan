@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الصلاحية')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
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

                    <form action="{{ tenant_route_name('permissions.update', $permission->id) }}" method="POST"
                        data-form-validate>
                        @csrf
                        @method('PUT')
                        @include('tenant.user-management.permissions.partials.form', ['permission' => $permission])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
