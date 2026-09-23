@extends('tenants.layouts.layoutMaster')

@section('title', 'سجلات الرسائل الفردية')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
        export: "{{ tenant_route_name('communication.message-individual-logs.export') }}",
        archives: "{{ tenant_route_name('communication.message-individual-logs.archives') }}", 
        portfoliosByCompany: "{{ tenant_route_name('communication.message-individual-logs.portfolios-by-company', ':companyId') }}",
        customersByPortfolio: "{{ tenant_route_name('communication.message-individual-logs.customers-by-portfolio', ':portfolioId') }}"
    };

    window.csrfToken = "{{ csrf_token() }}";
</script>
@vite(['resources/assets/js/tenants/communication/message-logs/message-individual-logs.js'])
@endsection

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي الرسائل</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($statistics['total_messages']) }}</h4>
                        </div>
                        <small class="mb-0">إجمالي الرسائل</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-message-2 ti-26px"></i>
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
                        <span class="text-heading">الرسائل المرسلة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ number_format($statistics['sent_messages']) }}</h4>
                        </div>
                        <small class="mb-0">الرسائل المرسلة</small>
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

    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">معدل النجاح</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $statistics['success_rate'] }}%</h4>
                        </div>
                        <small class="mb-0">معدل النجاح</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-chart-line ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-2">
                <label for="filter-status" class="form-label">الحالة</label>
                <select id="filter-status" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($formData['statuses'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-company" class="form-label">الشركة</label>
                <select id="filter-company" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($formData['companies'] as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">المحفظة</label>
                <select id="filter-portfolio" class="form-select select2" name="portfolio_id">
                    <option value="">الكل</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">العميل</label>
                <select id="filter-customer" class="form-select select2" name="customer_id">
                    <option value="">الكل</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-date-from" class="form-label">من تاريخ</label>
                <input type="date" id="filter-date-from" class="form-control">
            </div>

            <div class="col-md-2">
                <label for="filter-date-to" class="form-label">إلى تاريخ</label>
                <input type="date" id="filter-date-to" class="form-control">
            </div>
        </div>


        <hr class="mt-8">
        {!! $dataTable->table(
        ['class' => 'table table-striped table-bordered w-100', 'id' => 'message-individual-logs-table'],
        false,
        ) !!}
    </div>
</div>
@endsection