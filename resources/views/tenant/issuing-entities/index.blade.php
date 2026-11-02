@extends('tenant.layouts.layoutMaster')

@section('title', 'الجهات المسندة')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
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
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        window.handleMassDelete = function() {
            window.executeMassDelete('IssuingEntity', window.routes.massDelete, 'يرجى تحديد جهة مسندة واحدة على الأقل');
        };
    </script>
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="issuing-entities-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الجهات المسندة" :value="number_format($statistics['total'])" subtitle="كافة الجهات المسجلة"
                icon="ti-scale" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="الجهات النشطة" :value="number_format($statistics['active'])" subtitle="متاحة للاستخدام والربط"
                icon="ti-circle-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="الجهات المعطلة" :value="number_format($statistics['inactive'])" subtitle="معطلة مؤقتًا"
                icon="ti-circle-x" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#issuing-entities-table">
                <x-filter.select id="filter-status" filterKey="status" label="الحالة" placeholder="اختر الحالة"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'issuing-entities-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
