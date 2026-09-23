// ================================
// ملف JavaScript منفصل لأنواع المديونية
// resources/js/general-settings/debt-types/debt-types.js
// ================================

$(document).ready(function () {
    $('.select2').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    var debtTypesTable = $('#setting-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.debtTypesRoutes.index,
        },
        columns: [{
            data: 'checkbox',
            name: 'checkbox',
            orderable: false,
            searchable: false,
            className: 'custom-checkbox',
            render: function (data, type, full, meta) {
                return '<input type="checkbox" class="row-checkbox" value="' + full.id + '">';
            }
        }, {
            data: 'name',
            name: 'name'
        }, {
            data: 'status',
            name: 'status'
        }, {
            data: 'created_at',
            name: 'created_at'
        }, {
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false
        }],
        order: [[3, 'desc']],
        dom: '<"dt-toolbar mb-3"' +
            '<"dt-toolbar-left"' +
            'l' +
            '>' +
            '<"dt-toolbar-right"' +
            'f' +
            'B' +
            '>' +
            '>' +
            'rt' +
            '<"row"' +
            '<"col-12 d-flex align-items-center justify-content-between"' +
            'i' +
            'p' +
            '>' +
            '>',
        buttons: [{
            extend: 'collection',
            className: 'btn btn-export btn',
            text: 'الإجراءات',
            buttons: [{
                extend: 'copy',
                text: 'نسخ'
            }, {
                extend: 'excel',
                text: 'إكسل'
            }, {
                text: 'حذف المحدد',
                className: 'btn btn-default btn-delete-selected',
                action: function (e, dt, node, config) {
                    var selectedIds = [];
                    $('.row-checkbox:checked').each(function () {
                        selectedIds.push($(this).val());
                    });
                    confirmDeleteSelectedmss(selectedIds, window.debtTypesRoutes.massDelete, 'SettingsDebtType');
                }
            }]
        },
        {
            text: '<i class="fas fa-plus-circle me-1"></i> إضافة نوع مديونية جديد',
            className: 'btn btn-primary btn-add',
            action: function (e, dt, node, config) {
                $('#addSectionModal').modal('show');
            }
        }
        ],
        language: {
            url: window.assetsPath + '/json/ar.json'
        },
        responsive: true,
    });

    $('#select-all').on('click', function () {
        var rows = debtTypesTable.rows({
            'search': 'applied'
        }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var editForm = $('#editSettingForm');

        var actionUrl = window.debtTypesRoutes.update.replace(':id', id);

        editForm.attr('action', actionUrl);
        $('#modalSettingNameEdit').val(name);
        $('#editSectionModal').modal('show');
    });

    $(document).on('click', '.status-toggle', function () {
        var badge = $(this);
        var id = badge.data('id');

        $.ajax({
            url: window.debtTypesRoutes.edit.replace(':id', id),
            method: 'get',
            data: {
                _token: window.csrfToken
            },
            success: function (response) {
                if (response.success) {
                    if (response.status === 'active') {
                        badge.removeClass('bg-danger').addClass('bg-success').text('نشط');
                    } else if (response.status === 'inactive') {
                        badge.removeClass('bg-success').addClass('bg-danger').text('غير نشط');
                    }
                    toastr.success('تم تغيير الحالة بنجاح.');
                } else {
                    toastr.error('فشل في تغيير الحالة.');
                }
            },
            error: function (xhr) {
                toastr.error('حدث خطأ أثناء تغيير الحالة.');
            }
        });
    });
});