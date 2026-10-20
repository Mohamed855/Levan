@extends('admin.layouts.layoutMaster')

@section('title', 'المستحدمين ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.users.bulk-delete') }}",
            getUserRoles: "{{ route('admin.roles.get-user-roles', ':id') }}",
            updateUserRole: "{{ route('admin.roles.update-user-role', ':id') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/users/index.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')

    <div id="top_card" class="row g-4 mb-4">
        <x-ui.stat-card title="إجمالي المستخدمين" :value="$totalUsers" subtitle="إجمالي المستخدمين" icon="ti-users"
            color="primary" />
        <x-ui.stat-card title="المستخدمين النشطين" :value="$activeUsers" subtitle="المستخدمين النشطين" icon="ti-user-check"
            color="success" />
        <x-ui.stat-card title="المستخدمين غير النشطين" :value="$inactiveUsers" subtitle="المستخدمين غير النشطين"
            icon="ti-user-off" color="warning" />
        <x-ui.stat-card title="المستخدمين المؤرشفين" :value="$archivedUsers" subtitle="يمكن استعادتها من الأرشيف"
            icon="ti-archive" color="danger" />
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <x-data-table.filters table="#users-table">
        <x-filter.select id="filter-status" filterKey="status" label="حالة المستخدمين" placeholder="اختر حالة المستخدم"
            :options="$filterOptions['statuses']" />
        <x-filter.select id="filter-role" filterKey="role" label="دور المستخدم" placeholder="اختر الدور"
            :options="$filterOptions['roles']" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'users-table'], false) !!}
        </div>
    </div>

    <x-bootstrap.modal id="editUserRoleModal" title="تعديل دور المشرف" icon="ti-user-shield">
        <form id="updateUserRoleForm">
            <input id="role-assignment-user-id" type="hidden" name="user_id">
            <x-form.controls.select name="role" id="role-assignment-role" label="الدور" :options="$roles"
                placeholder="اختر الدور" required col="col-12" />
        </form>

        <x-slot:footer>
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" form="updateUserRoleForm" class="btn btn-primary">حفظ التغيير</button>
        </x-slot:footer>
    </x-bootstrap.modal>
@endsection
