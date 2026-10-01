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
    @vite(['resources/assets/js/admin/plans/filter.js', 'resources/assets/js/admin/plans/index.js'])
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
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <x-filter.select col="col-md-3" id="filter-status" label="حالة الباقة"
                    placeholder="اختر حالة الباقة" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-plan-type" label="نوع الباقة"
                    placeholder="اختر نوع الباقة" :options="$filterOptions['plan_types'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-billing-cycle" label="دورة الفوترة"
                    placeholder="اختر دورة الفوترة" :options="$filterOptions['billing_cycles'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-limits-mode" label="وضع الحدود"
                    placeholder="اختر وضع الحدود" :options="$filterOptions['limits_modes'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-availability" label="حالة الإتاحة"
                    placeholder="اختر حالة الإتاحة" :options="$filterOptions['availability'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-discounts" label="حالة الخصم"
                    placeholder="اختر حالة الخصم" :options="$filterOptions['discounts'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-domain" label="إدارة الدومينات"
                    placeholder="اختر حالة الدومين" :options="$filterOptions['domains'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-most-requested" label="الأكثر طلباً"
                    placeholder="اختر الأكثر طلباً" :options="$filterOptions['most_requested'] ?? []" />
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'paln-table'], false) !!}
        </div>
    </div>
@endsection
