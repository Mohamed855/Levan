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
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])

@endsection
@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
            updateSortOrder: "{{ route('admin.plans.update-sort-order') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/plans/filter.js', 'resources/assets/js/admin/plans/index.js'])

@endsection

@section('content')
    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="إجمالي الباقات" :value="$totalPlans" subtitle="إجمالي الباقات"
            icon="ti-packages" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="الباقات النشطة" :value="$activePlans" subtitle="الباقات النشطة"
            icon="ti-bolt" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="الباقات غير النشطة" :value="$inactivePlans"
            subtitle="الباقات غير النشطة" icon="ti-circle-off" color="warning" />
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
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label" for="filter-status">حالة الباقات</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر حالة الباقة">
                        <option value=""></option>
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="filter-domain">حالة الدومينات</label>
                    <select id="filter-domain" class="form-control select2" data-placeholder="اختر حالة الدومين">
                        <option value=""></option>
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'paln-table'], false) !!}
        </div>
    </div>
@endsection
