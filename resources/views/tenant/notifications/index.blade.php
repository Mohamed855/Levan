@extends('layouts.dashboard')

@section('title', 'الإشعارات')
@section('dashboard-scope', 'tenant')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-script')
    <script>
        const notificationRoutes = {
            markAsRead: "{{ route('tenant.notifications.mark-as-read') }}",
            markAllAsRead: "{{ route('tenant.notifications.mark-all-read') }}",
            destroy: "{{ route('tenant.notifications.destroy') }}",
            massDelete: "{{ route('tenant.notifications.mass-delete') }}"
        };

        $(function () {
            const tableWrapper = $('#tenant-notifications-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenant-notifications-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenant-notifications-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });

        window.markAsRead = function(id) {
            $.post(notificationRoutes.markAsRead, {
                _token: '{{ csrf_token() }}',
                id: id
            }).done(function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#tenant-notifications-table').DataTable().ajax.reload(null, false);
                }
            }).fail(function() {
                toastr.error('حدث خطأ أثناء تحديث حالة الإشعار');
            });
        };

        window.markAllAsRead = function() {
            $.post(notificationRoutes.markAllAsRead, {
                _token: '{{ csrf_token() }}'
            }).done(function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#tenant-notifications-table').DataTable().ajax.reload(null, false);
                }
            }).fail(function() {
                toastr.error('حدث خطأ أثناء تحديث الإشعارات');
            });
        };

        window.deleteNotification = function(id) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف هذا الإشعار نهائياً.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: notificationRoutes.destroy,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE',
                            id: id
                        },
                        success: function(res) {
                            if (res.success) {
                                toastr.success(res.message);
                                $('#tenant-notifications-table').DataTable().ajax.reload(null, false);
                            }
                        },
                        error: function() {
                            toastr.error('حدث خطأ أثناء حذف الإشعار');
                        }
                    });
                }
            });
        };

        window.handleNotificationMassDelete = function() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد إشعار واحد على الأقل للحذف.');
                return;
            }

            Swal.fire({
                title: 'حذف الإشعارات المحددة',
                text: 'هل أنت متأكد من حذف ' + selectedIds.length + ' إشعار؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف المحدد',
                cancelButtonText: 'إلغاء',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.post(notificationRoutes.massDelete, {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds
                    }).done(function(res) {
                        if (res.success) {
                            toastr.success(res.message);
                            $('#tenant-notifications-table').DataTable().ajax.reload(null, false);
                            $('#select-all').prop('checked', false);
                        }
                    }).fail(function() {
                        toastr.error('حدث خطأ أثناء حذف الإشعارات المحددة');
                    });
                }
            });
        };
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tenant-notifications-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الإشعارات"
                :value="$stats['total']"
                subtitle="كافة التنبيهات المستلمة"
                icon="ti-bell"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="غير مقروءة"
                :value="$stats['unread']"
                subtitle="بانتظار الاطلاع"
                icon="ti-bell-ringing"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="مقروءة"
                :value="$stats['read']"
                subtitle="تمت مراجعتها مسبقاً"
                icon="ti-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إشعارات اليوم"
                :value="$stats['today']"
                subtitle="تنبيهات وردت اليوم"
                icon="ti-calendar-event"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إشعارات النظام"
                :value="$stats['system']"
                subtitle="تحديثات المنصة"
                icon="ti-speakerphone"
                color="danger"
            />
        </x-slot:stats>

        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-hover table-striped w-100', 'id' => 'tenant-notifications-table']) !!}
            </div>
        </div>
    </x-data-table.page>
@endsection
