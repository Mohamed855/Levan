@extends('layouts.dashboard')

@section('title', 'إدارة الاشتراكات')
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
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
            massAccept: "{{ route('admin.subscriptions.mass-accept') }}",
            massReject: "{{ route('admin.subscriptions.mass-reject') }}"
        };

        function selectedSubscriptionIds() {
            return $('.row-checkbox:checked').map(function () { return $(this).val(); }).get();
        }

        async function reviewSelectedSubscriptions(mode) {
            const ids = selectedSubscriptionIds();
            if (ids.length === 0) { toastr.warning('يرجى تحديد اشتراك واحد على الأقل'); return; }
            const reason = mode === 'reject' ? window.prompt('سبب الرفض (اختياري):') : null;
            if (mode === 'reject' && reason === null) return;
            const response = await fetch(mode === 'accept' ? window.routes.massAccept : window.routes.massReject, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ ids, reason })
            });
            const payload = await response.json();
            if (!response.ok) { toastr.error(payload.message || 'تعذر إتمام المراجعة.'); return; }
            toastr.success(payload.message);
            $('#subscriptions-table').DataTable().ajax.reload(null, false);
        }
        window.reviewSelectedSubscriptions = reviewSelectedSubscriptions;

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('يرجى تحديد اشتراك واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Subscription');
            }
        }
        window.handleMassDelete = handleMassDelete;

        $(document).ready(function () {
            const tableWrapper = $('#subscriptions-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#subscriptions-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#subscriptions-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="subscriptions-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي الاشتراكات" :value="$stats['total'] ?? 0" subtitle="جميع الاشتراكات" icon="ti-packages"
                color="primary" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الاشتراكات النشطة" :value="$stats['active'] ?? 0" subtitle="سارية حالياً" icon="ti-circle-check"
                color="success" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الاشتراكات المعلقة" :value="$stats['pending'] ?? 0" subtitle="بانتظار الإجراء" icon="ti-clock-pause"
                color="warning" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الاشتراكات المنتهية" :value="$stats['expired'] ?? 0" subtitle="منتهية أو ملغاة" icon="ti-clock-x"
                color="danger" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="أوشكت على الانتهاء" :value="$stats['expiring'] ?? 0" subtitle="خلال 30 يوماً" icon="ti-alert-triangle"
                color="info" col="col-sm-6 col-xl" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#subscriptions-table">
                <x-filter.select id="filter-status" filterKey="status" label="حالة الاشتراك" placeholder="اختر حالة الاشتراك"
                    :options="$filterOptions['statuses'] ?? []" :value="$initialStatus ?? null" />
                <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة" placeholder="اختر الباقة"
                    :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-expiring" filterKey="expiring_in" label="قرب الانتهاء" placeholder="قرب الانتهاء"
                    :options="$filterOptions['expiring'] ?? []" :value="$initialExpiring ?? null" />
            </x-data-table.filters>
            @can('subscriptions.update')
                @if(($initialStatus ?? null) === 'pending')
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-success" onclick="reviewSelectedSubscriptions('accept')"><i class="ti ti-check me-1"></i> قبول المحدد</button>
                    <button type="button" class="btn btn-outline-danger" onclick="reviewSelectedSubscriptions('reject')"><i class="ti ti-x me-1"></i> رفض المحدد</button>
                </div>
                @endif
            @endcan
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'subscriptions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
