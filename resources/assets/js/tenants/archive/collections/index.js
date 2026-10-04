/**
 * Archive Collections Index - JavaScript Functions
 * ================================================
 * إدارة جدول التحصيلات المؤرشفة والتفاعلات
 */

'use strict';

// متغيرات عامة
let archivedCollectionsTable;

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
            dropdownParent: $(this).closest('.card')
        });
    });

    // تهيئة Flatpickr للتواريخ
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true,
        maxDate: 'today',
        allowClear: true
    });

    // تهيئة التحقق من النماذج
    initializeFormValidation();
}

/**
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
    archivedCollectionsTable = $('#archived-collections-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.archiveCollectionsIndex,
            data: function (d) {
                d.status = $('#filter-status').val();
                d.customer_id = $('#filter-customer').val();
                d.payment_method_id = $('#filter-payment-method').val();
                d.collection_type = $('#filter-collection-type').val();
                d.payment_type = $('#filter-payment-type').val();
                d.added_by = $('#filter-added-by').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: getTableColumns(),
        order: [[9, 'desc']],
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
            data: 'customer_info',
            name: 'customer_info',
            className: 'table-ellipsis'
        },
        {
            data: 'amount_info',
            name: 'amount_info',
            className: 'table-ellipsis'
        },
        {
            data: 'collection_details',
            name: 'collection_details',
            orderable: false,
            searchable: false
        },
        {
            data: 'payment_method',
            name: 'payment_method',
            className: 'table-ellipsis'
        },
        {
            data: 'collection_date_formatted',
            name: 'collection_date'
        },
        {
            data: 'status',
            name: 'status'
        },
        {
            data: 'added_by',
            name: 'added_by'
        },
        {
            data: 'deleted_at_formatted',
            name: 'deleted_at'
        },
        {
            data: 'attachment',
            name: 'attachment',
            orderable: false,
            searchable: false
        },
        {
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            className: 'text-center'
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
                    text: 'إكسل',
                    title: 'التحصيلات المؤرشفة'
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
                    return "تفاصيل التحصيل";
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
        const rows = archivedCollectionsTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // كود تحريك النص
    $(document).on('mouseenter', '.table-ellipsis', handleTextAnimation);
    $(document).on('mouseleave', '.table-ellipsis', resetTextAnimation);
}

/**
 * معالجة الاستعادة الجماعية
 */
function handleMassRestore() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد تحصيل واحد على الأقل');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم استرجاع التحصيلات المحددة من الأرشيف',
        icon: 'question',
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
        confirmButtonText: 'نعم، استرجع',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            // إرسال طلب AJAX للاستعادة الجماعية
            $.ajax({
                url: window.routes.archiveCollectionsIndex,
                type: 'POST',
                data: {
                    _token: window.csrfToken,
                    ids: selectedIds,
                    mass_restore: true
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        archivedCollectionsTable.draw();
                        $('#select-all').prop('checked', false);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function () {
                    toastr.error('حدث خطأ أثناء الاستعادة الجماعية');
                }
            });
        }
    });
}

/**
 * معالجة الحذف النهائي الجماعي
 */
function handleMassForceDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد تحصيل واحد على الأقل');
        return;
    }

    Swal.fire({
        title: 'تحذير!',
        text: 'سيتم حذف التحصيلات المحددة نهائياً ولا يمكن استرجاعها',
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
        confirmButtonText: 'نعم، احذف نهائياً',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            // إرسال طلب AJAX للحذف النهائي الجماعي
            $.ajax({
                url: window.routes.archiveCollectionsIndex,
                type: 'DELETE',
                data: {
                    _token: window.csrfToken,
                    ids: selectedIds,
                    mass_force_delete: true
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                        archivedCollectionsTable.draw();
                        $('#select-all').prop('checked', false);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function () {
                    toastr.error('حدث خطأ أثناء الحذف النهائي الجماعي');
                }
            });
        }
    });
}

/**
 * استرجاع التحصيل
 */
function confirmRestore(collectionId) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم استرجاع هذا التحصيل من الأرشيف',
        icon: 'question',
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
        confirmButtonText: 'نعم، استرجع',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('restore-form-' + collectionId).submit();
        }
    });
}

/**
 * حذف نهائي للتحصيل
 */
function confirmForceDelete(collectionId) {
    Swal.fire({
        title: 'تحذير!',
        text: 'سيتم حذف هذا التحصيل نهائياً ولا يمكن استرجاعه',
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
        confirmButtonText: 'نعم، احذف نهائياً',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('force-delete-form-' + collectionId).submit();
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

/**
 * تهيئة التحقق من النماذج
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// تصدير الدوال للاستخدام العام
window.confirmRestore = confirmRestore;
window.confirmForceDelete = confirmForceDelete;
