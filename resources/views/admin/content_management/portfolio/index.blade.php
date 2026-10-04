@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة المحافظ')

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
    @vite(['resources/assets/js/admin/portfolios/index.js'])
    <script>
        $(document).ready(function() {
            $(document).on('click', '.js-delete-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                confirmDelete(id);
            });
        });
    </script>
@endsection

@section('content')
    <div class="row g-4 mb-4">

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المحافظ</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['total'] }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المحافظ</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-briefcase ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المحافظ النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['active'] }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المحافظ النشطة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-discount-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المحافظ الغير نشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['inactive'] }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المحافظ الغير نشطة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-discount-off ti-26px"></i>
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

    {{-- Filters Card --}}
    <x-data-table.filters table="#portfolio-table">
        <x-filter.select id="filter-status" filterKey="status" label="حالة المحفظة"
            placeholder="اختر حالة المحفظة" :options="$filterOptions['statuses'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'portfolio-table'], false) !!}
        </div>
    </div>
@endsection
