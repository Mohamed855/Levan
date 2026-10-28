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
    $('.select2').not('.select2-hidden-accessible').each(function () {
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
                d.usage_status = $('#filter-usage-status').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: getTableColumns(),
        order: [[6, 'desc']],
        dom: getTableDom(),
        buttons: getTableButtons(),
        language: {
            url: window.assets.arabicDataTable
        },
        orderCellsTop: true,
        responsive: false,
        createdRow: handleTableRowCreation
    });
}

/**
 * تعريف أعمدة الجدول
 */
function getTableColumns() {
    return [
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
        'r<"table-responsive"t>' +
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
    const buttons = [
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
                },
                {
                    text: 'حذف المحدد',
                    className: 'btn btn-default btn-delete-selected',
                    action: function () {
                        handleMassDelete();
                    }
                }
            ]
        }
    ];

    if (window.permissions?.canAddRole && window.routes?.rolesCreate) {
        buttons.push({
            text: '<i class="ti ti-plus me-1"></i> إضافة دور',
            className: 'btn btn-primary btn-add',
            action: function () {
                window.location.href = window.routes.rolesCreate;
            }
        });
    }

    return buttons;
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
    const tableWrapper = $('#users-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        const rows = usersTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    $('#users-table').on('draw.dt', function () {
        $('#select-all').prop('checked', false);
    });

    // تصفية جدول المستخدمين عند تغيير الفلاتر
    $('#filter-status, #filter-role').on('change', function () {
        usersTable.ajax.reload();
    });

    // إعادة تعيين الفلاتر
    $('#btn-reset-filters').on('click', function () {
        $('#filter-status, #filter-role').val(null).trigger('change.select2');
        usersTable.ajax.reload();
    });

    // تصفية سريعة لجدول الأدوار العلوي
    $('#filter-roles-table').on('keyup', function () {
        const val = $(this).val().toLowerCase();
        $('#roles-table tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    // ربط أحداث المودالات
    bindModalEvents();
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked', usersTable.rows().nodes()).each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد مستخدم واحد على الأقل');
        }
        return;
    }

    if (typeof confirmDeleteSelectedmss === 'function') {
        confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'User');
    }
}

window.handleMassDelete = handleMassDelete;

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
        roleSelect.append(new Option(role.label, role.id, false, String(data.assigned_role) === String(role.id)));
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


