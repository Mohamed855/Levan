@extends('tenants.layouts.layoutMaster')
@section('title', 'تقارير متابعات العملاء')

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
            customerFollowsReportsIndex: "{{ tenant_route_name('reports.customer_follows.index') }}",
            customerFollowsReportsExportPDF: "{{ tenant_route_name('reports.customer_follows.export.pdf') }}",
            customerFollowsReportsExportExcel: "{{ tenant_route_name('reports.customer_follows.export.excel') }}",
            customerFollowsReportsShowExportModal: "{{ tenant_route_name('reports.customer_follows.show-export-modal') }}",
            customerFollowsReportsCheckProgress: "{{ tenant_route_name('reports.customer_follows.check-progress') }}",
            customerFollowsReportsDownload: "{{ tenant_route_name('reports.customer_follows.download', ['file' => 'FILE_PLACEHOLDER']) }}",
            customerFollowsSearchCustomers: "{{ tenant_route_name('reports.customer_follows.search-customers') }}",
            customerFollowsGetCustomer: "{{ tenant_route_name('reports.customer_follows.get-customer', ['customer' => 'ID_PLACEHOLDER']) }}",
            customerFollowsPortfoliosByCompany: "{{ tenant_route_name('reports.customer_follows.portfolios-by-company') }}",
            customerFollowsReportsCancelExport: "{{ tenant_route_name('reports.customer_follows.cancel-export') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";

        window.exportConfig = {
            maxRowsPerFile: {{ \App\Jobs\Tenants\Reports\CustomerFollow\ExportCustomerFollowsJob::MAX_ROWS_PER_EXPORT_FILE }}
        };

        // بيانات المخططات المحدثة
        window.chartData = {
            contactMethodData: @json($follows_by_contact_method),
            contactResultData: @json($follows_by_contact_result),
            userData: @json($follows_by_user),
            monthlyData: @json($follows_by_month)
        };
    </script>
    @vite(['resources/assets/js/tenants/reports/customer_follows/index.js'])
@endsection

@section('content')
    {{-- الإحصائيات الرئيسية --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المتابعات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_follows) }}</h4>
                            </div>
                            <small class="mb-0">متابعة مسجلة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-phone-call ti-26px"></i>
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
                            <span class="text-heading">متابعات قادمة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($upcoming_follows) }}</h4>
                            </div>
                            <small
                                class="mb-0">{{ $total_follows > 0 ? round(($upcoming_follows / $total_follows) * 100, 1) : 0 }}%
                                من المتابعات</small>
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
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">متابعات حديثة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($recent_follows) }}</h4>
                            </div>
                            <small class="mb-0">خلال 30 يوم</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
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
                            <span class="text-heading">المستخدمون النشطون</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ count($follows_by_user) }}</h4>
                            </div>
                            <small class="mb-0">مسجل متابعات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-users ti-26px"></i>
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
                    <h5 class="card-title mb-0">توزيع طرق الاتصال</h5>
                    <i class="ti ti-phone-incoming text-primary"></i>
                </div>
                <div class="card-body">
                    <div id="contactMethodChart"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">توزيع نتائج الاتصال</h5>
                    <i class="ti ti-phone-check text-success"></i>
                </div>
                <div class="card-body">
                    <div id="contactResultChart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">المستخدمون حسب المتابعات</h5>
                    <i class="ti ti-user-check text-info"></i>
                </div>
                <div class="card-body">
                    <div id="userChart"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">المتابعات حسب الشهر</h5>
                    <i class="ti ti-calendar text-warning"></i>
                </div>
                <div class="card-body">
                    <div id="monthlyChart"></div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#customer-follows-reports-table" reset-id="reset-filters">
        <div>
            <label for="filter-company" class="form-label">الشركة</label>
            <select id="filter-company" class="form-control select2" data-datatable-filter="true" data-filter-key="company_id" data-placeholder="جميع الشركات">
                <option value=""></option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-portfolio" class="form-label">المحفظة</label>
            <select id="filter-portfolio" class="form-control select2" data-datatable-filter="true" data-filter-key="portfolio_id" data-placeholder="اختر المحفظة" disabled>
                <option value=""></option>
            </select>
        </div>


        <div>
            <label for="filter-customer" class="form-label">العميل</label>
            <select id="filter-customer" class="form-control select2-ajax" data-datatable-filter="true" data-filter-key="customer_id" data-placeholder="ابحث عن العميل برقم الحساب">
                <option value=""></option>
                @foreach (array_slice($customers, 0, 10) as $customer)
                    <option value="{{ $customer['id'] }}">{{ $customer['text'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-contact-method" class="form-label">طريقة الاتصال</label>
            <select id="filter-contact-method" class="form-control select2" data-datatable-filter="true" data-filter-key="contact_method_id" data-placeholder="جميع الطرق">
                <option value=""></option>
                @foreach ($contact_methods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-customer-status" class="form-label">حالة العميل</label>
            <select id="filter-customer-status" class="form-control select2" data-datatable-filter="true" data-filter-key="customer_status_id" data-placeholder="جميع الحالات">
                <option value=""></option>
                @foreach ($customer_statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-contact-result" class="form-label">نتيجة الاتصال</label>
            <select id="filter-contact-result" class="form-control select2" data-datatable-filter="true" data-filter-key="contact_result_id" data-placeholder="جميع النتائج">
                <option value=""></option>
                @foreach ($contact_results as $result)
                    <option value="{{ $result->id }}">{{ $result->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-created-by" class="form-label">أنشأ بواسطة</label>
            <select id="filter-created-by" class="form-control select2" data-datatable-filter="true" data-filter-key="created_by" data-placeholder="جميع المستخدمين">
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'customer-follows-reports-table'],
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
