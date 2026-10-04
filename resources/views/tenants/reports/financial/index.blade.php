@extends('tenants.layouts.layoutMaster')
@section('title', 'تقارير التحصيل المالي')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            financialReportsIndex: "{{ tenant_route_name('reports.financial.index') }}",
            financialReportsExportPDF: "{{ tenant_route_name('reports.financial.export.pdf') }}",
            financialReportsExportExcel: "{{ tenant_route_name('reports.financial.export.excel') }}",
            financialReportsShowExportModal: "{{ tenant_route_name('reports.financial.show-export-modal') }}",
            financialReportsCheckProgress: "{{ tenant_route_name('reports.financial.check-progress') }}",
            financialReportsDownload: "{{ tenant_route_name('reports.financial.download', ['file' => 'FILE_PLACEHOLDER']) }}",
            financialSearchCustomers: "{{ tenant_route_name('reports.financial.search-customers') }}",
            financialGetCustomer: "{{ tenant_route_name('reports.financial.get-customer', ['customer' => 'ID_PLACEHOLDER']) }}",
            financialPortfoliosByCompany: "{{ tenant_route_name('reports.financial.portfolios-by-company') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";

        window.chartData = {
            collectionsByStatus: @json($collections_by_status),
            transactionsByUser: @json($collections_by_user),
            transactionsByMonth: @json($collections_by_month)
        };
    </script>
    @vite(['resources/assets/js/tenants/reports/financial/index.js'])
@endsection

@section('content')
    {{-- الإحصائيات الرئيسية --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي التحصيلات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_collections) }}</h4>
                            </div>
                            <small class="mb-0">تحصيل مسجل</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-receipt ti-26px"></i>
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
                            <span class="text-heading">التحصيلات المعتمدة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($approved_collections) }}</h4>
                            </div>
                            <small class="mb-0">{{ number_format($total_approved_amount, 0) }} ريال</small>
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
                            <span class="text-heading">التحصيلات المعلقة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($pending_collections) }}</h4>
                            </div>
                            <small class="mb-0">{{ number_format($total_pending_amount, 0) }} ريال</small>
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
                            <span class="text-heading">التحصيلات المرفوضة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($rejected_collections) }}</h4>
                            </div>
                            <small class="mb-0">تحصيل مرفوض</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">توزيع التحصيلات حسب الحالة</h5>
                    <i class="ti ti-chart-pie text-primary"></i>
                </div>
                <div class="card-body">
                    <div id="transactionTypeChart"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">المستخدمون حسب المبلغ المحصل</h5>
                    <i class="ti ti-users text-success"></i>
                </div>
                <div class="card-body">
                    <div id="userChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">التحصيلات حسب الشهر</h5>
                    <i class="ti ti-calendar-time text-warning"></i>
                </div>
                <div class="card-body">
                    <div id="monthlyChart"></div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#financial-reports-table" reset-id="reset-filters">
        <div class="col-md-6">
            <label for="filter-company" class="form-label">الشركة</label>
            <select id="filter-company" class="form-control select2" data-datatable-filter="true" data-filter-key="company_id" data-placeholder="جميع الشركات">
                <option value=""></option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="filter-portfolio" class="form-label">المحفظة</label>
            <select id="filter-portfolio" class="form-control select2" data-datatable-filter="true" data-filter-key="portfolio_id" data-placeholder="اختر المحفظة" disabled>
                <option value=""></option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="filter-date-from" class="form-label">من تاريخ التحصيل</label>
            <input type="date" id="filter-date-from" class="form-control" data-datatable-filter="true" data-filter-key="date_from" placeholder="اختر التاريخ">
        </div>
        <div class="col-md-3">
            <label for="filter-date-to" class="form-label">إلى تاريخ التحصيل</label>
            <input type="date" id="filter-date-to" class="form-control" data-datatable-filter="true" data-filter-key="date_to" placeholder="اختر التاريخ">
        </div>
        <div class="col-md-3">
            <label for="filter-created-from" class="form-label">من تاريخ الإنشاء</label>
            <input type="date" id="filter-created-from" class="form-control" data-datatable-filter="true" data-filter-key="created_from" placeholder="اختر التاريخ">
        </div>
        <div class="col-md-3">
            <label for="filter-created-to" class="form-label">إلى تاريخ الإنشاء</label>
            <input type="date" id="filter-created-to" class="form-control" data-datatable-filter="true" data-filter-key="created_to" placeholder="اختر التاريخ">
        </div>

        <div class="col-md-4">
            <label for="filter-customer" class="form-label">العميل</label>
            <select id="filter-customer" class="form-control select2-ajax" data-datatable-filter="true" data-filter-key="customer_id" data-placeholder="ابحث عن العميل برقم الحساب">
                <option value=""></option>
                @foreach (array_slice($customers, 0, 10) as $customer)
                    <option value="{{ $customer['id'] }}">{{ $customer['text'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter-status" class="form-label">حالة التحصيل</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="جميع الحالات">
                <option value=""></option>
                <option value="pending">معلق</option>
                <option value="approved">معتمد</option>
                <option value="rejected">مرفوض</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="filter-created-by" class="form-label">أنشأ بواسطة</label>
            <select id="filter-created-by" class="form-control select2" data-datatable-filter="true" data-filter-key="created_by" data-placeholder="جميع المستخدمين">
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="filter-amount-from" class="form-label">المبلغ من</label>
            <input type="number" id="filter-amount-from" class="form-control" data-datatable-filter="true" data-filter-key="amount_from" placeholder="0" step="0.01">
        </div>
        <div class="col-md-6">
            <label for="filter-amount-to" class="form-label">المبلغ إلى</label>
            <input type="number" id="filter-amount-to" class="form-control" data-datatable-filter="true" data-filter-key="amount_to" placeholder="0" step="0.01">
        </div>
    </x-data-table.filters>

    <div class="card">

        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'financial-reports-table'],
                false,
            ) !!}
        </div>

        <div class="modal fade" id="exportFieldsModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تخصيص حقول التصدير</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="fieldsContainer">
                        <!-- الحقول ستظهر هنا ديناميكيًا -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" id="confirmExport" class="btn btn-primary">تصدير</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div id="loadingMessage">جاري المعالجة...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
