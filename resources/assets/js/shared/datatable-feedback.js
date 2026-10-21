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

function patchDataTablesPager() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
        return;
    }
    const DataTable = window.jQuery.fn.dataTable;
    if (!DataTable.ext || !DataTable.ext.pager) {
        return;
    }

    Object.keys(DataTable.ext.pager).forEach((key) => {
        const origPager = DataTable.ext.pager[key];
        if (typeof origPager === 'function' && !origPager.__patched) {
            const patched = function (page, pages) {
                if (pages <= 0) {
                    return origPager(0, 1);
                }
                return origPager(page, pages);
            };
            patched.__patched = true;
            DataTable.ext.pager[key] = patched;
        }
    });
}

function enforceSingleOrEmptyPagePagination(settings) {
    if (!settings || !settings.nTableWrapper) {
        return;
    }

    const $ = window.jQuery;
    if (!$) return;

    let api;
    try {
        api = new $.fn.dataTable.Api(settings);
    } catch (_) {
        return;
    }

    const pageInfo = api.page.info();
    const $wrapper = $(settings.nTableWrapper);
    const $pagination = $wrapper.find('.dataTables_paginate ul.pagination');

    if (!$pagination.length) {
        return;
    }

    if (pageInfo.pages <= 1) {
        let $pageButtons = $pagination.find('.paginate_button.page-item').not('.previous, .next, .first, .last');

        if (!$pageButtons.length) {
            const $next = $pagination.find('.next');
            const page1Html =
                '<li class="paginate_button page-item active disabled">' +
                '<a class="page-link" aria-disabled="true" aria-current="page" tabindex="-1">1</a>' +
                '</li>';

            if ($next.length) {
                $(page1Html).insertBefore($next);
            } else {
                $pagination.append(page1Html);
            }
        } else {
            $pageButtons
                .addClass('disabled')
                .find('.page-link')
                .attr('aria-disabled', 'true')
                .attr('tabindex', '-1');
        }
    }
}

function initializeDataTableFeedback() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
        return;
    }

    const $ = window.jQuery;

    patchDataTablesPager();

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
        })
        .off('init.dt.sharedDataTablePagination')
        .on('init.dt.sharedDataTablePagination', function (_, settings) {
            patchDataTablesPager();
            enforceSingleOrEmptyPagePagination(settings);
        })
        .off('draw.dt.sharedDataTablePagination')
        .on('draw.dt.sharedDataTablePagination', 'table', function (_, settings) {
            enforceSingleOrEmptyPagePagination(settings);
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDataTableFeedback, { once: true });
} else {
    initializeDataTableFeedback();
}
