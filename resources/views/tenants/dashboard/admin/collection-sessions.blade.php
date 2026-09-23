@extends('tenants.layouts.layoutMaster')

@section('title', 'جلسات التحصيل اليومية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    {!! $dataTable->scripts() !!}
    @vite(['resources/assets/js/tenants/dashboard/admin/collection-sessions.js'])
    <script>
        window.routes = {
            exportSessions: "{{ tenant_route_name('dashboard.exportSessions') }}",
        };
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="card-title mb-1">جلسات التحصيل اليومية</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="input-group" style="width: 180px;">
                            <input type="date" id="sessionDateFilter" class="form-control form-control-sm"
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <select id="sessionStatusFilter" class="form-select form-select-sm" style="width: 160px;">
                            <option value="">كل الحالات</option>
                            <option value="active">المتواجدون</option>
                            <option value="inactive">غير متواجدين</option>
                        </select>
                        <button type="button" id="exportSessionsExcel" class="btn btn-primary btn-sm">
                            <i class="ti ti-download me-1"></i>
                            تصدير Excel</button>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-striped table-hover table-bordered nowrap', 'id' => 'collection-sessions-table'], false) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
