@extends('admin.layouts.layoutMaster')

@section('title', 'الاصدارات')

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
    @vite(['resources/assets/js/admin/shared-status-toggle.js'])
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        $(document).ready(function () {
            const tableWrapper = $('#versions-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#versions-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#versions-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد إصدارة واحدة على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Version');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    <x-ui.validation-errors />

    <x-data-table.page table-id="versions-table">
        <x-slot:filters>
            <x-data-table.filters table="#versions-table">
                <x-filter.select id="filter-stage" filterKey="stage" label="المرحلة"
                    placeholder="اختر المرحلة" :options="$filterOptions['stages'] ?? []" />
                <x-filter.select id="filter-status" filterKey="is_active" label="الحالة"
                    placeholder="اختر الحالة" :options="$filterOptions['statuses'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'versions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>

@endsection
