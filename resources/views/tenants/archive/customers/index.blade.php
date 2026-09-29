@extends('tenants.layouts.layoutMaster')

@section('title', 'أرشيف العملاء')

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
@vite(['resources/assets/js/tenants/archive/customers/index.js',
'resources/assets/js/tenants/archive/customers/selection.js'])
@endsection

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">العملاء المؤرشفين</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $totalArchivedCustomers }}</h4>
                        </div>
                        <small class="mb-0">إجمالي العملاء المؤرشفين</small>
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
                        <small class="mb-0">العملاء الأفراد المؤرشفين</small>
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
                        <small class="mb-0">عملاء الشركات المؤرشفين</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-secondary">
                            <i class="ti ti-building ti-26px"></i>
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
            <div class="col-md-3">
                <label for="filter-customer-type" class="form-label">نوع العميل</label>
                <select id="filter-customer-type" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    <option value="individual">فردي</option>
                    <option value="company">شركة</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-company" class="form-label">الشركة</label>
                <select id="filter-company" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-portfolio" class="form-label">المحفظة</label>
                <select id="filter-portfolio" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($portfolios as $portfolio)
                    <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                        {{ $portfolio->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="filter-contact-result" class="form-label">نتيجة الاتصال</label>
                <select id="filter-contact-result" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($contactResults as $result)
                    <option value="{{ $result->id }}">{{ $result->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-manager" class="form-label">مسؤول العلاقة</label>
                <select id="filter-manager" class="form-control select2" data-placeholder="الكل">
                    <option value=""></option>
                    @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr class="mt-10">
        {!! $dataTable->table(
        ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-customers-table'],
        false,
        ) !!}
    </div>
</div>
@endsection