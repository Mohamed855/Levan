@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة العملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/clients/index.js'])
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="clients-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي العملاء" :value="$totalClients ?? ($totalCompanies ?? 0)" subtitle="جميع العملاء المسجلين" icon="ti-building"
                color="primary" col="col-sm-6 col-xl" />

            <x-ui.stat-card title="العملاء النشطون" :value="$activeClients ?? ($activeCompanies ?? 0)" subtitle="عملاء مفعلون ونشطون" icon="ti-circle-check"
                color="success" col="col-sm-6 col-xl" />

            <x-ui.stat-card title="العملاء الموقوفون" :value="$suspendedClients ?? ($suspendedCompanies ?? 0)" subtitle="عملاء موقوفون مؤقتاً" icon="ti-ban"
                color="danger" col="col-sm-6 col-xl" />

            <x-ui.stat-card title="بانتظار التفعيل" :value="$pendingClients ?? ($pendingCompanies ?? 0)" subtitle="عملاء جدد بانتظار الاعتماد"
                icon="ti-clock-pause" color="warning" col="col-sm-6 col-xl" />

            <x-ui.stat-card title="بدون اشتراك" :value="$withoutSubscriptionClients ?? ($withoutSubscriptionCompanies ?? 0)" subtitle="عملاء بلا اشتراك فعّال" icon="ti-alert-circle"
                color="info" col="col-sm-6 col-xl" />

        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#clients-table">
                <x-filter.select id="filter-sub-status" filterKey="subscription_status" label="حالة آخر اشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['subscriptionStatuses'] ?? []" />
                <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة" placeholder="اختر الباقة"
                    :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-city" filterKey="city_id" label="المدينة" placeholder="اختر المدينة"
                    :options="$filterOptions['cities'] ?? []" />
                <x-filter.select id="filter-expiring" filterKey="expiring_in" label="انتهاء الاشتراك"
                    placeholder="انتهاء الاشتراك" :options="$filterOptions['expiring'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="من تاريخ الإنشاء" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="إلى تاريخ الإنشاء" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'clients-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Delete client confirmation
            $(document).on('click', '.js-delete-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const form = document.getElementById('delete-form-' + id);

                if (!form) return;

                window.confirmAction({
                    title: 'هل أنت متأكد من حذف هذا العميل؟',
                    text: 'سيتم نقل العميل للأرشيف وحذف جميع اشتراكاته نهائياً.',
                    confirmButtonText: 'نعم، حذف العميل',
                    confirmButtonClass: 'btn btn-danger',
                    icon: 'ti ti-alert-triangle text-danger',
                    onConfirm: function() {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
