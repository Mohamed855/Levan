'use strict';

function initializeDataTableFilterSelects() {
  if (!window.jQuery || !document.querySelector('.dataTable, table[id$="-table"]')) return;

  const $ = window.jQuery;

  $('[id^="filter-"].select2')
    .not('.select2-ajax, .select2-ajax-filter, .select2-hidden-accessible')
    .each(function () {
      const $select = $(this);

      $select.select2({
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        placeholder: $select.data('placeholder') || 'الكل',
      });
    });
}

window.addEventListener('load', initializeDataTableFilterSelects);
