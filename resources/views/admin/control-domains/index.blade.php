@extends('layouts.dashboard')

@section('title', 'نطاقات النظام')
@section('dashboard-scope', 'admin')

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
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        $(function () {
            const tableWrapper = $('#control-domains-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#control-domains-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#control-domains-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('يرجى تحديد نطاق واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'ControlDomain');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="control-domains-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي النطاقات"
                :value="$statistics['total']"
                subtitle="كل النطاقات المسجلة"
                icon="ti-world"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="نطاقات نشطة"
                :value="$statistics['active']"
                subtitle="متاحة للمستأجرين"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="نطاقات معطلة"
                :value="$statistics['inactive']"
                subtitle="موقوفة عن الاستخدام"
                icon="ti-circle-x"
                color="danger"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أضيفت هذا الشهر"
                :value="$statistics['added_this_month']"
                subtitle="خلال الشهر الحالي"
                icon="ti-calendar-event"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أضيفت هذا الأسبوع"
                :value="$statistics['added_this_week']"
                subtitle="خلال الـ 7 أيام الأخيرة"
                icon="ti-clock"
                color="warning"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#control-domains-table">
                <x-filter.select
                    id="filter-active"
                    filterKey="is_active"
                    table="#control-domains-table"
                    label="حالة التفعيل"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['is_active']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'control-domains-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
