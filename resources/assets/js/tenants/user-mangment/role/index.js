/**
 * Roles Index - JavaScript Functions
 * ===================================
 * إدارة جدول المستخدمين والأدوار والتفاعلات
 */

'use strict';

// متغيرات عامة
let usersTable;

$(document).ready(function () {
    initializeComponents();
    initializeDataTable();
    bindEvents();
});

/**
 * تهيئة المكونات الأساسية
 */
function initializeComponents() {
    // تهيئة Select2
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('#card-users')
        });
    });

    // تهيئة Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({
        container: 'body',
        boundary: 'window',
        popperConfig: function(defaultConfig) {
            return Object.assign({}, defaultConfig, { strategy: 'fixed' });
        }
    });
}

/**
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
    usersTable = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.rolesIndex,
            data: function (d) {
                d.status = $('#filter-status').val();
                d.role = $('#filter-role').val();
            }
        },
        columns: getTableColumns(),
        order: [[8, 'desc']],
        dom: getTableDom(),
        buttons: getTableButtons(),
        language: {
            url: window.assets.arabicDataTable
        },
        orderCellsTop: true,
        columnDefs: getColumnDefs(),
        responsive: getResponsiveConfig(),
        createdRow: handleTableRowCreation
    });
}

/**
 * تعريف أعمدة الجدول
 */
function getTableColumns() {
    return [
        {
            data: "DT_RowIndex",
            name: "DT_RowIndex",
            orderable: false,
            searchable: false,
            className: 'control'
        },
        {
            data: 'checkbox',
            name: 'checkbox',
            orderable: false,
            searchable: false,
            className: 'custom-checkbox'
        },
        {
            data: 'image',
            name: 'image',
            orderable: false,
            searchable: false,
            className: 'text-center'
        },
        {
            data: 'name',
            name: 'name',
            className: 'table-ellipsis'
        },
        {
            data: 'email',
            name: 'email',
            className: 'table-ellipsis'
        },
        {
            data: 'phone',
            name: 'phone',
            render: function (data, type, row) {
                return data && data.trim() !== '' ? data : 'لا يوجد';
            }
        },
        {
            data: 'role',
            name: 'role',
            orderable: false,
            searchable: false
        },
        {
            data: 'status',
            name: 'status'
        },
        {
            data: 'created_at_formatted',
            name: 'created_at'
        }
    ];
}

/**
 * تعريف DOM للجدول
 */
function getTableDom() {
    return '<"dt-toolbar mb-3"' +
        '<"dt-toolbar-left d-flex flex-column flex-sm-row align-items-start align-items-sm-center"' +
        'f' +
        'l' +
        '>' +
        '<"dt-toolbar-right d-flex flex-column flex-sm-row align-items-end align-items-sm-center justify-content-between"' +
        'B' +
        '>' +
        '>' +
        'rt' +
        '<"row mt-3"' +
        '<"d-flex flex-column flex-sm-row align-items-center justify-content-between w-100"' +
        'i' +
        '<"pagination-wrapper overflow-auto w-100"' +
        'p' +
        '>' +
        '>' +
        '>';
}

/**
 * تعريف أزرار الجدول
 */
function getTableButtons() {
    return [
        {
            extend: 'collection',
            className: 'btn btn-export',
            text: 'الإجراءات',
            buttons: [
                {
                    extend: 'copy',
                    text: 'نسخ'
                },
                {
                    extend: 'excel',
                    text: 'إكسل'
                }
            ]
        }
    ];
}

/**
 * تعريف أعمدة الجدول
 */
function getColumnDefs() {
    return [{
        className: "control",
        orderable: false,
        targets: 0,
        render: function () {
            return "";
        }
    }];
}

/**
 * تعريف الاستجابة للجدول
 */
function getResponsiveConfig() {
    return {
        details: {
            display: $.fn.dataTable.Responsive.display.modal({
                header: function () {
                    return "التفاصيل";
                }
            }),
            type: "column",
            renderer: function (api, rowIdx, columns) {
                const data = $.map(columns, function (col) {
                    return col.title !== "" ?
                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                        '<td>' + col.title + ':</td>' +
                        '<td>' + col.data + '</td>' +
                        '</tr>' : "";
                }).join("");

                return data ? $('<table class="table"/><tbody />').append(data) : false;
            }
        }
    };
}

/**
 * معالجة إنشاء صفوف الجدول
 */
function handleTableRowCreation(row, data, dataIndex) {
    $(row).find('td.table-ellipsis').each(function () {
        const cellContent = $(this).html();
        if ($(this).find('a').length > 0) {
            const linkElement = $(this).find('a');
            const linkText = linkElement.text();
            linkElement.html('<div class="cell-content"><span>' + linkText + '</span></div>');
        } else {
            $(this).html('<div class="cell-content"><span>' + cellContent + '</span></div>');
        }
    });
}

/**
 * ربط الأحداث
 */
function bindEvents() {
    // تحديث الجدول عند تغيير الفلاتر
    $('#filter-status, #filter-role').on('change', function () {
        usersTable.draw();
    });

    // تحديد الكل
    $('#select-all').on('click', function () {
        const rows = usersTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // كود تحريك النص
    $(document).on('mouseenter', '.table-ellipsis', handleTextAnimation);
    $(document).on('mouseleave', '.table-ellipsis', resetTextAnimation);

    // ربط أحداث المودالات
    bindModalEvents();
}

/**
 * ربط أحداث المودالات
 */
function bindModalEvents() {
    // التعامل مع النقر على رابط اسم الدور لفتح المودال
    $('#users-table').on('click', '.edit-role-link', function () {
        const userId = $(this).data('user-id');

        if (!userId) {
            toastr.error('معرف المستخدم غير موجود.');
            return;
        }

        openEditRoleModal(userId);
    });

    // إرسال تحديث الدور والحالة
    $('#updateRoleForm').submit(function (e) {
        e.preventDefault();
        updateUserRole();
    });

    // التعامل مع تغيير الحالة من الـ toggle switch أو القائمة
    $('#users-table').on('change', '.status-toggle', function () {
        toggleUserStatus(this, $(this).data('user-id'));
    });
    $('#users-table').on('change', '.status-dropdown', function () {
        const userId = $(this).data('user-id');
        const newStatus = $(this).val();
        updateUserStatus(userId, newStatus);
    });
}

/**
 * فتح مودال تعديل الدور
 */
function openEditRoleModal(userId) {
    $('#editRoleModal').modal('show');
    $('#editRoleModal').find('#user-id').val(userId);

    // جلب الأدوار والحالة المتاحة للمستخدم
    $.ajax({
        url: window.routes.getUserRoles.replace('___ID___', userId),
        method: 'GET',
        success: function (data) {
            if (data.error) {
                toastr.error(data.error);
                return;
            }

            populateRoleModal(data);
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            toastr.error('فشل في جلب الأدوار والحالة.');
        }
    });
}

/**
 * تعبئة مودال تعديل الدور
 */
function populateRoleModal(data) {
    const roleSelect = $('#editRoleModal').find('#user-role');
    const statusSelect = $('#editRoleModal').find('#user-status');
    const branchesWrapper = $('#editRoleModal').find('#branches-wrapper');

    // 1) تفريغ الحقول القديمة
    roleSelect.empty();
    statusSelect.empty();
    branchesWrapper.empty();

    // 2) تعبئة قائمة الأدوار
    roleSelect.append('<option value=""></option>');
    $.each(data.roles, function (index, role) {
        const isSelected = data.assigned_role === role.id ? 'selected' : '';
        roleSelect.append('<option value="' + role.id + '" ' + isSelected + '>' + role.name + '</option>');
    });

    // 3) تعبئة قائمة الحالات
    statusSelect.append('<option value=""></option>');
    statusSelect.append('<option value="active" ' + (data.assigned_status === 'active' ? 'selected' : '') + '>نشط</option>');
    statusSelect.append('<option value="inactive" ' + (data.assigned_status === 'inactive' ? 'selected' : '') + '>غير نشط</option>');

    // 4) تعبئة الفروع الإضافية
    if (data.branches && data.branches.length > 0) {
        // زر تحديد كل الفروع
        branchesWrapper.append(
            '<div class="form-check">' +
            '<input type="checkbox" class="form-check-input" id="branches-select-all">' +
            '<label class="form-check-label" for="branches-select-all">تحديد كل الفروع</label>' +
            '</div>' +
            '<hr class="mt-0">'
        );

        const additionalIds = (data.user_additional_branches || []).map(String);
        const primaryId = data.user_primary_branch_id ? String(data.user_primary_branch_id) : null;

        $.each(data.branches, function (index, branch) {
            const branchIdStr = String(branch.id);
            const isPrimary = primaryId !== null && primaryId === branchIdStr;
            const isAdditional = additionalIds.includes(branchIdStr);

            const checkedAttr = (isPrimary || isAdditional) ? 'checked' : '';
            const disabledAttr = isPrimary ? 'disabled' : '';
            const primaryBadge = isPrimary ? '<span class="text-muted ms-2">(الفرع الأساسي)</span>' : '';

            branchesWrapper.append(
                '<div class="form-check mb-2">' +
                '<input type="checkbox" ' +
                'class="form-check-input branch-checkbox" ' +
                'id="branch-' + branch.id + '" ' +
                'value="' + branch.id + '" ' +
                checkedAttr + ' ' + disabledAttr + '>' +
                '<label class="form-check-label d-flex align-items-center" for="branch-' + branch.id + '">' +
                '<span>' + branch.name + '</span>' +
                primaryBadge +
                '</label>' +
                '</div>'
            );

        });

        // حدث "تحديد كل الفروع"
        $('#branches-select-all').off('change').on('change', function () {
            const isChecked = $(this).is(':checked');
            branchesWrapper.find('.branch-checkbox:not(:disabled)').prop('checked', isChecked);
        });
    } else {
        branchesWrapper.append(
            '<p class="text-muted mb-0 text-center">لا توجد فروع نشطة</p>'
        );
    }

    // 5) تهيئة Select2 للمودال (الدور + الحالة)
    roleSelect.select2({
        dropdownParent: $('#editRoleModal'),
        placeholder: 'اختر الدور',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    statusSelect.select2({
        dropdownParent: $('#editRoleModal'),
        placeholder: 'اختر الحالة',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });
}

/**
 * تحديث دور المستخدم + الحالة + الفروع الإضافية
 */
function updateUserRole() {
    const userId = $('#user-id').val();
    const role = $('#user-role').val();
    const status = $('#user-status').val() || 'active';

    // جمع الفروع الإضافية من المودال
    const additionalBranches = [];
    $('#editRoleModal').find('.branch-checkbox:checked').each(function () {
        additionalBranches.push($(this).val());
    });

    $.ajax({
        url: window.routes.updateUserRoles.replace('___ID___', userId),
        method: 'POST',
        data: {
            role: role,
            status: status,
            additional_branches: additionalBranches, // 👈 مهم جدًا
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editRoleModal').modal('hide');
                usersTable.ajax.reload(null, false);
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            toastr.error('فشل في تحديث الدور والحالة.');
        }
    });
}


/**
 * تبديل حالة المستخدم عبر مفتاح التبديل (Switch)
 */
function toggleUserStatus(element, userId) {
    let el = null;
    let id = userId;
    if (typeof element === 'number' || typeof element === 'string') {
        id = element;
        el = document.getElementById('status_' + id);
    } else {
        el = element;
    }

    if (!id) {
        toastr.error('معرف المستخدم غير صحيح');
        return;
    }

    const newStatus = el && el.checked ? 'active' : 'inactive';

    if (el) {
        $(el).prop('disabled', true);
    }

    $.ajax({
        url: window.routes.updateStatus.replace('___ID___', id),
        method: 'PATCH',
        data: {
            status: newStatus,
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
                if (el) {
                    $(el).prop('disabled', false);
                }
            } else {
                toastr.error(response.message || 'فشل في تحديث الحالة.');
                if (el) {
                    $(el).prop('checked', !el.checked).prop('disabled', false);
                }
            }
        },
        error: function (xhr) {
            let errorMsg = 'فشل في تحديث الحالة.';
            if (xhr.responseJSON?.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            if (el) {
                $(el).prop('checked', !el.checked).prop('disabled', false);
            }
        }
    });
}

/**
 * تحديث حالة المستخدم
 */
function updateUserStatus(userId, newStatus) {
    $.ajax({
        url: window.routes.updateStatus.replace('___ID___', userId),
        method: 'PATCH',
        data: {
            status: newStatus,
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            toastr.error('فشل في تحديث الحالة.');
        }
    });
}

/**
 * تأكيد الحذف
 */
function confirmDelete(roleId) {
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
            document.getElementById('delete-form-' + roleId).submit();
        }
    });
}

/**
 * معالجة تحريك النص
 */
function handleTextAnimation() {
    const span = $(this).find('span');
    const textWidth = span.width();
    const cellWidth = $(this).width();

    if (textWidth > cellWidth) {
        span.css('transform', 'translateX(' + (textWidth - cellWidth) + 'px)');
    }
}

/**
 * إعادة تعيين تحريك النص
 */
function resetTextAnimation() {
    $(this).find('span').css('transform', 'translateX(0)');
}

// تصدير الدوال للاستخدام العام
window.confirmDelete = confirmDelete;
window.toggleUserStatus = toggleUserStatus;
window.updateUserStatus = updateUserStatus;
