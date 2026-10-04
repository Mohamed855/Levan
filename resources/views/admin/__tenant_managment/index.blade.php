@extends('admin.layouts.layoutMaster')

@section('title', 'العملاء')

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
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/tenant_managment/index.js'])
@endsection
@section('content')
    <div class="row g-4 mb-4">
        <!-- إجمالي العملاء -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي العملاء</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $total }}</h4>
                            </div>
                            <small class="mb-0">عدد جميع العملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- العملاء باشتراك فعال -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات الفعالة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $with_active_subscription }}</h4>
                            </div>
                            <small class="mb-0">العملاء باشتراك فعال</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-bolt ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- العملاء باشتراك منتهي -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات المنتهية</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $with_expired_subscription }}</h4>
                            </div>
                            <small class="mb-0">العملاء باشتراك منتهي</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-alert-circle ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- العملاء بدون اشتراك -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">بدون اشتراك</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $without_subscription }}</h4>
                            </div>
                            <small class="mb-0">العملاء بدون أي اشتراك</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <x-data-table.filters table="#tenant-table">
        <x-filter.select id="filter-status" filterKey="status" label="حالة الاشتراك"
            placeholder="اختر حالة الاشتراك" :options="[
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'queued', 'label' => 'في الانتظار'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'cancelled', 'label' => 'ملغي'],
                ['value' => 'pending_payment', 'label' => 'في انتظار الدفع'],
            ]" />

        <x-filter.select id="filter-client" filterKey="statusClient" label="حالة العميل"
            placeholder="اختر حالة العميل" :options="[
                ['value' => '1', 'label' => 'نشط'],
                ['value' => '0', 'label' => 'محذوف'],
            ]" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tenant-table'], false) !!}
        </div>
    </div>
@endsection
