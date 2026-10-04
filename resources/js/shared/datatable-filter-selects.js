'use strict';

/**
 * Unified DataTable Filters Controller
 * Handles declarative filtering, preXhr parameter passing, debounced reloads,
 * and unified reset across all DataTables.
 */

const filterSelector = [
  '[data-datatable-filter]',
  '.datatable-filters-card select',
  '.datatable-filters-card input',
  '.datatable-filters-card textarea',
  'select[id^="filter-"]',
  'input[id^="filter-"]',
  'textarea[id^="filter-"]',
  'select[id^="filter_"]',
  'input[id^="filter_"]',
  'textarea[id^="filter_"]',
  'select[id$="Filter"]',
  'input[id$="Filter"]',
  'textarea[id$="Filter"]'
].join(', ');

const resetSelector = [
  '[data-datatable-filter-reset]',
  '.btn-reset-datatable-filters',
  '#btn-reset-filters',
  'button[id^="btn-reset-filter"]',
  'a[id^="btn-reset-filter"]'
].join(', ');

const scheduledReloads = new WeakMap();
let isResettingFilters = false;

/**
 * Initialize Select2 on filter dropdowns.
 */
function initializeDataTableFilterSelects() {
  if (!window.jQuery) return;
  const $ = window.jQuery;

  $(filterSelector)
    .filter('select.select2, select.select2-basic')
    .not('.select2-ajax, .select2-ajax-filter, .select2-hidden-accessible')
    .each(function () {
      const $select = $(this);
      const $modal = $select.closest('.modal');

      $select.select2({
        allowClear: $select.data('allow-clear') !== false && $select.data('allow-clear') !== 'false',
        dir: 'rtl',
        width: '100%',
        placeholder: $select.data('placeholder') || 'الكل',
        dropdownParent: $modal.length ? $modal : undefined
      });
    });
}

/**
 * Determine the backend request parameter key for a given filter element.
 */
function getFilterKey(element) {
  if (element.dataset.filterKey) {
    return element.dataset.filterKey;
  }

  const rawKey = element.name || element.id || '';
  const cleaned = rawKey
    .replace(/^filter[-_]?/i, '')
    .replace(/Filter$/, '')
    .replace(/\[\]$/, '');

  return cleaned
    .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
    .replace(/-/g, '_')
    .toLowerCase();
}

/**
 * Check if a filter element is associated with a specific table element.
 */
function isFilterForTable(element, tableElement) {
  const $ = window.jQuery;
  if (!tableElement) return false;

  const tableId = tableElement.id ? '#' + tableElement.id : '';
  const $filter = $(element);
  const explicitTarget = element.dataset.datatableTarget || $filter.closest('[data-datatable-target]').data('datatable-target');

  if (explicitTarget) {
    if (tableId && (explicitTarget === tableId || explicitTarget === tableElement.id)) {
      return true;
    }
    const matchingTables = $(explicitTarget).get();
    return matchingTables.includes(tableElement);
  }

  // If no explicit target is declared:
  // If there is only one DataTable on the page, associate all general filters with it.
  const allTables = $.fn.dataTable.tables();
  if (allTables.length <= 1) {
    return true;
  }

  // Multiple tables: check if in same card/section
  const $card = $filter.closest('.card, .tab-pane, section');
  if ($card.length && $card.find(tableElement).length > 0) {
    return true;
  }

  return false;
}

/**
 * Extract active filter values for a specific table.
 */
function getActiveFiltersForTable(tableElement) {
  const $ = window.jQuery;
  const filters = {};

  $(filterSelector).each(function () {
    const el = this;
    if (el.disabled || el.dataset.datatableFilter === 'false') return;
    if (!isFilterForTable(el, tableElement)) return;

    const $el = $(el);
    const key = getFilterKey(el);
    if (!key) return;

    let value = null;

    if (el.type === 'checkbox') {
      if (el.checked) {
        value = el.value || '1';
      }
    } else if (el.type === 'radio') {
      if (el.checked) {
        value = el.value;
      }
    } else {
      value = $el.val();
      if (typeof value === 'string') {
        value = value.trim();
      }
    }

    const isEmpty = Array.isArray(value) ? value.length === 0 : (value === null || value === undefined || value === '');

    if (!isEmpty) {
      filters[key] = value;
    }
  });

  return filters;
}

/**
 * Find all target server-side DataTables for a filter or reset element.
 */
function getTargetTables(element) {
  if (!window.jQuery || !window.jQuery.fn.dataTable) return [];
  const $ = window.jQuery;

  const $el = $(element);
  const explicitTarget = element.dataset.datatableTarget || $el.closest('[data-datatable-target]').data('datatable-target');

  let tableElements = [];
  if (explicitTarget) {
    tableElements = Array.from(document.querySelectorAll(explicitTarget));
  } else {
    tableElements = Array.from($.fn.dataTable.tables());
  }

  return tableElements.filter((tableElement) => {
    if (!$.fn.DataTable.isDataTable(tableElement)) return false;
    const settings = $(tableElement).DataTable().settings()[0];
    return Boolean(settings && settings.oFeatures.bServerSide);
  });
}

/**
 * Reload a DataTable while retaining pagination.
 */
function reloadTable(tableElement, debounceMs = 300) {
  const $ = window.jQuery;
  if (!$.fn.DataTable.isDataTable(tableElement)) return;

  const previousReload = scheduledReloads.get(tableElement);
  if (previousReload) {
    window.clearTimeout(previousReload.timer);
  }

  const table = $(tableElement).DataTable();
  const settings = table.settings()[0];
  const drawCount = settings?.iDraw;

  const executeReload = () => {
    scheduledReloads.delete(tableElement);
    if (!$.fn.DataTable.isDataTable(tableElement)) return;

    const currentTable = $(tableElement).DataTable();
    const currentSettings = currentTable.settings()[0];

    if (!currentTable.ajax || typeof currentTable.ajax.reload !== 'function') return;
    if (currentSettings && currentSettings.iDraw !== drawCount) return;

    // Retain current pagination: reload(null, false)
    currentTable.ajax.reload(null, false);
  };

  if (debounceMs <= 0) {
    executeReload();
  } else {
    const timer = window.setTimeout(executeReload, debounceMs);
    scheduledReloads.set(tableElement, { timer, drawCount });
  }
}

/**
 * Reset all filter controls for given target tables or filter group.
 */
function handleFilterReset(resetButton) {
  if (!window.jQuery) return;
  const $ = window.jQuery;

  isResettingFilters = true;
  const $btn = $(resetButton);
  const targetTables = getTargetTables(resetButton);
  const $group = $btn.closest('[data-datatable-filter-group], .datatable-filters-card');

  const filterElements = $group.length
    ? $group.find(filterSelector)
    : $(filterSelector).filter(function () {
        return targetTables.some(t => isFilterForTable(this, t));
      });

  filterElements.each(function () {
    const $input = $(this);
    if (this.disabled) return;

    if ($input.hasClass('select2-hidden-accessible')) {
      $input.val(null).trigger('change.select2');
    } else if (this.type === 'checkbox' || this.type === 'radio') {
      this.checked = false;
    } else {
      $input.val('');
    }
  });

  // Notify custom cascade handlers of reset
  $(document).trigger('datatable-filter:reset', {
    button: resetButton,
    targetTables,
    elements: filterElements.get()
  });

  isResettingFilters = false;

  // Trigger single reload for each target table
  targetTables.forEach((tableEl) => {
    reloadTable(tableEl, 0);
  });
}

/**
 * Global initialization and delegated event binding.
 */
function initializeGlobalDataTableFilters() {
  if (!window.jQuery || !window.jQuery.fn.dataTable) return;
  const $ = window.jQuery;

  // 1. Hook into preXhr.dt for all DataTables
  $(document)
    .off('preXhr.dt.globalDataTableFilters')
    .on('preXhr.dt.globalDataTableFilters', 'table', function (event, settings, data) {
      const tableElement = settings.nTable;
      const activeFilters = getActiveFiltersForTable(tableElement);
      Object.assign(data, activeFilters);
    });

  // 2. Change event on selects, checkboxes, radios
  $(document)
    .off('change.globalDataTableFilters', filterSelector)
    .on('change.globalDataTableFilters', filterSelector, function () {
      if (isResettingFilters || this.dataset.datatableFilter === 'false') return;

      const targetTables = getTargetTables(this);
      const key = getFilterKey(this);
      const val = $(this).val();

      // Trigger custom hook for dependent filters
      $(document).trigger('datatable-filter:changed', {
        element: this,
        key: key,
        value: val,
        targetTables: targetTables
      });

      targetTables.forEach((tableEl) => reloadTable(tableEl, 50));
    });

  // 3. Debounced input event on text/search/number inputs
  $(document)
    .off('input.globalDataTableFilters', filterSelector)
    .on('input.globalDataTableFilters', filterSelector, function () {
      if (isResettingFilters || this.dataset.datatableFilter === 'false') return;
      if (this.tagName === 'SELECT') return;

      const targetTables = getTargetTables(this);
      targetTables.forEach((tableEl) => reloadTable(tableEl, 400));
    });

  // 4. Delegated reset button click
  $(document)
    .off('click.globalDataTableFilters', resetSelector)
    .on('click.globalDataTableFilters', resetSelector, function (e) {
      e.preventDefault();
      handleFilterReset(this);
    });

  // 5. Initialize Select2
  initializeDataTableFilterSelects();
}

// Auto-run on document ready and window load
if (typeof window !== 'undefined') {
  if (window.jQuery) {
    window.jQuery(initializeGlobalDataTableFilters);
    window.addEventListener('load', initializeDataTableFilterSelects);
  } else {
    document.addEventListener('DOMContentLoaded', initializeGlobalDataTableFilters);
    window.addEventListener('load', initializeDataTableFilterSelects);
  }
}

export {
  initializeDataTableFilterSelects,
  initializeGlobalDataTableFilters,
  getActiveFiltersForTable,
  reloadTable,
  handleFilterReset
};
