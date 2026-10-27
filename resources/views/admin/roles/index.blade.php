@extends('admin.layouts.layoutMaster')

@section('title', 'الأدوار والصلاحيات')

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
            massDelete: "{{ route('admin.mass-delete') }}",
        };
        window.handleMassDelete = function() {
            window.executeMassDelete('Role', window.routes.massDelete, 'يرجى تحديد دور واحد على الأقل');
        };
    </script>
@endsection


@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="roles-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الأدوار" :value="$statistics['total']" subtitle="كل الأدوار الإدارية" icon="ti-shield"
                color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار مستخدمة" :value="$statistics['assigned']" subtitle="مرتبطة بمستخدم واحد أو أكثر" icon="ti-users"
                color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار غير مستخدمة" :value="$statistics['unassigned']" subtitle="جاهزة للإسناد" icon="ti-user-question"
                color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الصلاحيات" :value="$statistics['permissions']" subtitle="صلاحيات حارس الإدارة" icon="ti-key"
                color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار مخصصة" :value="$statistics['custom']" subtitle="أدوار تم إنشاؤها" icon="ti-settings"
                color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#roles-table">
                <x-filter.select id="filter-role" filterKey="role" table="#roles-table" label="اسم الدور"
                    placeholder="اختر الدور" :options="$roleOptions ?? []" />
                <x-filter.select id="filter-usage-status" filterKey="usage_status" table="#roles-table" label="حالة الاستخدام"
                    placeholder="اختر حالة الاستخدام" :options="[
                        'assigned' => 'أدوار مستخدمة (مرتبطة بمستخدمين)',
                        'unassigned' => 'أدوار غير مستخدمة (جاهزة للإسناد)',
                    ]" />
                <x-filter.date id="filter-date-from" filterKey="date_from" table="#roles-table" label="تاريخ الإضافة من" />
                <x-filter.date id="filter-date-to" filterKey="date_to" table="#roles-table" label="تاريخ الإضافة إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'roles-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
