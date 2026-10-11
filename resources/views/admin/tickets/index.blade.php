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
    @vite(['resources/assets/js/admin/tickets/index.js'])
@endsection

@section('content')
    <form id="toggle-service-form" action="{{ route('admin.tickets.toggleService') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="tickets_enabled" value="{{ $ticketsEnabled ? '0' : '1' }}">
    </form>

    <div class="row g-4 mb-4">

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي التذاكر" :value="number_format($stats['total'] ?? 0)" subtitle="إجمالي التذاكر"
            icon="ti-messages" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="تذاكر جديدة" :value="number_format($stats['open'] ?? 0)"
            subtitle="تحتاج للرد والمعالجة" icon="ti-bell-ringing" color="info" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="قيد المعالجة" :value="number_format($stats['in_progress'] ?? 0)" subtitle="جارٍ العمل عليها"
            icon="ti-clock" color="warning" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="تم الحل" :value="number_format($stats['resolved'] ?? 0)" subtitle="تم الانتهاء منها"
            icon="ti-circle-check" color="success" />
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

    <x-data-table.filters table="#admin-tickets-table">
        <x-filter.select id="filter-status" filterKey="status" label="حالة التذكرة"
            placeholder="اختر حالة التذكرة" :options="$filterOptions['statuses'] ?? []" />
        <x-filter.select id="filter-section" filterKey="section" label="القسم"
            placeholder="اختر القسم" :options="$filterOptions['sections'] ?? []" />
        <x-filter.select id="filter-tenant" filterKey="tenant_id" label="الشركة / المستأجر"
            placeholder="اختر الشركة" :options="$filterOptions['tenants'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'admin-tickets-table'],
                false,
            ) !!}
        </div>
    </div>

@endsection
