@extends('tenants.layouts.layoutMaster')

@section('title', 'العملاء المحذوفون')

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
            archiveIndex: "{{ tenant_route_name('archive.customers.index') }}",
            massRestore: "{{ tenant_route_name('archive.customers.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.customers.mass-force-delete') }}",
            getAllIds: "{{ tenant_route_name('archive.customers.get-all-ids') }}",
            restore: "{{ tenant_route_name('archive.customers.restore', ['customer' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.customers.force-delete', ['customer' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
        window.totalArchivedCount = {{ $totalArchivedCustomers }};
    </script>
    @vite(['resources/assets/js/tenants/archive/customers/index.js', 'resources/assets/js/tenants/archive/customers/selection.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">العملاء المحذوفون</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedCustomers }}</h4>
                            </div>
                            <small class="mb-0">إجمالي العملاء المحذوفين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-archive ti-26px"></i>
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
                                <h4 class="mb-0 me-2">{{ $archivedIndividualCustomers }}</h4>
                            </div>
                            <small class="mb-0">العملاء الأفراد المحذوفين</small>
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
                            <span class="text-heading">عملاء الشركات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedCompanyCustomers }}</h4>
                            </div>
                            <small class="mb-0">عملاء الشركات المحذوفين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-secondary">
                                <i class="ti ti-building ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#archived-customers-table">
        <div class="col-md-2">
            <x-filter.select id="filter-customer-type" filterKey="customer_type" table="#archived-customers-table"
                name="customer_type" label="نوع العميل" placeholder="اختر نوع العميل" :options="['individual' => 'فردي', 'company' => 'شركة']" />
        </div>

        <div class="col-md-2">
            <x-filter.select id="filter-company" filterKey="company_id" table="#archived-customers-table" name="company_id"
                label="الشركة" placeholder="اختر الشركة" :options="$companies->pluck('name', 'id')" />
        </div>

        <div class="col-md-2">
            <label for="filter-portfolio" class="form-label">المحفظة</label>
            <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة"
                data-filter-key="portfolio_id" data-datatable-filter="true"
                data-datatable-target="#archived-customers-table">
                <option value=""></option>
                @foreach ($portfolios as $portfolio)
                    <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                        {{ $portfolio->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <x-filter.select id="filter-contact-result" filterKey="last_contact_result_id" table="#archived-customers-table"
                name="last_contact_result_id" label="نتيجة الاتصال" placeholder="اختر نتيجة الاتصال" :options="$contactResults->pluck('name', 'id')" />
        </div>

        <div class="col-md-3">
            <x-filter.select id="filter-manager" filterKey="relationship_manager_id" table="#archived-customers-table"
                name="relationship_manager_id" label="مسؤول العلاقة" placeholder="اختر مسؤول العلاقة" :options="$users->pluck('name', 'id')" />
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-customers-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
