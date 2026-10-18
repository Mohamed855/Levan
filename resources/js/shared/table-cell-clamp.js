'use strict';

/**
 * Checks if a table cell should be excluded from 2-line clamping.
 * Excludes:
 * - Checkbox / radio cells (.custom-checkbox, .dt-checkboxes-cell, inputs)
 * - Switch / status toggles (.form-switch, .js-table-status-toggle)
 * - Action buttons / dropdowns (.dt-actions, .actions, .btn-group, .dropdown-menu, .btn, buttons)
 * - Avatars / images (.avatar, .avatar-group, img.rounded-circle)
 * - Already clamped cells
 * - Empty or single placeholder cells ('-')
 */
function shouldSkipCell(td) {
  if (!td || td.nodeType !== Node.ELEMENT_NODE) return true;

  // Checkbox cells
  if (
    td.classList.contains('custom-checkbox') ||
    td.classList.contains('dt-checkboxes-cell') ||
    td.querySelector('input[type="checkbox"], input[type="radio"]')
  ) {
    return true;
  }

  // Switch status toggles
  if (
    td.classList.contains('form-switch') ||
    td.querySelector('.form-switch, .js-table-status-toggle')
  ) {
    return true;
  }

  // Action columns and button groups / dropdowns
  if (
    td.classList.contains('dt-actions') ||
    td.classList.contains('actions') ||
    td.querySelector('button, .btn, .btn-group, .dropdown, .dropdown-menu, .dropdown-toggle, .btn-icon, .btn-add, .btn-export')
  ) {
    return true;
  }

  // Avatars / avatar-groups / circular icons
  if (
    td.classList.contains('avatar-group') ||
    td.classList.contains('avatar') ||
    td.querySelector('.avatar-group, .avatar, img.rounded-circle')
  ) {
    return true;
  }

  const copyable = td.querySelector('.copyable-code-wrapper, .copyable-code') || (td.classList.contains('copyable-code-wrapper') || td.classList.contains('copyable-code') ? td : null);
  if (copyable) {
    const fullCode = copyable.textContent.trim();
    if (fullCode && !copyable.getAttribute('title')) {
      copyable.setAttribute('title', fullCode);
    }
    return true;
  }

  // Already clamped
  if (
    td.classList.contains('table-cell-clamp') ||
    td.querySelector('.table-cell-clamp')
  ) {
    return true;
  }

  // Empty or placeholder cells
  const text = td.textContent.trim();
  if (!text || text === '-') {
    return true;
  }

  return false;
}

/**
 * Wraps cell contents into a .table-cell-clamp container
 */
function clampTableCell(td) {
  if (shouldSkipCell(td)) {
    return;
  }

  const textContent = td.textContent.trim();
  const wrapper = document.createElement('div');
  wrapper.className = 'table-cell-clamp';
  wrapper.style.maxWidth = '200px';
  td.style.maxWidth = '200px';

  // Provide hover tooltip with full text if not already defined
  if (!td.getAttribute('title') && !td.querySelector('[title]')) {
    wrapper.setAttribute('title', textContent);
  }

  // Move all child nodes into wrapper
  while (td.firstChild) {
    wrapper.appendChild(td.firstChild);
  }

  td.appendChild(wrapper);
}

/**
 * Clamps all eligible cells in a specific table
 */
function clampTableCells(tableElement) {
  if (!tableElement || !(tableElement instanceof HTMLElement)) {
    return;
  }

  const cells = tableElement.querySelectorAll('tbody > tr > td');
  for (let i = 0; i < cells.length; i++) {
    clampTableCell(cells[i]);
  }

  // Handle responsive datatables child rows (.dtr-data)
  const dtrDataCells = tableElement.querySelectorAll('tbody > tr.child .dtr-data');
  for (let i = 0; i < dtrDataCells.length; i++) {
    clampTableCell(dtrDataCells[i]);
  }
}

/**
 * Clamps all tables currently in the DOM
 */
function clampAllTables() {
  const tables = document.querySelectorAll('table.dataTable, table.table');
  for (let i = 0; i < tables.length; i++) {
    clampTableCells(tables[i]);
  }
}

// Initialization and Event Listeners
function initTableCellClamp() {
  clampAllTables();

  // DataTables draw event (paging, searching, sorting, ajax draw)
  if (window.jQuery) {
    window.jQuery(document)
      .off('draw.dt.tableCellClamp')
      .on('draw.dt.tableCellClamp', function (event, settings) {
        if (settings?.nTable) {
          clampTableCells(settings.nTable);
        }
      });

    // AJAX completion (modals, dynamic tab loads)
    window.jQuery(document).ajaxComplete(function () {
      clampAllTables();
    });
  }

  // Observe DOM for newly inserted tables or table rows
  if (typeof MutationObserver !== 'undefined' && document.body) {
    let timeoutId = null;
    const observer = new MutationObserver(function (mutations) {
      let needsRun = false;
      for (let i = 0; i < mutations.length; i++) {
        const added = mutations[i].addedNodes;
        for (let j = 0; j < added.length; j++) {
          const node = added[j];
          if (node.nodeType === Node.ELEMENT_NODE) {
            const tag = node.tagName;
            if (
              tag === 'TABLE' ||
              tag === 'TBODY' ||
              tag === 'TR' ||
              node.querySelector?.('table')
            ) {
              needsRun = true;
              break;
            }
          }
        }
        if (needsRun) break;
      }

      if (needsRun) {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(clampAllTables, 30);
      }
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTableCellClamp);
} else {
  initTableCellClamp();
}

window.addEventListener('load', clampAllTables);

export { clampTableCell, clampTableCells, clampAllTables, initTableCellClamp };
