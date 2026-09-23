@extends('tenants.layouts.layoutMaster')

@section('title', 'مهامي')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])

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
            massDelete: "{{ tenant_route_name('mass-delete') }}",
        };
        window.csrfToken = "{{ csrf_token() }}";
</script>
@vite(['resources/assets/js/tenants/business-center/my-tasks/index.js'])
@endsection

@section('content')
@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Statistics Cards --}}
<div id="top_card" class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي مهامي</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $totalTasks }}</h4>
                        </div>
                        <small class="mb-0">جميع المهام الخاصة بي</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-clipboard-list ti-26px"></i>
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
                        <span class="text-heading">المهام المعلقة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $pendingTasks }}</h4>
                        </div>
                        <small class="mb-0">المهام قيد الانتظار</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-clock ti-26px"></i>
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
                        <span class="text-heading">المهام المكتملة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $completedTasks }}</h4>
                        </div>
                        <small class="mb-0">المهام المنجزة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-check ti-26px"></i>
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
                        <span class="text-heading">المهام المتأخرة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $overdueTasks }}</h4>
                        </div>
                        <small class="mb-0">تجاوزت موعدها</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-alert-triangle ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- My Tasks Table --}}
<div class="card" id="card-my-tasks">
    <div class="card-body">
        {{-- Filters --}}
        <div class="filters row g-3 mb-4">
            <div class="col-md-3">
                <label for="filter-company" class="form-label">الشركة</label>
                <select id="filter-company" class="form-control select2" data-placeholder="اختر الشركة">
                    <option value=""></option>
                    @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-portfolio" class="form-label">المحفظة</label>
                <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة">
                    <option value=""></option>
                    @foreach ($portfolios as $portfolio)
                    <option value="{{ $portfolio->id }}">{{ $portfolio->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-task-type" class="form-label">نوع المهمة</label>
                <select id="filter-task-type" class="form-control select2" data-placeholder="اختر نوع المهمة">
                    <option value=""></option>
                    <option value="promise">وعد بالسداد</option>
                    <option value="callback">معاودة اتصال</option>
                    <option value="document_request">طلب مستندات</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-status" class="form-label">الحالة</label>
                <select id="filter-status" class="form-control select2" data-placeholder="اختر الحالة">
                    <option value=""></option>
                    <option value="pending">معلقة</option>
                    <option value="completed">مكتملة</option>
                    <option value="overdue">متأخرة</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-overdue-days" class="form-label">أيام التأخير</label>
                <select id="filter-overdue-days" class="form-control select2" data-placeholder="اختر الفترة">
                    <option value=""></option>
                    <option value="1">أمس فقط</option>
                    <option value="3">آخر 3 أيام</option>
                    <option value="7">آخر أسبوع</option>
                    <option value="30">آخر شهر</option>
                    <option value="custom">نطاق مخصص</option>
                </select>
            </div>

            <div id="custom-date-range" class="col-md-2 d-none">
                <label class="form-label">نطاق تاريخي مخصص</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="date" id="filter-date-from" class="form-control" placeholder="من تاريخ">
                    </div>
                    <div class="col-md-6">
                        <input type="date" id="filter-date-to" class="form-control" placeholder="إلى تاريخ">
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-8">

        {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'my-tasks-table'], false)
        !!}
    </div>
</div>
@endsection