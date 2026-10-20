@extends('tenant.layouts.layoutMaster')

@section('title', 'جميع الإشعارات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            massDelete: "{{ tenant_route_name('notifications.mass-delete') }}",
            markAsRead: "{{ tenant_route_name('notifications.mark-as-read', ['id' => '__ID__']) }}",
            markAllAsRead: "{{ tenant_route_name('notifications.mark-all-read') }}",
            deleteNotification: "{{ tenant_route_name('notifications.destroy', ['id' => '__ID__']) }}",
            unreadCount: "{{ tenant_route_name('notifications.unread-count') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenant/notifications/all.js'])
@endsection


@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">جميع الإشعارات</h5>
        </div>
        <div class="card-body">
            {{-- <div class="row g-4 mb-3">
            <div class="col-md-6">
                <label for="filter-type" class="form-label">نوع الإشعار</label>
                <select id="filter-type" class="form-control select2" data-placeholder="الكل">
                <option value=""></option>
                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Notifications\NotificationType::class" />
            </select>
            </div>

            <div class="col-md-6">
                <label for="filter-status" class="form-label">حالة الإشعار</label>
                <select id="filter-status" class="form-control select2" data-placeholder="الكل">
                <option value=""></option>
                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Notifications\ReadStatus::class" />
            </select>
            </div>
        </div> --}}

            {{--
        <hr class="mt-10"> --}}
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'notifications-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
