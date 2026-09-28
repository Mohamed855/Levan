/**
 * Confirm Delete — SweetAlert2 helpers
 *
 * Replaces ALL inline <script> blocks in:
 *   - admin/layouts/layoutMaster.blade.php  (confirmDelete, confirmDeleteSelected,
 *                                            confirmDeleteSelectedmss, showProcessingDialog,
 *                                            checkDeletionStatus)
 *   - tenants/layouts/layoutMaster.blade.php (same functions — 100% identical logic,
 *                                             except confirmDeleteSelectedmss adds password field)
 *
 * Exposed on window.* so Blade onclick="confirmDelete(id)" still works.
 */

/* --------------------------------------------------------------------------
 | Swal shared config builder
 -------------------------------------------------------------------------- */
function _swalDefaults(overrides) {
    return Object.assign({
        icon:              'warning',
        showCancelButton:  true,
        showConfirmButton: true,
        showDenyButton:    false,
        buttonsStyling:    false,
        customClass: {
            popup:         'custom-popup',
            title:         'custom-title',
            text:          'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton:  'btn btn-danger custom-cancel',
        },
        confirmButtonText: 'تأكيد',
        cancelButtonText:  'إلغاء',
        reverseButtons:    false,
    }, overrides);
}

/* --------------------------------------------------------------------------
 | confirmDelete — single item via hidden form submit
 -------------------------------------------------------------------------- */
window.confirmDelete = function (id) {
    Swal.fire(_swalDefaults({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text:  'لا يمكن التراجع عن هذا الإجراء!',
    })).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
};

/* --------------------------------------------------------------------------
 | confirmDeleteSelected — bulk delete via AJAX (no password)
 -------------------------------------------------------------------------- */
window.confirmDeleteSelected = function (selectedIds, deleteUrl) {
    if (!selectedIds || selectedIds.length === 0) {
        toastr.warning('لم تقم بتحديد أي صفوف.');
        return;
    }

    Swal.fire(_swalDefaults({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text:  'لا يمكن التراجع عن هذا الإجراء!',
    })).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url:    deleteUrl,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids:    selectedIds,
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success('تم حذف المحدد بنجاح.');
                        location.reload();
                    } else {
                        toastr.error('فشل في حذف المحدد.');
                    }
                },
                error: function () {
                    toastr.error('حدث خطأ أثناء حذف المحدد.');
                },
            });
        }
    });
};

/* --------------------------------------------------------------------------
 | confirmDeleteSelectedmss — bulk delete via AJAX with password confirmation
 -------------------------------------------------------------------------- */
window.confirmDeleteSelectedmss = function (selectedIds, deleteUrl, modelName) {
    if (!selectedIds || selectedIds.length === 0) {
        toastr.warning('لم تقم بتحديد أي صفوف.');
        return;
    }

    var isLargeDelete = selectedIds.length > 1000;

    Swal.fire(_swalDefaults({
        title: 'هل أنت متأكد من عملية الحذف؟',
        html: `
            <form autocomplete="off" onsubmit="return false;">
                <div style="text-align: center;">
                    <p style="margin-bottom: 15px; color: #6c757d; font-size: 14px;">
                        ${isLargeDelete
                            ? `سيتم حذف ${selectedIds.length} عنصر - ستكتمل العملية خلال لحظات`
                            : ''}
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
        customClass: {
            popup:         'custom-popup',
            title:         'custom-title',
            htmlContainer: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton:  'btn btn-danger custom-cancel',
        },
        didOpen: function () {
            var input = document.getElementById('delete-password-confirm');
            setTimeout(function () {
                input.removeAttribute('readonly');
                input.focus();
            }, 100);
        },
        preConfirm: function () {
            var password = document.getElementById('delete-password-confirm').value;
            if (!password) {
                Swal.showValidationMessage('يجب إدخال كلمة المرور');
                return false;
            }
            return password;
        },
    })).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url:     deleteUrl,
                method:  'POST',
                timeout: 60000,
                data: {
                    _token:   $('meta[name="csrf-token"]').attr('content'),
                    ids:      selectedIds.join(','),
                    model:    modelName,
                    password: result.value,
                },
                success: function (response) {
                    if (response.success) {
                        if (response.is_queued) {
                            Swal.fire({
                                title:             'جاري المعالجة...',
                                text:              'سيتم تحديث الصفحة خلال لحظات',
                                timer:             10000,
                                allowOutsideClick: false,
                                allowEscapeKey:    false,
                                showConfirmButton:  false,
                                buttonsStyling:    false,
                                customClass: {
                                    popup: 'custom-popup',
                                    title: 'custom-title',
                                    text:  'custom-text',
                                },
                                didOpen: function () { Swal.showLoading(); },
                                willClose: function () { location.reload(); },
                            });
                        } else {
                            toastr.success(response.message);
                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 401) {
                        toastr.error('كلمة المرور غير صحيحة.');
                    } else {
                        toastr.error('حدث خطأ أثناء الحذف.');
                    }
                },
            });
        }
    });
};

/* --------------------------------------------------------------------------
 | showProcessingDialog — loading Swal while batch job runs
 -------------------------------------------------------------------------- */
window.showProcessingDialog = function (batchId) {
    Swal.fire({
        title:             'جاري الحذف...',
        text:              'يرجى عدم إغلاق الصفحة',
        allowOutsideClick: false,
        allowEscapeKey:    false,
        showConfirmButton:  false,
        buttonsStyling:    false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text:  'custom-text',
        },
        didOpen: function () { Swal.showLoading(); },
    });
};

/* --------------------------------------------------------------------------
 | checkDeletionStatus — polling until batch is done
 -------------------------------------------------------------------------- */
window.checkDeletionStatus = function (batchId) {
    var checkInterval = setInterval(function () {
        $.ajax({
            url:    '/mass-delete/status',
            method: 'GET',
            data:   { batch_id: batchId },
            success: function (response) {
                if (response.completed) {
                    clearInterval(checkInterval);
                    Swal.close();
                    toastr.success('تم الحذف بنجاح');
                    location.reload();
                }
            },
            error: function () {
                clearInterval(checkInterval);
                Swal.close();
                toastr.error('حدث خطأ في التحقق من حالة الحذف');
                location.reload();
            },
        });
    }, 3000);

    setTimeout(function () {
        clearInterval(checkInterval);
        Swal.close();
        toastr.info('انتهت مهلة الانتظار - سيتم تحديث الصفحة');
        location.reload();
    }, 300000);
};

/* --------------------------------------------------------------------------
 | Delete button — delegated click handler (replaces inline in layoutMaster)
 -------------------------------------------------------------------------- */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-delete-btn');
    if (!btn) return;
    var id = btn.dataset.id;
    if (!id) return;
    window.confirmDelete(id);
});
