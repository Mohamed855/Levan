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
    @vite(['resources/assets/js/admin/tickets/filter.js', 'resources/assets/js/admin/tickets/index.js'])
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

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label" for="filter-status">حالة التذكرة</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر حالة التذكرة">
                        <option value=""></option>
                        <option value="open">جديدة</option>
                        <option value="in_progress">قيد المعالجة</option>
                        <option value="resolved">تم الحل</option>
                        <option value="resolved_pending_survey">بانتظار التقييم</option>
                        <option value="reopened">معادة</option>
                        <option value="closed">مغلقة</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="filter-section">القسم</label>
                    <select id="filter-section" class="form-control select2" data-placeholder="اختر القسم">
                        <option value=""></option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="filter-tenant">الشركة / المستأجر</label>
                    <select id="filter-tenant" class="form-control select2" data-placeholder="اختر الشركة">
                        <option value=""></option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->company_name ?? $tenant->id }}</option>
                        @endforeach
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
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'admin-tickets-table'],
                false,
            ) !!}
        </div>
    </div>

@endsection
