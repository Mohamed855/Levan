'use strict';

$(document).ready(function () {
    initializeComponents();

    $('#client-table').on('init.dt', function () {
        bindTableEvents();
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
    const table = $('#client-table').DataTable();
    const tableWrapper = $('#client-table_wrapper');

    // الفلاتر
    $('#filter-status').on('change', function () {
        table.draw();
    });

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    // ==================== STATUS BADGE CLICK ====================
    tableWrapper.on('click', '.status-badge', function () {
        const $badge = $(this);
        const id = $badge.data('id');
        const name = $badge.data('name');

        $badge.css('opacity', '0.6').css('pointer-events', 'none');

        $.ajax({
            url: `/admin/content-management/client/${id}/toggle-status`,
            method: 'PATCH',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    const $currentBadge = tableWrapper.find(`.status-badge[data-id="${id}"]`);
                    $currentBadge.removeClass('bg-success bg-danger');
                    $currentBadge.addClass(response.status ? 'bg-success' : 'bg-danger');
                    $currentBadge.html(response.status ? 'نشط' : 'غير نشط');
                    toastr.success(response.message, name);
                } else {
                    toastr.error(response.message, 'خطأ');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'حدث خطأ أثناء التحديث';
                toastr.error(errorMsg, 'خطأ');
            },
            complete: function () {
                const $currentBadge = tableWrapper.find(`.status-badge[data-id="${id}"]`);
                $currentBadge.css('opacity', '1').css('pointer-events', 'auto');
            }
        });
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد عميل واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Client');
}

window.handleMassDelete = handleMassDelete;