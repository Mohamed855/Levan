@extends('admin.layouts.layoutMaster')

@section('title', 'الكوبونات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/coupons/index.js'])
@endsection

@section('content')
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="coupon-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الكوبونات" :value="$totalCoupons ?? ($totalPlans ?? 0)" subtitle="جميع الكوبونات المسجلة"
                icon="ti-ticket" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="الكوبونات النشطة" :value="$activeCoupons ?? ($activecoupons ?? 0)" subtitle="كوبونات مفعلة حالياً"
                icon="ti-rosette-discount" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="الكوبونات غير النشطة" :value="$inactiveCoupons ?? ($inactivecoupons ?? 0)"
                subtitle="كوبونات معطلة مؤقتاً" icon="ti-rosette-discount-off" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="الكوبونات المنتهية" :value="$expiredCoupons ?? 0"
                subtitle="تجاوزت تاريخ الصلاحية" icon="ti-calendar-off" color="danger" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="مرات الاستخدام" :value="$usedCount ?? ($couponCount ?? 0)"
                subtitle="إجمالي استخدام الكوبونات" icon="ti-discount-check" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#coupon-table">
                <x-filter.select id="filter-status" filterKey="is_active" label="حالة الكوبونات"
                    placeholder="اختر حالة الكوبون" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-type" filterKey="discount_type" label="نوع الخصم"
                    placeholder="اختر نوع الخصم" :options="$filterOptions['types'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'coupon-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
