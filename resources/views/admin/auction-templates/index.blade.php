@extends('layouts.dashboard')

@section('title', 'قوالب المزادات')
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
            const tableWrapper = $('#auction-templates-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#auction-templates-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#auction-templates-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد قالب واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'AuctionTemplate');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="auction-templates-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي القوالب"
                :value="$statistics['total']"
                subtitle="كل قوالب المزادات"
                icon="ti-templates"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قوالب منشورة"
                :value="$statistics['published']"
                subtitle="جاهزة للاستخدام"
                icon="ti-world-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قوالب مسودة"
                :value="$statistics['draft']"
                subtitle="قيد الإعداد والتطوير"
                icon="ti-pencil"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قوالب نشطة"
                :value="$statistics['active']"
                subtitle="مفعلة في النظام"
                icon="ti-toggle-right"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قوالب الكتيبات"
                :value="$statistics['brochure']"
                subtitle="كتيبات تسويقية"
                icon="ti-notebook"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auction-templates-table">
                <x-filter.select
                    id="filter-type"
                    filterKey="type"
                    table="#auction-templates-table"
                    label="نوع القالب"
                    placeholder="اختر النوع"
                    :options="$filterOptions['types']"
                />
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#auction-templates-table"
                    label="حالة النشر"
                    placeholder="اختر حالة النشر"
                    :options="$filterOptions['statuses']"
                />
                <x-filter.select
                    id="filter-active"
                    filterKey="is_active"
                    table="#auction-templates-table"
                    label="حالة التفعيل"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['is_active']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-templates-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
