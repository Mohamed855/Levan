@extends('layouts.dashboard')

@section('title', 'طلبات التواصل')
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
            const tableWrapper = $('#contact-requests-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#contact-requests-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#contact-requests-table').on('draw.dt', function () {
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
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'ContactRequest');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="contact-requests-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الطلبات"
                :value="$stats['total']"
                subtitle="كل الرسائل الواردة"
                icon="ti-messages"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="بانتظار الرد"
                :value="$stats['pending']"
                subtitle="تحتاج لمتابعة"
                icon="ti-clock"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تم الرد"
                :value="$stats['replied']"
                subtitle="تمت معالجتها"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات اليوم"
                :value="$stats['today']"
                subtitle="وارد خلال 24 ساعة"
                icon="ti-calendar-event"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="طلبات الشهر"
                :value="$stats['this_month']"
                subtitle="خلال الشهر الحالي"
                icon="ti-calendar"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#contact-requests-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#contact-requests-table"
                    label="الحالة"
                    placeholder="اختر الحالة"
                    :options="[
                        ['value' => 'pending', 'label' => 'بانتظار الرد'],
                        ['value' => 'replied', 'label' => 'تم الرد']
                    ]"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'contact-requests-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
