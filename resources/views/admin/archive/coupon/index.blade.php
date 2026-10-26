@extends('admin.layouts.layoutMaster')

@section('title', 'ارشيف الكوبونات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            archiveIndex: "{{ route('admin.archives.coupons.index') }}",
            restore: "{{ route('admin.archives.coupons.restore', ['id' => ':id']) }}",
            forceDelete: "{{ route('admin.archives.coupons.force-delete', ['id' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/archive/coupons/index.js'])
@endsection

@section('content')
    <x-data-table.archive-page>
        <x-slot:filters>
            <x-data-table.filters table="#coupon-archive-table">
                <x-filter.select id="filter-status" filterKey="is_active" label="الحالة" placeholder="اختر الحالة" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-type" filterKey="discount_type" label="نوع الخصم" placeholder="اختر نوع الخصم"
                    :options="$filterOptions['types'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        {!! $dataTable->table(
            ['class' => 'table table-striped table-bordered w-100', 'id' => 'coupon-archive-table'],
            false,
        ) !!}
    </x-data-table.archive-page>
@endsection
