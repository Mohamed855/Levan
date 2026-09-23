@extends('tenants.layouts.layoutMaster')

@section('title', 'العملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
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
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };

        window.userPermissions = {
            canAddCustomer: @json(auth()->user()->can('إضافة عميل')),
            canEditCustomer: @json(auth()->user()->can('تعديل عميل')),
            canDeleteCustomer: @json(auth()->user()->can('حذف عميل')),
            canDeleteAllCustomers: @json(auth()->user()->can('حذف جميع العملاء')),
            canSendSMS: @json(auth()->user()->can('إرسال رسالة')),
            canExportExcel: @json(auth()->user()->can('تصدير أكسل'))
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

    {{-- جدول العملاء --}}
    <div class="card" id="card-customers">
        <div class="card-body">
            {{-- فلاتر البحث --}}
            <div class="filters row g-3">
                {{-- الصف الأول --}}
                <div class="col-md-2">
                    <label for="filter-status">حالة العميل</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر الحالة">
                        <option value=""></option>
                        @foreach ($customerStatuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter-contact-result">نتيجة الاتصال</label>
                    <select id="filter-contact-result" class="form-control select2" data-placeholder="اختر نتيجة الاتصال">
                        <option value=""></option>
                        @foreach ($contactResults as $result)
                            <option value="{{ $result->id }}">{{ $result->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter-customer-type">نوع العميل</label>
                    <select id="filter-customer-type" class="form-control select2" data-placeholder="اختر النوع">
                        <option value=""></option>
                        <option value="individual">فردي</option>
                        <option value="company">شركة</option>
                    </select>
                </div>

                @if ($viewType !== 'my')
                    <div class="col-md-2">
                        <label for="filter-company">الشركة</label>
                        <select id="filter-company" class="form-control select2" data-placeholder="اختر الشركة">
                            <option value=""></option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter-portfolio">المحفظة</label>
                        <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة">
                            <option value=""></option>
                            @foreach ($portfolios as $portfolio)
                                <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                                    {{ $portfolio->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter-manager">مسؤول العلاقة</label>
                        <select id="filter-manager" class="form-control select2" data-placeholder="اختر المسؤول" multiple>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    {{-- في شاشة "عملائي" نكمل الصف الأول بفلاتر المديونية وحالة الإرسال --}}
                    <div class="col-md-3">
                        <label for="filter-debt-min">المديونية من</label>
                        <input type="number" id="filter-debt-min" class="form-control" placeholder="من القيمة">
                    </div>

                    <div class="col-md-3">
                        <label for="filter-debt-max">المديونية إلى</label>
                        <input type="number" id="filter-debt-max" class="form-control" placeholder="إلى القيمة">
                    </div>

                    <div class="col-md-4">
                        <label for="filter-sms-status">حالة الإرسال</label>
                        <select id="filter-sms-status" class="form-control select2" data-placeholder="اختر الحالة">
                            <option value=""></option>
                            <option value="sent">تم الإرسال</option>
                            <option value="not_sent">لم يتم الإرسال</option>
                        </select>
                    </div>
                @endif

                {{-- الصف الثاني --}}
                @if ($viewType !== 'my')
                    <div class="col-md-2 mt-3">
                        <label for="filter-debt-min">المديونية من</label>
                        <input type="number" id="filter-debt-min" class="form-control" placeholder="من القيمة">
                    </div>

                    <div class="col-md-2 mt-3">
                        <label for="filter-debt-max">المديونية إلى</label>
                        <input type="number" id="filter-debt-max" class="form-control" placeholder="إلى القيمة">
                    </div>

                    <div class="col-md-2 mt-3">
                        <label for="filter-sms-status">حالة الإرسال</label>
                        <select id="filter-sms-status" class="form-control select2" data-placeholder="اختر الحالة">
                            <option value=""></option>
                            <option value="sent">تم الإرسال</option>
                            <option value="not_sent">لم يتم الإرسال</option>
                        </select>
                    </div>

                    <div class="col-md-3 mt-3">
                        <label for="filter-sms-date-from">تاريخ الإرسال من</label>
                        <input type="date" id="filter-sms-date-from" class="form-control" placeholder="من التاريخ">
                    </div>

                    <div class="col-md-3 mt-3">
                        <label for="filter-sms-date-to">تاريخ الإرسال إلى</label>
                        <input type="date" id="filter-sms-date-to" class="form-control" placeholder="إلى التاريخ">
                    </div>
                @else
                    {{-- في شاشة "عملائي" الصف الثاني يحتوي فقط على تواريخ الإرسال --}}
                    <div class="col-md-4 mt-3">
                        <label for="filter-sms-date-from">تاريخ الإرسال من</label>
                        <input type="date" id="filter-sms-date-from" class="form-control" placeholder="من التاريخ">
                    </div>

                    <div class="col-md-4 mt-3">
                        <label for="filter-sms-date-to">تاريخ الإرسال إلى</label>
                        <input type="date" id="filter-sms-date-to" class="form-control" placeholder="إلى التاريخ">
                    </div>
                @endif
            </div>

            <hr class="mt-10">

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-striped table-bordered w-100 text-center',
                    'id' => 'customers-table',
                ]) !!}
            </div>
        </div>
    </div>
@endsection
