@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة صلاحية جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection


@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <h5 class="card-header">إضافة صلاحية جديدة</h5>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ tenant_route_name('permissions.store') }}" method="POST" data-form-validate>
                        @csrf
                        <div class="row g-4 mb-4">
                            <x-form.controls.input name="name" id="name" label="اسم الصلاحية"
                                :value="old('name')" required col="col-md-6" />
                        </div>
                        <div class="d-flex gap-2">
                            <x-form.wizard.draft-button />
                            <button type="submit" class="btn btn-primary">إضافة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
