@extends('tenant.layouts.layoutMaster')

@section('title', 'المستخدمين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        // متغيرات JavaScript العامة
        window.routes = {
            usersIndex: "{{ tenant_route_name('users.index') }}",
            usersCreate: "{{ tenant_route_name('users.create') }}",
            toggleStatus: "{{ tenant_route_name('users.toggle-status', ['user' => '___ID___']) }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenant/user-management/user/index.js'])

    {!! $dataTable->scripts() !!}
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="users-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="المستخدمين" :value="$totalUsers" subtitle="إجمالي المستخدمين"
                icon="ti-users" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="المستخدمين النشطين" :value="$activeUsers" subtitle="المستخدمين النشطين"
                icon="ti-user-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="المستخدمين غير النشطين" :value="$inactiveUsers"
                subtitle="المستخدمين غير النشطين" icon="ti-user-off" color="danger" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="المستخدمين المحذوفين" :value="$archivedUsers"
                subtitle="المستخدمين في الأرشيف" icon="ti-archive" color="warning" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#users-table" reset-id="btn-reset-filters">
                <x-filter.select id="filter-status" filterKey="status" label="حالة المستخدم"
                    placeholder="اختر حالة المستخدم" :enum="\App\Enums\Shared\ActivityStatus::class" />
                <x-filter.select id="filter-role" filterKey="role" label="دور المستخدم" placeholder="اختر الدور"
                    :options="\App\Support\AccessLabels::roleOptions($roles)" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'users-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
