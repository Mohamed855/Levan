@extends('tenants.layouts.layoutMaster')

@section('title', 'العملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.customerExportTruncationCriteria = @json(collect(\App\Support\SelectOptions::fromEnum(\App\Enums\Tenants\Customers\ExportTruncationCriterion::class))->map(fn ($label, $key) => compact('key', 'label'))->values());
    </script>
    <script>
        window.viewType = '{{ $viewType }}';
        window.routes = {
            customersIndex: "{{ tenant_route_name('customers.index') }}",
            customersCreate: "{{ tenant_route_name('customers.create') }}",
            customersImport: "{{ tenant_route_name('customers.import.index') }}",
            customersBulkUpdate: "{{ tenant_route_name('customers.bulk-update.index') }}",
            getAvailableStatuses: "{{ tenant_route_name('customers.available-statuses') }}",
            portfoliosByCompany: "{{ tenant_route_name('customers.portfolios-by-company') }}",
            customersExport: "{{ tenant_route_name('exports.customers.export') }}",
            customersExportCheckProgress: "{{ tenant_route_name('exports.customers.check-progress') }}",
            customersExportDownload: "{{ tenant_route_name('exports.customers.download', ['file' => 'FILE_PLACEHOLDER']) }}",
            customersGetAllIds: "{{ tenant_route_name('customers.get-all-ids') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}",

            // مسارات الرسائل النصية
            customersSMSForm: "{{ tenant_route_name('customers.sms.form') }}",
            customersSendSMS: "{{ tenant_route_name('customers.sms.send') }}",
            customersPreviewSMS: "{{ tenant_route_name('customers.sms.preview') }}",

            customerSmsCreate: "{{ tenant_route_name('customers.sms-messages.create', ':customerId') }}"

        };

        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.userPermissions = {
            canAddCustomer: @json(auth()->user()->can('customers.create')),
            canEditCustomer: @json(auth()->user()->can('customers.edit')),
            canDeleteCustomer: @json(auth()->user()->can('customers.delete')),
            canDeleteAllCustomers: @json(auth()->user()->can('customers.delete_all')),
            canSendSMS: @json(auth()->user()->can('customers.send_message')),
            canExportExcel: @json(auth()->user()->can('customers.export'))
        };

        function handleImportCustomers() {
            window.location.href = window.routes.customersImport;
        }

        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/basic/selection.js', 'resources/assets/js/tenants/business-center/customers/basic/row-colors.js', 'resources/assets/js/tenants/business-center/customers/sms/sms-modal.js', 'resources/assets/js/tenants/business-center/customers/basic/index.js'])

    {!! $dataTable->scripts() !!}
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

    {{-- الكروت الإحصائية --}}
    <div id="top_card" class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">
                                @if ($viewType === 'my')
                                    عملائي
                                @else
                                    العملاء
                                @endif
                            </span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($totalCustomers) }}</h4>
                            </div>
                            <small class="mb-0">
                                @if ($viewType === 'my')
                                    إجمالي عملائي
                                @else
                                    إجمالي العملاء
                                @endif
                            </small>
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
                                <h4 class="mb-0 me-2">{{ number_format($individualCustomers) }}</h4>
                            </div>
                            <small class="mb-0">الأفراد</small>
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
                                <h4 class="mb-0 me-2">{{ number_format($companyCustomers) }}</h4>
                            </div>
                            <small class="mb-0">الشركات</small>
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

    {{-- فلاتر البحث --}}
    {{-- Filters Card --}}
    <x-data-table.filters table="#customers-table">
        <div>
            <x-filter.select
                id="filter-status"
                filterKey="customer_status_id"
                table="#customers-table"
                name="customer_status_id"
                label="حالة العميل"
                placeholder="اختر الحالة"
                :options="$customerStatuses->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-contact-result"
                filterKey="last_contact_result_id"
                table="#customers-table"
                name="last_contact_result_id"
                label="نتيجة الاتصال"
                placeholder="اختر نتيجة الاتصال"
                :options="$contactResults->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-customer-type"
                filterKey="customer_type"
                table="#customers-table"
                name="customer_type"
                label="نوع العميل"
                placeholder="اختر النوع"
                :enum="\App\Enums\Tenants\Customers\CustomerType::class"
            />
        </div>

        @if ($viewType !== 'my')
            <div>
                <x-filter.select
                    id="filter-company"
                    filterKey="company_id"
                    table="#customers-table"
                    name="company_id"
                    label="الشركة"
                    placeholder="اختر الشركة"
                    :options="$companies->pluck('name', 'id')"
                />
            </div>

            <div>
                <label class="form-label" for="filter-portfolio">المحفظة</label>
                <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة" data-filter-key="portfolio_id" data-datatable-filter="true" data-datatable-target="#customers-table">
                    <option value=""></option>
                    @foreach ($portfolios as $portfolio)
                        <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                            {{ $portfolio->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-filter.select
                    id="filter-manager"
                    filterKey="relationship_manager_id"
                    table="#customers-table"
                    name="relationship_manager_id[]"
                    label="مسؤول العلاقة"
                    placeholder="اختر المسؤول"
                    multiple
                    :options="$users->pluck('name', 'id')"
                />
            </div>
        @else
            <div>
                <x-filter.select
                    id="filter-sms-status"
                    filterKey="sms_status"
                    table="#customers-table"
                    name="sms_status"
                    label="حالة الإرسال"
                    placeholder="اختر الحالة"
                    :enum="\App\Enums\Tenants\Customers\SmsDeliveryStatus::class"
                />
            </div>
        @endif

        @if ($viewType !== 'my')
            <div>
                <x-filter.select
                    id="filter-sms-status"
                    filterKey="sms_status"
                    table="#customers-table"
                    name="sms_status"
                    label="حالة الإرسال"
                    placeholder="اختر الحالة"
                    :enum="\App\Enums\Tenants\Customers\SmsDeliveryStatus::class"
                />
            </div>

        @else
        @endif
    </x-data-table.filters>

    <div class="card" id="card-customers">
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-striped table-bordered w-100 text-center',
                    'id' => 'customers-table',
                ]) !!}
            </div>
        </div>
    </div>
@endsection
