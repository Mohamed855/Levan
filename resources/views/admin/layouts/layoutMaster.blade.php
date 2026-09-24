@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp


@if (!isset($excludeJquery) || !$excludeJquery)
    <script src="{{ asset('assets/shared/js/cdn/jquery-init.js') }}"></script>
@endif

@isset($configData['layout'])
    @include(
        $configData['layout'] === 'horizontal'
            ? 'admin.layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'admin.layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'admin.layouts.layoutFront'
                    : 'admin.layouts.contentNavbarLayout')))
@endisset

<script>
    var csrfToken = "{{ csrf_token() }}";
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined') {
            jQuery.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
        }
    });
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

    function confirmDelete(id) {
        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            text: " لا يمكن التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true, // يعرض زر الإلغاء
            showConfirmButton: true, // يعرض زر التأكيد
            showDenyButton: false, // لا يعرض زر الرفض
            buttonsStyling: false, // لتعطيل التنسيق الافتراضي للأزرار
            customClass: {
                popup: 'custom-popup', // تخصيص شكل النافذة
                title: 'custom-title', // تخصيص شكل العنوان
                text: 'custom-text', // تخصيص شكل النص
                confirmButton: 'btn btn-success custom-confirm', // تخصيص زر التأكيد
                cancelButton: 'btn btn-danger custom-cancel' // تخصيص زر الإلغاء
            },
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: false, // لعكس ترتيب الأزرار إذا رغبت
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>

<script>
    function confirmDeleteSelected(selectedIds, deleteUrl) {
        if (selectedIds.length === 0) {
            toastr.warning('لم تقم بتحديد أي صفوف.');
            return;
        }

        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            text: "لا يمكن التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true, // يعرض زر الإلغاء
            showConfirmButton: true, // يعرض زر التأكيد
            showDenyButton: false, // لا يعرض زر الرفض
            buttonsStyling: false, // لتعطيل التنسيق الافتراضي للأزرار
            customClass: {
                popup: 'custom-popup', // تخصيص شكل النافذة
                title: 'custom-title', // تخصيص شكل العنوان
                text: 'custom-text', // تخصيص شكل النص
                confirmButton: 'btn btn-success custom-confirm', // تخصيص زر التأكيد
                cancelButton: 'btn btn-danger custom-cancel' // تخصيص زر الإلغاء
            },
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: false, // لعكس ترتيب الأزرار إذا رغبت
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
                            location.reload(); // إعادة تحميل الصفحة لتحديث البيانات
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
    function confirmDeleteSelectedmss(selectedIds, deleteUrl, modelName) {
        if (selectedIds.length === 0) {
            toastr.warning('لم تقم بتحديد أي صفوف.');
            return;
        }

        const isLargeDelete = selectedIds.length > 1000;

        Swal.fire({
            title: 'هل أنت متأكد من عملية الحذف؟',
            text: isLargeDelete ?
                `سيتم حذف ${selectedIds.length} عنصر  - ستكتمل العملية خلال لحظات` :
                "لا يمكن التراجع عن هذا الإجراء!",
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
                    timeout: 60000,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: selectedIds.join(','),
                        model: modelName
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
                    error: function() {
                        toastr.error('حدث خطأ أثناء الحذف.');
                    }
                });
            }
        });
    }


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
    const userName = "{{ Auth::guard('admin')->user()->name ?? '' }}";
</script>
