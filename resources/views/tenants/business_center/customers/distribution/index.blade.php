@extends('tenants.layouts.layoutMaster')

@section('title', 'توزيع العملاء')

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
    <style>
        #distributions-table tbody tr:hover {
            background-color: var(--bs-gray-50) !important;
            cursor: pointer;
        }

        #distributions-table tbody tr {
            transition: background-color 0.2s ease;
        }
    </style>
@endsection

@section('page-script')
    <script>
        // تعريف المسارات والمتغيرات العامة
        window.routes = {
            distributionCreate: "{{ tenant_route_name('customers.distribution.create') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
        window.collectorsData = @json(\App\Models\Tenants\UserMangment\User\User::role('collector')->select('id', 'name')->orderBy('name')->get());
    </script>
    @vite(['resources/assets/js/tenants/business-center/customers/distribution/index.js'])
@endsection

@section('content')
    <!-- إحصائيات سريعة -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي العملاء</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($totalCustomers) }}</h4>
                            </div>
                            <small class="mb-0">عميل في النظام</small>
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

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">عملاء غير موزعين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($unassignedCustomers) }}</h4>
                            </div>
                            <small class="mb-0">بحاجة للتوزيع</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-exclamation ti-26px"></i>
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
                            <span class="text-heading">عدد المحصلين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($totalCollectors) }}</h4>
                            </div>
                            <small class="mb-0">محصل نشط</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-user-check ti-26px"></i>
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
                            <span class="text-heading">توزيعات هذا الشهر</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($thisMonthDistributions) }}</h4>
                            </div>
                            <small class="mb-0">عملية توزيع</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-calendar-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#distributions-table">
        <div>
            <x-filter.select
                id="filter-distribution-method"
                filterKey="distribution_method"
                table="#distributions-table"
                name="distribution_method"
                label="نوع التوزيع"
                placeholder="جميع الأنواع"
                :enum="\App\Enums\Tenants\Customers\DistributionMethod::class"
            />
        </div>

    </x-data-table.filters>

    <!-- جدول عمليات التوزيع -->
    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'distributions-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
