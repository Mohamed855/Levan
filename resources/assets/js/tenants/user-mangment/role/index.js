/**
 * Roles Index - JavaScript Functions
 * ===================================
 * إدارة جدول المستخدمين والأدوار والتفاعلات
 */

'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

// متغيرات عامة
let usersTable;

$(document).ready(function () {
    initializeTableStatusToggles();
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
            dir: 'rtl'
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
        order: [[7, 'desc']],
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
            data: 'created_at_formatted',
            name: 'created_at'
        },
        {
            data: 'status',
            name: 'status'
        },
        {
            data: 'actions',
            name: 'actions',
            orderable: false,
            searchable: false
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
    // تحديد الكل
    $('#select-all').on('click', function () {
        const rows = usersTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

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

    // 1) تفريغ الحقول القديمة
    roleSelect.empty();
    statusSelect.empty();

    // 2) تعبئة قائمة الأدوار
    roleSelect.append('<option value=""></option>');
    $.each(data.roles, function (index, role) {
        const isSelected = data.assigned_role === role.id ? 'selected' : '';
        roleSelect.append('<option value="' + role.id + '" ' + isSelected + '>' + role.name + '</option>');
    });

    // 3) تعبئة قائمة الحالات
    statusSelect.append('<option value=""></option>');
    $.each(data.status_options, function (value, label) {
        const option = new Option(label, value, false, data.assigned_status === value);
        statusSelect.append(option);
    });

    // 4) تهيئة Select2 للمودال (الدور + الحالة)
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
 * تحديث دور المستخدم وحالته
 */
function updateUserRole() {
    const userId = $('#user-id').val();
    const role = $('#user-role').val();
    const status = $('#user-status').val() || 'active';

    $.ajax({
        url: window.routes.updateUserRoles.replace('___ID___', userId),
        method: 'POST',
        data: {
            role: role,
            status: status,
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

// تصدير الدوال للاستخدام العام
window.confirmDelete = confirmDelete;
