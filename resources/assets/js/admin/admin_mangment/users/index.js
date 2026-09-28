
'use strict';

$(document).ready(function () {
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#users-table').on('init.dt', function () {
        bindTableEvents();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            confirmDelete(id);
        });
    });
});

function initializeComponents() {
    $('.filters .select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('.card-body')
        });
    });
}

function bindTableEvents() {
    const table = $('#users-table').DataTable();

    // الفلاتر
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.draw();
    });

    const tableWrapper = $('#users-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد مستخدم واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'AdminUser');
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-toggle-btn');
    if (!btn) return;

    const userId = btn.dataset.id;
    const url = btn.dataset.url;

    if (!userId || !url) {
        toastr.error('بيانات المستخدم غير مكتملة');
        return;
    }

    toggleStatus(btn, userId, url);
});

function toggleStatus(button, userId, url) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content
        || window.csrfToken;

    if (!csrfToken) {
        toastr.error('رمز الأمان مفقود');
        return;
    }

    const isCheckbox = button && (button.type === 'checkbox' || button.tagName === 'INPUT');
    const originalHtml = (!isCheckbox && button) ? button.innerHTML : null;

    if (button) {
        if (isCheckbox) {
            button.disabled = true;
        } else {
            button.classList.add('disabled');
            button.innerHTML = '<i class="ti ti-loader ti-spin"></i>';
        }
    }

    $.ajax({
        url: url,
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'تم تغيير الحالة بنجاح');
                if (button && isCheckbox) {
                    button.disabled = false;
                } else {
                    $('#users-table')
                        .DataTable()
                        .ajax.reload(null, false);
                }
            } else {
                toastr.error(response.message || 'حدث خطأ ما');
                if (button && isCheckbox) {
                    button.checked = !button.checked;
                    button.disabled = false;
                }
            }
        },
        error: function (xhr) {
            let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';

            if (xhr.status === 403) {
                errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
            } else if (xhr.status === 404) {
                errorMessage = 'المستخدم غير موجود';
            } else if (xhr.status === 419) {
                errorMessage = 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة';
            } else if (xhr.status === 422) {
                errorMessage = 'بيانات غير صالحة';
            } else if (xhr.responseJSON?.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage);
            if (button && isCheckbox) {
                button.checked = !button.checked;
                button.disabled = false;
            }
        },
        complete: function () {
            if (button && !isCheckbox) {
                button.classList.remove('disabled');
                button.innerHTML = originalHtml;
            }
        }
    });
}


// تصدير الدوال للاستخدام العام
window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;
