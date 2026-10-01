@extends('tenants.layouts.layoutMaster')
@section('title', 'تقارير العملاء')

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
            customerReportsIndex: "{{ tenant_route_name('reports.customers.index') }}",
            customerReportsExportPDF: "{{ tenant_route_name('reports.customers.export.pdf') }}",
            customerReportsExportExcel: "{{ tenant_route_name('reports.customers.export.excel') }}",
            customerReportsShowExportModal: "{{ tenant_route_name('reports.customers.show-export-modal') }}",
            customerReportsCheckProgress: "{{ tenant_route_name('reports.customers.check-progress') }}",
            customerReportsDownload: "{{ tenant_route_name('reports.customers.download', ['file' => 'FILE_PLACEHOLDER']) }}"
        };

        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";

        window.chartData = {
            contactResultData: @json($customers_by_contact_result),
            portfoliosAmountData: @json($portfolios_by_amount),
            monthlyData: @json($customers_by_month)
        };
    </script>
    @vite(['resources/assets/js/tenants/reports/customers/index.js'])
@endsection

@section('content')
    {{-- الإحصائيات الرئيسية --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي العملاء</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_customers) }}</h4>
                            </div>
                            <small class="mb-0">عميل مسجل</small>
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
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">العملاء الأفراد</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($individual_customers) }}</h4>
                            </div>
                            <small
                                class="mb-0">{{ $total_customers > 0 ? round(($individual_customers / $total_customers) * 100, 1) : 0 }}%
                                من العملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-user ti-26px"></i>
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
                            <span class="text-heading">العملاء الشركات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($company_customers) }}</h4>
                            </div>
                            <small
                                class="mb-0">{{ $total_customers > 0 ? round(($company_customers / $total_customers) * 100, 1) : 0 }}%
                                من العملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-building ti-26px"></i>
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
                    <h5 class="card-title mb-0">توزيع نتائج الاتصال</h5>
                    <i class="ti ti-phone-call text-primary"></i>
                </div>
                <div class="card-body">
                    <div id="contactResultChart"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">المحافظ حسب المبالغ</h5>
                    <i class="ti ti-wallet"></i>
                </div>
                <div class="card-body">
                    <div id="portfolioAmountChart"></div>
                    <div class="text-center mt-2 py-1 px-3 mx-auto"
                        style="border: 1px dashed #4cc1bb; border-radius: 4px; background: rgba(76, 193, 187, 0.03); max-width: 220px;">
                        <small class="text-muted" style="font-size: 0.65rem;">إجمالي المديونية :</small>
                        <strong style="color: #4cc1bb; font-size: 0.85rem; margin-right: 5px;">
                            {{ number_format($total_portfolios_debt ?? 0, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">تقرير العملاء </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="collapse"
                    data-bs-target="#filtersSection" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i>
                    فلاتر البحث
                    <i class="ti ti-chevron-down ms-1"></i>
                </button>
            </div>
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
                    <div class="col-md-3">
                        <label for="filter-customer-status" class="form-label">حالة العميل</label>
                        <select id="filter-customer-status" class="form-control select2" data-placeholder="جميع الحالات">
                            <option value=""></option>
                            @foreach ($customer_statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-customer-type" class="form-label">نوع العميل</label>
                        <select id="filter-customer-type" class="form-control select2" data-placeholder="جميع الأنواع">
                            <option value=""></option>
                            <option value="individual">فردي</option>
                            <option value="company">شركة</option>
                        </select>
                    </div>
                </div>

                {{-- مجموعة فلاتر الشركة والمحفظة --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="filter-company" class="form-label">الشركة</label>
                        <select id="filter-company" class="form-control select2" data-placeholder="جميع الشركات">
                            <option value=""></option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter-portfolio" class="form-label">المحفظة</label>
                        <select id="filter-portfolio" class="form-control select2" data-placeholder="جميع المحافظ">
                            <option value=""></option>
                            @foreach ($portfolios as $portfolio)
                                <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                                    {{ $portfolio->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter-nationality" class="form-label">الجنسية</label>
                        <select id="filter-nationality" class="form-control select2" data-placeholder="جميع الجنسيات">
                            <option value=""></option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label for="filter-manager" class="form-label">مسؤول العلاقة</label>
                        <select id="filter-manager" class="form-control select2-multiple" multiple
                            data-placeholder="اختر المسؤولين">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="filter-contact-result" class="form-label">نتيجة الاتصال</label>
                        <select id="filter-contact-result" class="form-control select2-multiple" multiple
                            data-placeholder="اختر نتائج الاتصال">
                            @foreach ($contact_results as $result)
                                <option value="{{ $result->id }}">{{ $result->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="filter-debt-from" class="form-label">مديونية من</label>
                        <input type="number" id="filter-debt-from" class="form-control" placeholder="0"
                            step="0.01">
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

        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'customer-reports-table'],
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
