'use strict';

$(document).ready(function () {

    if ($('.select2').length) {
        $('.select2').each(function () {
            $(this).select2({
                dropdownAutoWidth: true,
                width: '180px',
                minimumResultsForSearch: 5,
                language: {
                    noResults: function () {
                        return 'لا توجد نتائج';
                    }
                }
            });
        });
    }

    if ($('#upcoming-followups-table').length) {
        initializeUpcomingFollowupsTable();
    }

    $('#filterCompany, #filterPortfolio, #filterManager').on('change', function () {
        if ($.fn.DataTable.isDataTable('#upcoming-followups-table')) {
            $('#upcoming-followups-table').DataTable().ajax.reload();
        }
    });
});

function initializeUpcomingFollowupsTable() {

    if ($.fn.DataTable.isDataTable('#upcoming-followups-table')) {
        $('#upcoming-followups-table').DataTable().destroy();
    }

    $('#upcoming-followups-table').DataTable({
        processing: true,
        serverSide: true,

        ajax: {
            url: window.routes.upcomingFollowUps,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },

            data: function (d) {
                d.company_id = $('#filterCompany').val() || '';
                d.portfolio_id = $('#filterPortfolio').val() || '';
                d.relationship_manager_id = $('#filterManager').val() || '';
            },

            error: function () {
                $('#upcoming-followups-table tbody').html(
                    '<tr><td colspan="9" class="text-center text-danger py-4">' +
                    '<div class="mb-2">حدث خطأ في تحميل البيانات</div>' +
                    '<small class="text-muted">يرجى المحاولة مرة أخرى</small>' +
                    '</td></tr>'
                );
            }
        },

        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '6%' },
            { data: 'customer_name', name: 'customer_name', orderable: true, searchable: true },
            { data: 'account_number', name: 'account_number', orderable: true, searchable: true, className: 'text-center' },
            { data: 'identity_number', name: 'identity_number', orderable: true, searchable: true, className: 'text-center' },
            { data: 'company_name', name: 'company_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'portfolio_name', name: 'portfolio_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'follow_result', name: 'follow_result', orderable: true, searchable: true, className: 'text-center' },
            { data: 'remaining_debt', name: 'remaining_debt', orderable: true, searchable: false, className: 'text-center' },
            { data: 'relationship_manager', name: 'relationship_manager', orderable: true, searchable: true, className: 'text-center' }
        ],

        order: [[8, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        scrollX: false,

        language: {
            url: window.assets?.arabicDataTable || '/assets/json/ar.json',
            processing: 'جاري التحميل...',
            emptyTable: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام يومية</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>',
            zeroRecords: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام يومية</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>'
        }
    });
}
