@extends('tenants.layouts.layoutMaster')

@section('title', 'المستخدمون المحذوفون')

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
            archiveIndex: "{{ tenant_route_name('archive.users.index') }}",
            massRestore: "{{ tenant_route_name('archive.users.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.users.mass-force-delete') }}",
            restore: "{{ tenant_route_name('archive.users.restore', ['user' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.users.force-delete', ['user' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/archive/users/index.js'])
@endsection

@section('content')
    <x-data-table.filters table="#archived-users-table">
        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-users-table"
                name="status"
                label="حالة المستخدم"
                placeholder="اختر حالة المستخدم"
                :enum="\App\Enums\Shared\ActivityStatus::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-role"
                filterKey="role"
                table="#archived-users-table"
                name="role"
                label="الدور"
                placeholder="اختر الدور"
                :options="$roles->pluck('name', 'name')"
            />
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-users-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
