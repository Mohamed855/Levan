@extends('admin.layouts.layoutMaster')

@section('title', 'الباقات')

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
            massDelete: "{{ route('admin.mass-delete') }}",
            updateSortOrder: "{{ route('admin.plans.update-sort-order') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/plans/index.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الباقات" :value="$totalPlans" subtitle="جميع الباقات المعرفة"
            icon="ti-packages" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الاشتراكات النشطة" :value="$activeSubscriptions" subtitle="الاشتراكات السارية حالياً"
            icon="ti-bolt" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الاشتراكات المنتهية" :value="$expiredSubscriptions" subtitle="المنتهية أو الملغاة"
            icon="ti-calendar-off" color="warning" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الإيراد الشهري" :value="number_format($monthlyRevenue, 2) . ' ر.س'" subtitle="الفواتير المدفوعة هذا الشهر"
            icon="ti-cash" color="info" />
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <x-data-table.filters table="#paln-table">
        <x-filter.select id="filter-status" filterKey="is_active" label="حالة الباقة"
            placeholder="اختر حالة الباقة" :options="$filterOptions['statuses'] ?? []" />
        <x-filter.select id="filter-plan-type" filterKey="plan_type" label="نوع الباقة"
            placeholder="اختر نوع الباقة" :options="$filterOptions['plan_types'] ?? []" />
        <x-filter.select id="filter-billing-cycle" filterKey="subscription_billing_cycle" label="دورة الفوترة"
            placeholder="اختر دورة الفوترة" :options="$filterOptions['billing_cycles'] ?? []" />
        <x-filter.select id="filter-limits-mode" filterKey="limits_mode" label="وضع الحدود"
            placeholder="اختر وضع الحدود" :options="$filterOptions['limits_modes'] ?? []" />
        <x-filter.select id="filter-availability" filterKey="availability" label="حالة الإتاحة"
            placeholder="اختر حالة الإتاحة" :options="$filterOptions['availability'] ?? []" />
        <x-filter.select id="filter-discounts" filterKey="discounts" label="حالة الخصم"
            placeholder="اختر حالة الخصم" :options="$filterOptions['discounts'] ?? []" />
        <x-filter.select id="filter-domain" filterKey="domain_management_enabled" label="إدارة الدومينات"
            placeholder="اختر حالة الدومين" :options="$filterOptions['domains'] ?? []" />
        <x-filter.select id="filter-most-requested" filterKey="most_requested" label="الأكثر طلباً"
            placeholder="اختر الأكثر طلباً" :options="$filterOptions['most_requested'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'paln-table'], false) !!}
        </div>
    </div>
@endsection
