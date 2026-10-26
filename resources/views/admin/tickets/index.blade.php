@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة التذاكر والدعم الفني')

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
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/tickets/index.js'])
@endsection

@section('content')
    <form id="toggle-service-form" action="{{ route('admin.tickets.toggleService') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="tickets_enabled" value="{{ $ticketsEnabled ? '0' : '1' }}">
    </form>

    <x-ui.validation-errors />

    <x-data-table.page table-id="admin-tickets-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي التذاكر" :value="number_format($stats['total'] ?? 0)" subtitle="إجمالي التذاكر المسجلة"
                icon="ti-messages" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تذاكر جديدة" :value="number_format($stats['open'] ?? 0)" subtitle="تحتاج للرد والمعالجة"
                icon="ti-bell-ringing" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="قيد المعالجة" :value="number_format($stats['in_progress'] ?? 0)" subtitle="جارٍ العمل عليها"
                icon="ti-clock" color="info" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تم الحل" :value="number_format($stats['resolved'] ?? 0)" subtitle="تم الانتهاء منها"
                icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="تذاكر مغلقة" :value="number_format($stats['closed'] ?? 0)" subtitle="مغلقة نهائياً"
                icon="ti-lock" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#admin-tickets-table">
                <x-filter.select id="filter-status" filterKey="status" label="حالة التذكرة" placeholder="اختر حالة التذكرة"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-section" filterKey="section" label="القسم" placeholder="اختر القسم"
                    :options="$filterOptions['sections'] ?? []" />
                <x-filter.select id="filter-tenant" filterKey="tenant_id" label="الشركة / المستأجر"
                    placeholder="اختر الشركة" :options="$filterOptions['tenants'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'admin-tickets-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
