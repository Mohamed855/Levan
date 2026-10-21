@extends('layouts.dashboard')

@section('title', 'الفواتير')
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
            const tableWrapper = $('#tenant-bills-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenant-bills-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenant-bills-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tenant-bills-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الفواتير"
                :value="$stats['total']"
                subtitle="كافة الفواتير الصادرة"
                icon="ti-receipt-2"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="الفواتير المسددة"
                :value="$stats['paid']"
                subtitle="المدفوعة بنجاح"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قيد التحقق"
                :value="$stats['pending_verification']"
                subtitle="بانتظار مراجعة الإدارة"
                icon="ti-clock-check"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="بانتظار السداد"
                :value="$stats['unpaid']"
                subtitle="فواتير غير مدفوعة"
                icon="ti-alert-circle"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="متأخرة / ملغاة"
                :value="$stats['overdue']"
                subtitle="متأخرات وإلغاءات"
                icon="ti-receipt-off"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tenant-bills-table" reset-id="btn-reset-filters">
                <div>
                    <label class="form-label" for="filter-type">نوع العملية</label>
                    <select id="filter-type" class="form-select select2" data-datatable-filter="true" data-filter-key="type" data-placeholder="كل الأنواع">
                        <option value=""></option>
                        <option value="subscription">اشتراك جديد</option>
                        <option value="renewal">تجديد اشتراك</option>
                        <option value="upgrade">ترقية باقة</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter-status">حالة الفاتورة</label>
                    <select id="filter-status" class="form-select select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="كل الحالات">
                        <option value=""></option>
                        @foreach (\App\Enums\Admin\Bills\BillStatus::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter-payment-method">طريقة الدفع</label>
                    <select id="filter-payment-method" class="form-select select2" data-datatable-filter="true" data-filter-key="payment_method" data-placeholder="كل الطرق">
                        <option value=""></option>
                        @foreach (\App\Enums\Admin\Bills\PaymentMethod::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </x-data-table.filters>
        </x-slot:filters>

        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-hover table-striped w-100', 'id' => 'tenant-bills-table']) !!}
            </div>
        </div>
    </x-data-table.page>
@endsection
