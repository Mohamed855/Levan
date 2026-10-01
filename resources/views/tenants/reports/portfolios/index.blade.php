@extends('tenants.layouts.layoutMaster')

@section('title', 'تقارير العقود ')

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
    @vite(['resources/assets/js/tenants/business-center/contracts/index.js'])

@endsection

@section('content')
    {{-- <div class="row g-4 mb-4">
        <!-- بطاقات الإحصائيات -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">السلف</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalAdvance }}</h4>
                            </div>
                            <small class="mb-0">إجمالي السلف</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-stack ti-26px"></i>
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
                            <span class="text-heading">بانتظار الموافقة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $pendingAdvance }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي السلف الجديدة </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-hourglass ti-26px"></i>
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
                            <span class="text-heading">السلف المعتمدة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $approvedAdvance }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي السلف المعتمدة</small>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-file-check ti-26px"></i>
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
                            <span class="text-heading">السلف المرفوضة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $rejectedAdvance }}</h4>
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
        </div>
    </div> --}}

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
            {{-- <div class="filters row g-4">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label for="filter-status">حالة السلفة</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر  حالة السلفة">
                        <option value=""></option>
                        @foreach ($advanceStatus as $status)
                            <option value="{{ $status['id'] }}">{{ $status['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div> --}}
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">تقارير المحافظ </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filtersSection"
                    aria-expanded="false">
                    <i class="ti ti-filter me-1"></i>
                    فلاتر البحث
                    <i class="ti ti-chevron-down ms-1"></i>
                </button>
            </div>

            <div class="collapse" id="filtersSection">
                <div class="card-body border-bottom">
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <label for="filter-date-from" class="form-label">من تاريخ</label>
                            <input type="date" id="filter-date-from" class="form-control" placeholder="اختر التاريخ">
                        </div>
                        <div class="col-md-3">
                            <label for="filter-date-to" class="form-label">إلى تاريخ</label>
                            <input type="date" id="filter-date-to" class="form-control" placeholder="اختر التاريخ">
                        </div>

                    </div>


                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label for="filter-debt-from" class="form-label">مديونية من</label>
                            <input type="number" id="filter-debt-from" class="form-control" placeholder="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="filter-debt-to" class="form-label">مديونية إلى</label>
                            <input type="number" id="filter-debt-to" class="form-control" placeholder="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary w-100 d-block">
                                <i class="ti ti-refresh me-1"></i>إعادة تعيين
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
