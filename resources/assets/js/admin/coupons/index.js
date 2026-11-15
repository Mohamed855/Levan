
'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
});

function handleMassDelete() {
    if (typeof window.executeMassDelete === 'function') {
        window.executeMassDelete('Coupon', window.routes?.massDelete, 'يرجى تحديد كوبون واحد على الأقل');
        return;
    }

    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد كوبون واحد على الأقل');
        }
        return;
    }

    if (typeof confirmDeleteSelectedmss === 'function') {
        confirmDeleteSelectedmss(selectedIds, window.routes?.massDelete, 'Coupon');
    }
}

window.handleMassDelete = handleMassDelete;

