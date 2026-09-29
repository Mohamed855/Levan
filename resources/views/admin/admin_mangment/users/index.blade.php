@extends('admin.layouts.layoutMaster')

@section('title', 'المستحدمين ')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')

@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
{!! $dataTable->scripts() !!}
@endsection


@section('page-script')
<script>
    window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };
</script>
@vite(['resources/assets/js/admin/admin_mangment/users/index.js',
'resources/assets/js/admin/admin_mangment/users/filter.js'])
@endsection

@section('toastr')
@vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
@vite(['resources/css/dataTable.css'])
@endsection

@section('content')

<div id="top_card" class="row g-4 mb-4">
    <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين" :value="$totalUsers" subtitle="إجمالي المستخدمين"
        icon="ti-users" color="primary" avatarClass="circle" />
    <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين النشطين" :value="$activeUsers"
        subtitle="المستخدمين النشطين" icon="ti-user-check" color="success" avatarClass="circle" />
    <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين غير النشطين" :value="$inactiveUsers"
        subtitle="المستخدمين غير النشطين" icon="ti-user-off" color="danger" avatarClass="circle" />
    <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين المؤرشفين" :value="$archivedUsers"
        subtitle="المستخدمين المحذوفين" icon="ti-archive" color="warning" avatarClass="circle" />
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

<div class="card">
    <div class="card-body">
        <div class="filters row g-3">


            <div class="col-md-6">
                <label for="filter-status">حالة المستخدمين</label>
                <select id="filter-status" class="form-control select2" data-placeholder="حالة المستخدمين">
                    <option value=""></option>
                    {{-- @foreach ($advanceStatus as $status) --}}
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                    {{-- @endforeach --}}
                </select>
            </div>

            <div class="col-md-6">
                <label for="filter-role">دور المستخدم</label>
                <select id="filter-role" class="form-control select2" data-placeholder="اختر الدور">
                    <option value=""></option>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

        </div>
        <hr class="mt-10">
        {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
    </div>
</div>
@endsection