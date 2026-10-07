'use strict';

function removeTableError(table) {
    table.closest('.dataTables_wrapper')?.querySelector('.datatable-ajax-error')?.remove();
}

function showTableError(table) {
    const wrapper = table.closest('.dataTables_wrapper');

    if (!wrapper || wrapper.querySelector('.datatable-ajax-error')) {
        return;
    }

    const alert = document.createElement('div');
    alert.className = 'alert alert-danger datatable-ajax-error mt-3 mb-0';
    alert.setAttribute('role', 'alert');
    alert.textContent = 'تعذر تحميل بيانات الجدول. يرجى المحاولة مرة أخرى.';
    wrapper.append(alert);
}

function initializeDataTableFeedback() {
    if (!window.jQuery || !window.jQuery.fn.dataTable) {
        return;
    }

    const $ = window.jQuery;

    $(document)
        .off('processing.dt.sharedDataTableFeedback')
        .on('processing.dt.sharedDataTableFeedback', 'table', function (_, __, processing) {
            this.setAttribute('aria-busy', processing ? 'true' : 'false');
        })
        .off('xhr.dt.sharedDataTableFeedback')
        .on('xhr.dt.sharedDataTableFeedback', 'table', function () {
            removeTableError(this);
        })
        .off('error.dt.sharedDataTableFeedback')
        .on('error.dt.sharedDataTableFeedback', 'table', function () {
            showTableError(this);
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDataTableFeedback, { once: true });
} else {
    initializeDataTableFeedback();
}
