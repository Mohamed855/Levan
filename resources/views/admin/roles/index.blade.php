@extends('admin.layouts.layoutMaster')

@section('title', 'الأدوار والصلاحيات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            <x-ui.stat-card title="إجمالي الأدوار" :value="$statistics['total']" subtitle="كل الأدوار الإدارية" icon="ti-shield"
                color="primary" />
            <x-ui.stat-card title="أدوار مستخدمة" :value="$statistics['assigned']" subtitle="مرتبطة بمستخدم واحد أو أكثر" icon="ti-users"
                color="success" />
            <x-ui.stat-card title="أدوار غير مستخدمة" :value="$statistics['unassigned']" subtitle="جاهزة للإسناد" icon="ti-user-question"
                color="info" />
            <x-ui.stat-card title="إجمالي الصلاحيات" :value="$statistics['permissions']" subtitle="صلاحيات حارس الإدارة" icon="ti-key"
                color="warning" />
        </x-slot:stats>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'roles-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
