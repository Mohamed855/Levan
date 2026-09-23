@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp

@if (!isset($excludeJquery) || !$excludeJquery)
    <script src="{{ asset('assets/shared/js/cdn/jquery-init.js') }}"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script> --}}
@endif

@isset($configData['layout'])
    @include(
        $configData['layout'] === 'horizontal'
            ? 'tenants.layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'tenants.layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'tenants.layouts.layoutFront'
                    : 'tenants.layouts.contentNavbarLayout')))
@endisset

<script>
    var csrfToken = "{{ csrf_token() }}";
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if (Session::has('success'))
            toastr.success("{{ Session::get('success') }}");
        @endif

        @if (Session::has('error'))
            toastr.error("{{ Session::get('error') }}");
        @endif

        @if (Session::has('info'))
            toastr.info("{{ Session::get('info') }}");
        @endif

        @if (Session::has('warning'))
            toastr.warning("{{ Session::get('warning') }}");
        @endif
    });
</script>

<script>
        document.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-delete-btn');
                if (!btn) return;
                const id = btn.dataset.id;
                if (!id) return;

                confirmDelete(id);
            });
    /*
    |--------------------------------------------------------------------------
    | Confirm Delete Single Item
    |--------------------------------------------------------------------------
    */
    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            text: " لا يمكن التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true,
            showConfirmButton: true,
            showDenyButton: false,
            buttonsStyling: false,
            customClass: {
                popup: 'custom-popup',
                title: 'custom-title',
                text: 'custom-text',
                confirmButton: 'btn btn-success custom-confirm',
                cancelButton: 'btn btn-danger custom-cancel'
            },
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: false,
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>

<script>
    /*
    |--------------------------------------------------------------------------
    | Confirm Delete Selected Items
    |--------------------------------------------------------------------------
    */
    function confirmDeleteSelected(selectedIds, deleteUrl) {
        if (selectedIds.length === 0) {
            toastr.warning('لم تقم بتحديد أي صفوف.');
            return;
        }

        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            text: "لا يمكن التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true,
            showConfirmButton: true,
            showDenyButton: false,
            buttonsStyling: false,
            customClass: {
                popup: 'custom-popup',
                title: 'custom-title',
                text: 'custom-text',
                confirmButton: 'btn btn-success custom-confirm',
                cancelButton: 'btn btn-danger custom-cancel'
            },
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds,
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('تم حذف المحدد بنجاح.');
                            location.reload();
                        } else {
                            toastr.error('فشل في حذف المحدد.');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('حدث خطأ أثناء حذف المحدد.');
                    }
                });
            }
        });
    }
</script>

<script>
    /*
    |--------------------------------------------------------------------------
    | Confirm Delete Selected Mass with Password
    |--------------------------------------------------------------------------
    */
    function confirmDeleteSelectedmss(selectedIds, deleteUrl, modelName) {
        if (selectedIds.length === 0) {
            toastr.warning('لم تقم بتحديد أي صفوف.');
            return;
        }

        const isLargeDelete = selectedIds.length > 1000;

        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            html: `
                <form autocomplete="off" onsubmit="return false;">
                    <div style="text-align: center;">
                        <p style="margin-bottom: 15px; color: #6c757d; font-size: 14px;">
                            ${isLargeDelete ?
                                `سيتم حذف ${selectedIds.length} عنصر - ستكتمل العملية خلال لحظات` :
                                // 'لا يمكن التراجع عن هذا الإجراء!'
                                ''
                                }
                        </p>
                        <p style="margin-bottom: 10px; color: #666; font-size: 13px;">
                            يرجى إدخال كلمة المرور الخاصة بك لإتمام عملية الحذف
                        </p>
                        <input
                            type="password"
                            id="delete-password-confirm"
                            name="password-confirm-delete"
                            class="swal2-input"
                            placeholder="كلمة المرور"
                            autocomplete="off"
                            readonly
                            onfocus="this.removeAttribute('readonly');"
                            style="width: 85%; text-align: center; margin-top: 5px;">
                    </div>
                </form>
            `,
            icon: 'warning',
            showCancelButton: true,
            showConfirmButton: true,
            buttonsStyling: false,
            customClass: {
                popup: 'custom-popup',
                title: 'custom-title',
                htmlContainer: 'custom-text',
                confirmButton: 'btn btn-success custom-confirm',
                cancelButton: 'btn btn-danger custom-cancel'
            },
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: false,
            didOpen: () => {
                const input = document.getElementById('delete-password-confirm');
                setTimeout(() => {
                    input.removeAttribute('readonly');
                    input.focus();
                }, 100);
            },
            preConfirm: () => {
                const password = document.getElementById('delete-password-confirm').value;
                if (!password) {
                    Swal.showValidationMessage('يجب إدخال كلمة المرور');
                    return false;
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const password = result.value;

                $.ajax({
                    url: deleteUrl,
                    method: 'POST',
                    timeout: 60000,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds.join(','),
                        model: modelName,
                        password: password
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.is_queued) {
                                Swal.fire({
                                    title: 'جاري المعالجة...',
                                    text: 'سيتم تحديث الصفحة خلال لحظات',
                                    timer: 10000,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    buttonsStyling: false,
                                    customClass: {
                                        popup: 'custom-popup',
                                        title: 'custom-title',
                                        text: 'custom-text'
                                    },
                                    didOpen: () => {
                                        Swal.showLoading();
                                    },
                                    willClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                toastr.success(response.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            }
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) {
                            toastr.error('كلمة المرور غير صحيحة.');
                        } else {
                            toastr.error('حدث خطأ أثناء الحذف.');
                        }
                    }
                });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Show Processing Dialog
    |--------------------------------------------------------------------------
    */
    function showProcessingDialog(batchId) {
        Swal.fire({
            title: 'جاري الحذف...',
            text: 'يرجى عدم إغلاق الصفحة',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            buttonsStyling: false,
            customClass: {
                popup: 'custom-popup',
                title: 'custom-title',
                text: 'custom-text'
            },
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Check Deletion Status
    |--------------------------------------------------------------------------
    */
    function checkDeletionStatus(batchId) {
        const checkInterval = setInterval(() => {
            $.ajax({
                url: '/mass-delete/status',
                method: 'GET',
                data: {
                    batch_id: batchId
                },
                success: function(response) {
                    if (response.completed) {
                        clearInterval(checkInterval);
                        Swal.close();
                        toastr.success('تم الحذف بنجاح');
                        location.reload();
                    }
                },
                error: function() {
                    clearInterval(checkInterval);
                    Swal.close();
                    toastr.error('حدث خطأ في التحقق من حالة الحذف');
                    location.reload();
                }
            });
        }, 3000);

        setTimeout(() => {
            clearInterval(checkInterval);
            Swal.close();
            toastr.info('انتهت مهلة الانتظار - سيتم تحديث الصفحة');
            location.reload();
        }, 300000);
    }
</script>

<script>
    const userName = "{{ Auth::user()->name ?? '' }}";
</script>

<script>
    /*
    |--------------------------------------------------------------------------
    | Disable Autocomplete for DataTable Search
    |--------------------------------------------------------------------------
    */
    $(document).ready(function() {
        setTimeout(function() {
            $('input[type="search"]').each(function() {
                $(this).attr('autocomplete', 'off');
                $(this).attr('autocorrect', 'off');
                $(this).attr('autocapitalize', 'off');
                $(this).attr('spellcheck', 'false');
                $(this).attr('readonly', 'readonly');

                $(this).on('focus', function() {
                    $(this).removeAttr('readonly');
                });

                $(this).on('blur', function() {
                    if ($(this).val() === '') {
                        $(this).attr('readonly', 'readonly');
                    }
                });
            });

            $('.dataTables_filter input').each(function() {
                $(this).attr('autocomplete', 'off');
                $(this).attr('autocorrect', 'off');
                $(this).attr('autocapitalize', 'off');
                $(this).attr('spellcheck', 'false');
                $(this).attr('readonly', 'readonly');

                $(this).on('focus', function() {
                    $(this).removeAttr('readonly');
                });

                $(this).on('blur', function() {
                    if ($(this).val() === '') {
                        $(this).attr('readonly', 'readonly');
                    }
                });
            });
        }, 500);
    });
</script>
