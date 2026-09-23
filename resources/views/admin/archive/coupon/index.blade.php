@extends('admin.layouts.layoutMaster')

@section('title', 'ارشيف الكوبونات')


 
@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
{!! $dataTable->scripts() !!}
@endsection

@section('toastr')
@vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
@vite(['resources/css/dataTable.css'])
@endsection
@section('page-script')
    <script>
        window.routes = {
            archiveIndex: "{{ route('admin.archives.coupons.index') }}",
            restore: "{{ route('admin.archives.coupons.restore', ['id' => ':id']) }}",
            forceDelete: "{{ route('admin.archives.coupons.force-delete', ['id' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/archive/coupons/index.js'])
@endsection

@section('content')
<div class="row g-4 mb-4">
    <!-- بطاقات الإحصائيات -->
    {{-- <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">الباقات</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $totalPlans }}</h4>
                        </div>
                        <small class="mb-0">إجمالي الباقات</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-package ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">الباقات النشطة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $activePlans }}</h4>
                        </div>
                        <small class="mb-0"> إجمالي الباقات النشطة </small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-bolt ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">الباقات الغير نشطة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $inactivePlans }}</h4>
                        </div>
                        <small class="mb-0"> إجمالي الباقات الغير نشطة</small>

                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-circle-off ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">السلف المرفوضة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">4</h4>
                            </div>
                            <small class="mb-0"> إجمالي السلف المرفوضة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-circle-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
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
        {{-- <div class="filters row g-3"> --}}
            {{-- <div class="col-md-4">
                    <label for="filter-employee">الموظف</label>
                    <select id="filter-employee" class="form-control select2" data-placeholder="اختر الموظف">
                        <option value=""></option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter-type">نوع السلفة</label>
            <select id="filter-type" class="form-control select2" data-placeholder="اختر نوع السلفة">
                <option value=""></option>
                @foreach ($advanceTypes as $type)
                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                @endforeach
            </select>
        </div> 
        <div class="col-md-6">
            <label for="filter-status">حالة الباقات</label>
            <select id="filter-status" class="form-control select2" data-placeholder="اختر  حالة الباقة">
                <option value=""></option>
                {{-- @foreach ($advanceStatus as $status) --}}
                {{-- <option value="1">نشط</option>
                <option value="0">غير نشط</option> --}}
                {{-- @endforeach --}}
            {{-- </select>
        </div>

        <div class="col-md-6">
            <label for="filter-domain">حالة الدومينات</label>
            <select id="filter-domain" class="form-control select2" data-placeholder="اختر  حالة الدومين">
                <option value=""></option> --}}
                {{-- @foreach ($advanceStatus as $status) --}}
                {{-- <option value="1">نشط</option>
                <option value="0">غير نشط</option> --}}
                {{-- @endforeach --}}
            {{-- </select>
        </div>
    </div>
    <hr class="mt-10"> --}}
    {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
</div>
</div>
@endsection
