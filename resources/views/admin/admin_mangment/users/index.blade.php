@extends('admin.layouts.layoutMaster')

@section('title', 'المستحدمين ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-script')
    <script>
       window.routes = {
        massDelete: "{{ route('admin.mass-delete') }}"
     };
    </script>
    @vite(['resources/assets/js/admin/admin_mangment/users/index.js' , 'resources/assets/js/admin/admin_mangment/users/filter.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('content')

     <div id="top_card" class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2">{{ $totalUsers }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المستخدمين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين النشطين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمين النشطين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين غير النشطين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactiveUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمين غير النشطين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-off ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين المؤرشفين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمين المحذوفين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-archive ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
        <div class="filters row g-3">
              

                <div class="col-md-6">
                    <label for="filter-status">حالة المستخدمين</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="حالة المستخدمين">
                        <option value=""></option>
                        {{-- @foreach ($advanceStatus as $status) --}}
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        {{-- @endforeach --}}
                    </select>
                </div>

                <div class="col-md-6">
                <label for="filter-role">دور المستخدم</label>
                <select id="filter-role" class="form-control select2" data-placeholder="اختر الدور">
                    <option value=""></option>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            
            </div> 
            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>
@endsection
