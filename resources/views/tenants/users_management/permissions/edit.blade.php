@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل الصلاحية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection


@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <h5 class="card-header">تعديل الصلاحية</h5>
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

                    <form action="{{ tenant_route_name('permissions.update', $permission->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم الصلاحية</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name', $permission->name) }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">تحديث</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
