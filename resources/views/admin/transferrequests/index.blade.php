@extends('admin.layouts.layoutMaster')

@section('title', 'طلبات التحويل البنكي')

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
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الطلبات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $all_transfer_requests }}</h4>
                            </div>
                            <small class="mb-0">جميع طلبات التحويل</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-clipboard-list ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">قيد الانتظار</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $pending_transfer_requests }}</h4>
                            </div>
                            <small class="mb-0">طلبات بانتظار المراجعة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الطلبات المقبولة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $approved_transfer_requests }}</h4>
                            </div>
                            <small class="mb-0">إجمالي الطلبات المقبولة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-circle-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الطلبات المرفوضة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $rejected_transfer_requests }}</h4>
                            </div>
                            <small class="mb-0">إجمالي الطلبات المرفوضة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-circle-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <x-data-table.filters table="#transfer-table">
        <x-filter.select id="filter-status" filterKey="status" label="حالة الطلب"
            placeholder="اختر حالة الطلب" :options="$filterOptions['statuses'] ?? []" />
        <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة"
            placeholder="اختر الباقة" :options="$filterOptions['plans'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'transfer-table'], false) !!}
        </div>
    </div>
@endsection
