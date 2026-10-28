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
    @vite(['resources/assets/js/admin/plans/index.js'])
@endsection
@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="plans-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي الباقات" :value="$totalPlans" subtitle="جميع الباقات المعرفة" icon="ti-packages"
                color="primary" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الباقات النشطة" :value="$activePlans" subtitle="متاحة للاشتراك" icon="ti-bolt" color="success"
                col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الباقات غير النشطة" :value="$inactivePlans" subtitle="غير متاحة للاشتراك"
                icon="ti-calendar-off" color="warning" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="متاحة الآن" :value="$availablePlans" subtitle="ضمن تاريخ الإتاحة" icon="ti-circle-check"
                color="info" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="مجدولة" :value="$futurePlans" subtitle="تتاح مستقبلاً" icon="ti-calendar-time" color="danger"
                col="col-sm-6 col-xl" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#plans-table">
                <x-filter.select id="filter-status" filterKey="is_active" label="حالة الباقة" placeholder="اختر حالة الباقة"
                    :options="$filterOptions['statuses'] ?? []" />
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
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'plans-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
