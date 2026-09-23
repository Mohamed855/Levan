'use strict';

/*
|--------------------------------------------------------------------------
| Collection Sessions Table Filters + Export
|--------------------------------------------------------------------------
| الجدول نفسه (init/columns/ajax) يُولَّد تلقائيًا عبر $dataTable->scripts()
| من CollectionSessionsDataTable — هنا فقط نربط الفلاتر وزر التصدير.
*/

$(document).ready(function () {
    if (!$('#collection-sessions-table').length) return;

    $('#sessionDateFilter').val(new Date().toISOString().split('T')[0]);

    bindDataTableFilters();
    bindExportButton();
});

function bindDataTableFilters() {
    if ($.fn.DataTable.isDataTable('#collection-sessions-table')) {
        setupSessionsTableFilters($('#collection-sessions-table').DataTable());
        return;
    }

    $('#collection-sessions-table').one('init.dt', function () {
        setupSessionsTableFilters($(this).DataTable());
    });
}

function setupSessionsTableFilters(table) {
    table.on('preXhr.dt', function (event, settings, data) {
        data.date = $('#sessionDateFilter').val() || new Date().toISOString().split('T')[0];
        data.status = $('#sessionStatusFilter').val() || '';
    });

    $('#sessionDateFilter, #sessionStatusFilter').on('change', function () {
        table.draw();
    });
}

function bindExportButton() {
    $('#exportSessionsExcel').on('click', function () {
        const date = $('#sessionDateFilter').val() || new Date().toISOString().split('T')[0];
        const status = $('#sessionStatusFilter').val();

        if (!window.routes || !window.routes.exportSessions) {
            alert('تعذّر التصدير.');
            return;
        }

        const params = new URLSearchParams({ date });
        if (status) params.set('status', status);

        window.location.href = `${window.routes.exportSessions}?${params.toString()}`;
    });
}
