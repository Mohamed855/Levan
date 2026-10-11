'use strict';

function ensureResponsiveDataTable(tableElement) {
  if (!(tableElement instanceof HTMLTableElement)) {
    return;
  }

  // If the table is already directly wrapped in table-responsive, do nothing
  if (tableElement.parentElement && tableElement.parentElement.classList.contains('table-responsive')) {
    return;
  }

  const parent = tableElement.parentNode;
  if (!parent) return;

  const responsiveContainer = document.createElement('div');
  responsiveContainer.className = 'table-responsive';
  parent.insertBefore(responsiveContainer, tableElement);
  responsiveContainer.appendChild(tableElement);
}

function initializeResponsiveDataTables() {
  document.querySelectorAll('table.dataTable').forEach(ensureResponsiveDataTable);

  if (!window.jQuery) return;

  window.jQuery(document)
    .off('init.dt.globalDataTableResponsive')
    .on('init.dt.globalDataTableResponsive', function (event, settings) {
      ensureResponsiveDataTable(settings?.nTable);
    });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeResponsiveDataTables);
} else {
  initializeResponsiveDataTables();
}

window.addEventListener('load', () => {
  document.querySelectorAll('table.dataTable').forEach(ensureResponsiveDataTable);
});

export { ensureResponsiveDataTable, initializeResponsiveDataTables };
