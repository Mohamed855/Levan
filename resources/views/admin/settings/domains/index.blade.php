@extends('admin.layouts.layoutMaster')

@section('title', 'اعدادات اسعار الامتدادات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/shared-status-toggle.js'])
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        window.handleMassDelete = function() {
            window.executeMassDelete('ControlDomain', window.routes.massDelete, 'يرجى تحديد امتداد واحد على الأقل');
        };
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="domain-setting-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الامتدادات" :value="number_format($statistics['total'])" subtitle="كل امتدادات الدومين"
                icon="ti-world" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="الامتدادات النشطة" :value="number_format($statistics['active'])" subtitle="امتدادات مفعلة"
                icon="ti-circle-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="الامتدادات المعطلة" :value="number_format($statistics['inactive'])" subtitle="امتدادات معطلة"
                icon="ti-circle-x" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#domain-setting-table">
                <x-filter.select id="filter-status" filterKey="status" label="الحالة" placeholder="اختر الحالة"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'domain-setting-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
