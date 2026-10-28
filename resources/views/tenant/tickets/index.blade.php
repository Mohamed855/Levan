@extends('tenant.layouts.layoutMaster')

@section('title', 'التذاكر')

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
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="tickets-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي التذاكر" :value="number_format($stats['total'] ?? 0)"
                subtitle="جميع تذاكر الدعم الخاصة بك" icon="ti-messages" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تذاكر جديدة" :value="number_format($stats['open'] ?? 0)" subtitle="بانتظار رد الدعم الفني"
                icon="ti-bell-ringing" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="قيد المعالجة" :value="number_format($stats['in_progress'] ?? 0)" subtitle="جارٍ العمل على حلها"
                icon="ti-clock" color="info" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تم الحل" :value="number_format($stats['resolved'] ?? 0)" subtitle="تم حل المشكلة"
                icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تذاكر مغلقة" :value="number_format($stats['closed'] ?? 0)" subtitle="مغلقة نهائياً"
                icon="ti-lock" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tickets-table" reset-id="btn-reset-filters">
                <x-filter.select id="filter-status" filterKey="status" label="حالة التذكرة" placeholder="اختر حالة التذكرة"
                    :enum="\App\Enums\Tenants\Tickets\TicketStatus::class" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tickets-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
