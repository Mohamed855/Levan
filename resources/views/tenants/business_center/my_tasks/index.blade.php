@extends('tenants.layouts.layoutMaster')

@section('title', 'مهامي')

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

    {{-- Filters Card --}}
    <x-data-table.filters table="#my-tasks-table">
        <div>
            <x-filter.select
                id="filter-company"
                filterKey="company_id"
                table="#my-tasks-table"
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
                table="#my-tasks-table"
                name="portfolio_id"
                label="المحفظة"
                placeholder="اختر المحفظة"
                :options="$portfolios->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-task-type"
                filterKey="task_type"
                table="#my-tasks-table"
                name="task_type"
                label="نوع المهمة"
                placeholder="اختر نوع المهمة"
                :options="[
                    'promise' => 'وعد بالسداد',
                    'callback' => 'معاودة اتصال',
                    'document_request' => 'طلب مستندات'
                ]"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#my-tasks-table"
                name="status"
                label="الحالة"
                placeholder="اختر الحالة"
                :options="[
                    'pending' => 'معلقة',
                    'completed' => 'مكتملة',
                    'overdue' => 'متأخرة'
                ]"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-overdue-days"
                filterKey="overdue_days"
                table="#my-tasks-table"
                name="overdue_days"
                label="أيام التأخير"
                placeholder="اختر الفترة"
                :options="[
                    '1' => 'أمس فقط',
                    '3' => 'آخر 3 أيام',
                    '7' => 'آخر أسبوع',
                    '30' => 'آخر شهر',
                    'custom' => 'نطاق مخصص'
                ]"
            />
        </div>

        <div id="custom-date-range" class="d-none">
            <label class="form-label">نطاق تاريخي مخصص</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="date" id="filter-date-from" class="form-control" placeholder="من تاريخ" data-filter-key="date_from" data-datatable-filter="true" data-datatable-target="#my-tasks-table">
                </div>
                <div class="col-6">
                    <input type="date" id="filter-date-to" class="form-control" placeholder="إلى تاريخ" data-filter-key="date_to" data-datatable-filter="true" data-datatable-target="#my-tasks-table">
                </div>
            </div>
        </div>
    </x-data-table.filters>

    {{-- DataTable Card --}}
    <div class="card" id="card-my-tasks">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'my-tasks-table'], false) !!}
        </div>
    </div>
@endsection
