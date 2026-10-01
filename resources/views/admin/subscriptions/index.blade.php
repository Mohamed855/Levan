@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة الاشتراكات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Dashboard Cards --}}
    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الاشتراكات" :value="$totalSubscriptions ?? 0"
            subtitle="جميع الاشتراكات الصادرة" icon="ti-packages" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الاشتراكات النشطة" :value="$activeSubscriptions ?? 0"
            subtitle="اشتراكات سارية حالياً" icon="ti-circle-check" color="success" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الاشتراكات المعلقة" :value="$pendingSubscriptions ?? 0"
            subtitle="اشتراكات بانتظار الدفع أو التفعيل" icon="ti-clock-pause" color="warning" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الاشتراكات المنتهية" :value="$expiredSubscriptions ?? 0"
            subtitle="اشتراكات منتهية أو ملغاة" icon="ti-clock-x" color="danger" />
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="form-label" for="filter-sub-status">حالة الاشتراك</label>
                    <select id="filter-sub-status" class="form-control select2" data-placeholder="اختر حالة الاشتراك">
                        <option value=""></option>
                        <option value="active">نشط</option>
                        <option value="pending">معلق</option>
                        <option value="suspended">موقوف</option>
                        <option value="expired">منتهي</option>
                        <option value="trial">تجريبي</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-plan">الباقة</label>
                    <select id="filter-plan" class="form-control select2" data-placeholder="اختر الباقة">
                        <option value=""></option>
                        @foreach ($plans ?? [] as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-city">مدينة الشركة</label>
                    <select id="filter-city" class="form-control select2" data-placeholder="اختر المدينة">
                        <option value=""></option>
                        @foreach ($cities ?? [] as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-expiring">قرب الانتهاء</label>
                    <select id="filter-expiring" class="form-control select2" data-placeholder="قرب الانتهاء">
                        <option value=""></option>
                        <option value="7">تنتهي خلال 7 أيام</option>
                        <option value="30">تنتهي خلال 30 يوماً</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'subscriptions-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.select2').select2({
                allowClear: true,
                dir: 'rtl'
            });

            const tableId = 'subscriptions-table';

            function getTable() {
                return window.LaravelDataTables ? window.LaravelDataTables[tableId] : null;
            }

            function applyFilters() {
                const table = getTable();
                if (!table) return;

                const url = new URL(table.ajax.url(), window.location.origin);
                const subStatus = $('#filter-sub-status').val();
                const plan = $('#filter-plan').val();
                const city = $('#filter-city').val();
                const expiring = $('#filter-expiring').val();

                if (subStatus) url.searchParams.set('status', subStatus);
                else url.searchParams.delete('status');
                if (plan) url.searchParams.set('plan_id', plan);
                else url.searchParams.delete('plan_id');
                if (city) url.searchParams.set('city_id', city);
                else url.searchParams.delete('city_id');
                if (expiring) url.searchParams.set('expiring_in', expiring);
                else url.searchParams.delete('expiring_in');

                table.ajax.url(url.toString()).load();
            }

            $('#filter-sub-status, #filter-plan, #filter-city, #filter-expiring').on('change', applyFilters);

            $('#btn-reset-filters').on('click', function() {
                $('#filter-sub-status, #filter-plan, #filter-city, #filter-expiring').val('').trigger(
                    'change');
            });

            // Toggle subscription status
            $(document).on('change', '.js-subscription-status-toggle', function() {
                const checkbox = $(this);
                const url = checkbox.data('url');
                checkbox.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        checkbox.prop('disabled', false);
                        Swal.fire({
                            icon: 'success',
                            title: res.message || 'تم تحديث حالة الاشتراك بنجاح',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2500
                        });
                        const table = getTable();
                        if (table) table.ajax.reload(null, false);
                    },
                    error: function() {
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        Swal.fire({
                            icon: 'error',
                            title: 'تعذر تغيير حالة الاشتراك',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            });

            // Delete subscription confirmation
            $(document).on('click', '.js-delete-subscription-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const number = $(this).data('number');
                const form = document.getElementById('delete-subscription-form-' + id);

                Swal.fire({
                    title: 'حذف نهائي للاشتراك؟',
                    text: 'سيتم حذف الاشتراك رقم (' + number + ') نهائياً مع كافة فتراته ومقاعده.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف نهائي',
                    cancelButtonText: 'إلغاء',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed && form) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
