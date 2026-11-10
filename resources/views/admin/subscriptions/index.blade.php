@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة الاشتراكات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
        };
    </script>
    @vite(['resources/assets/js/admin/subscriptions/index.js'])
@endsection

@section('content')

    <x-data-table.page table-id="subscriptions-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي الاشتراكات" :value="$totalSubscriptions ?? 0"
                subtitle="جميع الاشتراكات الصادرة" icon="ti-packages" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الاشتراكات النشطة" :value="$activeSubscriptions ?? 0"
                subtitle="اشتراكات سارية حالياً" icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارب على الانتهاء" :value="$expiringSubscriptions ?? 0"
                subtitle="تنتهي خلال 30 يوماً" icon="ti-alert-triangle" color="info" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الاشتراكات المنتهية" :value="$expiredSubscriptions ?? 0"
                subtitle="اشتراكات منتهية أو ملغاة" icon="ti-clock-x" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#subscriptions-table">
                <x-filter.select id="filter-sub-status" filterKey="status" label="حالة الاشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة" placeholder="اختر الباقة"
                    :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-city" filterKey="city_id" label="مدينة الشركة" placeholder="اختر المدينة"
                    :options="$filterOptions['cities'] ?? []" />
                <x-filter.select id="filter-expiring" filterKey="expiring_in" label="قرب الانتهاء"
                    placeholder="قرب الانتهاء" :options="$filterOptions['expiring'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'subscriptions-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
