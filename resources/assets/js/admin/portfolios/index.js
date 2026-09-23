'use strict';

$(document).ready(function () {
    initializeComponents();

    $('#portfolio-table').on('init.dt', function () {
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
    const table = $('#portfolio-table').DataTable();

    // الفلاتر
    $('#filter-status').on('change', function () {
        table.draw();
    });

    const tableWrapper = $('#portfolio-table_wrapper');

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
        const portfolioId = $badge.data('id');
        const portfolioName = $badge.data('name');

        $badge.css('opacity', '0.6').css('cursor', 'not-allowed');
        const originalBadgeClass = $badge.attr('class');
        const originalBadgeHTML = $badge.html();

        $.ajax({
            url: `/admin/content-management/portfolio/${portfolioId}/toggle-status`,
            method: 'PATCH',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $badge.removeClass('bg-success bg-danger');
                    $badge.addClass(response.status ? 'bg-success' : 'bg-danger');
                    $badge.html(response.status ? 'نشط' : 'غير نشط');

                    toastr.success(response.message, portfolioName);
                } else {
                    $badge.attr('class', originalBadgeClass);
                    $badge.html(originalBadgeHTML);
                    toastr.error(response.message, 'خطأ');
                }
            },
            error: function (xhr) {
                $badge.attr('class', originalBadgeClass);
                $badge.html(originalBadgeHTML);
                const errorMsg = xhr.responseJSON?.message || 'حدث خطأ أثناء التحديث';
                toastr.error(errorMsg, 'خطأ');
            },
            complete: function () {
                $badge.css('opacity', '1').css('cursor', 'pointer');
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
        toastr.warning('يرجى تحديد محفظة واحدة على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Portfolio');
}

window.handleMassDelete = handleMassDelete;

