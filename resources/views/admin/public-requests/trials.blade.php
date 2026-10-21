@extends('layouts.dashboard')

@section('title', 'طلبات التجربة')
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
            const tableWrapper = $('#trial-requests-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#trial-requests-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#trial-requests-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد طلب واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'TrialRequest');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="trial-requests-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الطلبات"
                :value="$stats['total']"
                subtitle="كل طلبات التجربة"
                icon="ti-test-pipe"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات معلّقة"
                :value="$stats['pending']"
                subtitle="بانتظار المراجعة"
                icon="ti-clock"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات مقبولة"
                :value="$stats['approved']"
                subtitle="تم إنشاء الحسابات"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات مرفوضة"
                :value="$stats['rejected']"
                subtitle="تم رفضها"
                icon="ti-circle-x"
                color="danger"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات هذا الشهر"
                :value="$stats['this_month']"
                subtitle="خلال الشهر الحالي"
                icon="ti-calendar"
                color="info"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#trial-requests-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#trial-requests-table"
                    label="الحالة"
                    placeholder="اختر الحالة"
                    :options="[
                        ['value' => 'pending', 'label' => 'معلّق'],
                        ['value' => 'approved', 'label' => 'مقبول'],
                        ['value' => 'rejected', 'label' => 'مرفوض']
                    ]"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'trial-requests-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
