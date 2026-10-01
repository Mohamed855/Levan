'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(function () {
    initializeTableStatusToggles();

    document.addEventListener('table-status-toggled', () => {
        const table = $('#subscriptions-table').DataTable();

        if (table) {
            table.ajax.reload(null, false);
        }
    });
});
