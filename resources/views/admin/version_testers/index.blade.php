@extends('admin.layouts.layoutMaster')

@section('title', 'فريق اختبار الاصدارات')

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
            const tableWrapper = $('#version-testers-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#version-testers-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#version-testers-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد عضو واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'VersionTester');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <x-data-table.filters table="#version-testers-table">
        <x-filter.select id="filter-type" filterKey="type" label="نوع المختبر"
            placeholder="اختر نوع المختبر" :options="$filterOptions['types'] ?? []" />
        <x-filter.select id="filter-status" filterKey="is_active" label="الحالة"
            placeholder="اختر الحالة" :options="$filterOptions['statuses'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'version-testers-table'],
                false,
            ) !!}
        </div>
    </div>

@endsection
