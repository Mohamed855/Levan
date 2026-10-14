@extends('tenants.layouts.layoutMaster')

@section('title', 'المهام')

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
            massDelete: "{{ tenant_route_name('mass-delete') }}",
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/business-center/tasks/index.js'])
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
        <div class="col-sm-6 col-xl">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المهام</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalTasks }}</h4>
                            </div>
                            <small class="mb-0">جميع المهام في النظام</small>
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

        <div class="col-sm-6 col-xl">
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

        <div class="col-sm-6 col-xl">
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

        <div class="col-sm-6 col-xl">
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
        <div class="col-sm-6 col-xl">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المهام المستحقة اليوم</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $dueTodayTasks }}</h4>
                            </div>
                            <small class="mb-0">تحتاج متابعة خلال اليوم</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-calendar-event ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#tasks-table">
        <div>
            <x-filter.select
                id="filter-company"
                filterKey="company_id"
                table="#tasks-table"
                name="company_id"
                label="الشركة"
                placeholder="اختر الشركة"
                :options="$companies->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-portfolio"
                filterKey="portfolio_id"
                table="#tasks-table"
                name="portfolio_id"
                label="المحفظة"
                placeholder="اختر المحفظة"
                :options="$portfolios->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-manager"
                filterKey="relationship_manager_id"
                table="#tasks-table"
                name="relationship_manager_id"
                label="مسؤول العلاقات"
                placeholder="اختر مسؤول العلاقات"
                :options="$users->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-task-type"
                filterKey="task_type"
                table="#tasks-table"
                name="task_type"
                label="نوع المهمة"
                placeholder="اختر نوع المهمة"
                :enum="\App\Enums\Tenants\Tasks\TaskType::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#tasks-table"
                name="status"
                label="الحالة"
                placeholder="اختر الحالة"
                :enum="\App\Enums\Tenants\Tasks\TaskStatus::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-overdue-days"
                filterKey="overdue_days"
                table="#tasks-table"
                name="overdue_days"
                label="أيام التأخير"
                placeholder="اختر الفترة"
                :enum="\App\Enums\Tenants\Collections\OverdueDaysRange::class"
            />
        </div>

        <div id="custom-date-range" class="d-none">
            <label class="form-label">نطاق تاريخي مخصص</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="date" id="filter-date-from" class="form-control" placeholder="من تاريخ" data-filter-key="date_from" data-datatable-filter="true" data-datatable-target="#tasks-table">
                </div>
                <div class="col-6">
                    <input type="date" id="filter-date-to" class="form-control" placeholder="إلى تاريخ" data-filter-key="date_to" data-datatable-filter="true" data-datatable-target="#tasks-table">
                </div>
            </div>
        </div>
    </x-data-table.filters>

    <div class="card" id="card-tasks">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tasks-table'], false) !!}
        </div>
    </div>
@endsection
