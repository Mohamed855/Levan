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
    <!-- Toggle Service -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><i class="ti ti-settings me-2"></i> حالة خدمة التذاكر</h6>
                    <small class="text-muted">عند الإيقاف، لن يتمكن المستأجرون من إنشاء تذاكر جديدة</small>
                </div>
                <form action="{{ route('admin.tickets.toggleService') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="tickets_enabled" value="{{ $ticketsEnabled ? '0' : '1' }}">
                    <button type="submit" class="btn btn-sm {{ $ticketsEnabled ? 'btn-success' : 'btn-danger' }}">
                        <i class="ti {{ $ticketsEnabled ? 'ti-toggle-right' : 'ti-toggle-left' }} me-1"></i>
                        {{ $ticketsEnabled ? 'الخدمة مفعلة' : 'الخدمة معطلة' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- إجمالي التذاكر -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي التذاكر</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($stats['total'] ?? 0) }}</h4>
                            </div>
                            <small class="mb-0">كل تذاكر الدعم</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-messages ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تذاكر جديدة / مفتوحة -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">تذاكر جديدة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-danger">{{ number_format($stats['open'] ?? 0) }}</h4>
                            </div>
                            <small class="mb-0">تحتاج للرد والمعالجة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-bell-ringing ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قيد المعالجة -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">قيد المعالجة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-warning">{{ number_format($stats['in_progress'] ?? 0) }}</h4>
                            </div>
                            <small class="mb-0">جارٍ العمل عليها</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تم الحل -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">تم الحل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success">{{ number_format($stats['resolved'] ?? 0) }}</h4>
                            </div>
                            <small class="mb-0">تم الانتهاء منها</small>
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

                <div class="col-md-4">
                    <label for="filter-status">حالة التذكرة</label>
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
                    <label for="filter-section">القسم</label>
                    <select id="filter-section" class="form-control select2" data-placeholder="اختر القسم">
                        <option value=""></option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filter-tenant">المستأجر</label>
                    <select id="filter-tenant" class="form-control select2" data-placeholder="اختر المستأجر">
                        <option value=""></option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->company_name ?? $tenant->id }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
