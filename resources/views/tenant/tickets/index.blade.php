@extends('layouts.dashboard')

@section('title', 'الدعم الفني')
@section('dashboard-scope', 'tenant')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-script')
    <script>
        $(function () {
            const tableWrapper = $('#tenant-tickets-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenant-tickets-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenant-tickets-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tenant-tickets-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي التذاكر"
                :value="$stats['total']"
                subtitle="كافة تذاكر الدعم الفني"
                icon="ti-headset"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تذاكر جديدة"
                :value="$stats['open']"
                subtitle="بانتظار رد الدعم"
                icon="ti-mail-opened"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قيد المعالجة"
                :value="$stats['in_progress']"
                subtitle="جاري العمل عليها"
                icon="ti-clock"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تم الحل"
                :value="$stats['resolved']"
                subtitle="تم حل المشكلة بنجاح"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="مغلقة"
                :value="$stats['closed']"
                subtitle="تذاكر منتهية ومغلقة"
                icon="ti-lock"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tenant-tickets-table" reset-id="btn-reset-filters">
                <div>
                    <label class="form-label" for="filter-department">القسم</label>
                    <select id="filter-department" class="form-select select2" data-datatable-filter="true" data-filter-key="department" data-placeholder="كل الأقسام">
                        <option value=""></option>
                        <option value="technical">الدعم الفني</option>
                        <option value="billing">الفواتير والمالية</option>
                        <option value="general">استفسارات عامة</option>
                        <option value="auctions">إدارة المزادات</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter-status">الحالة</label>
                    <select id="filter-status" class="form-select select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="كل الحالات">
                        <option value=""></option>
                        @foreach (\App\Enums\Admin\Tickets\TicketStatus::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </x-data-table.filters>
        </x-slot:filters>

        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-hover table-striped w-100', 'id' => 'tenant-tickets-table']) !!}
            </div>
        </div>
    </x-data-table.page>
@endsection
