/**
 * Activity Log Selection Management
 * ==================================
 * تحديد سجلات النشاط: تحديد الصفحة الحالية أو تحديد كل السجلات المطابقة للفلاتر
 */

'use strict';

const ActivityLogSelection = {
    state: {
        allIds: new Set(),
        isSelectingAll: false,
        isPageSelected: false,

        reset() {
            this.allIds.clear();
            this.isSelectingAll = false;
            this.isPageSelected = false;
        },

        setAllSelected(ids) {
            this.allIds = new Set(ids);
            this.isSelectingAll = true;
            this.isPageSelected = false;
        },

        setPageSelected() {
            this.isPageSelected = true;
            this.isSelectingAll = false;
        }
    },

    elements: {
        selectAll: null,
        pageCheckbox: null,
        allCheckbox: null,

        init() {
            this.selectAll = $('#select-all');
            this.pageCheckbox = $('#selectPageCheckbox');
            this.allCheckbox = $('#selectAllCheckbox');
        },

        ensureInit() {
            if (!this.selectAll || this.selectAll.length === 0) {
                this.init();
            }
        }
    },

    initialize() {
        this.elements.init();
        $('#select-all, #selectPageCheckbox, #selectAllCheckbox').prop('disabled', false);
        this.bindEvents();
    },

    bindEvents() {
        $(document).on('change', '.row-checkbox', (e) => {
            this.handleSingleRow(e.target);
        });

        $('#select-page').on('click', (e) => {
            e.preventDefault();
            this.togglePageSelection();
        });

        $('#select-all-items').on('click', (e) => {
            e.preventDefault();
            this.toggleAllSelection();
        });

        $('#select-all').on('change', (e) => {
            if (e.target.checked) {
                this.selectPage();
            } else {
                this.deselectPage();
            }
        });
    },

    handleSingleRow(checkbox) {
        if (this.state.isSelectingAll || this.state.isPageSelected) {
            this.state.reset();
            this.elements.ensureInit();
            this.elements.allCheckbox.prop('checked', false);
            this.elements.pageCheckbox.prop('checked', false);
        }

        this.updateMainCheckbox();
    },

    togglePageSelection() {
        if (this.state.isPageSelected) {
            this.deselectPage();
        } else {
            this.selectPage();
        }
    },

    selectPage() {
        this.state.reset();
        $('.row-checkbox').prop('checked', true);

        this.state.setPageSelected();
        this.elements.ensureInit();
        this.elements.selectAll.prop('checked', true);
        this.elements.pageCheckbox.prop('checked', true);
        this.elements.allCheckbox.prop('checked', false);
    },

    deselectPage() {
        $('.row-checkbox, #select-all').prop('checked', false);

        this.state.reset();
        this.elements.ensureInit();
        this.elements.pageCheckbox.prop('checked', false);
    },

    toggleAllSelection() {
        if (this.state.isSelectingAll) {
            this.clearAll();
        } else {
            this.selectAll();
        }
    },

    selectAll() {
        this.showLoadingState();

        this.state.isPageSelected = false;
        this.elements.ensureInit();
        this.elements.pageCheckbox.prop('checked', false);

        this.fetchAllIds();
    },

    fetchAllIds() {
        const filters = this.getCurrentFilters();

        $.ajax({
            url: window.routes.activityLogsGetAllIds,
            type: 'GET',
            data: filters,
            timeout: 15000,
            success: (response) => {
                if (response.success && response.ids) {
                    this.handleFetchSuccess(response.ids, response.truncated, response.total);
                } else {
                    this.handleFetchError('استجابة غير صحيحة');
                }
            },
            error: (xhr, status) => {
                let message = 'حدث خطأ أثناء جلب البيانات';
                if (status === 'timeout') {
                    message = 'انتهت مهلة الاتصال';
                }
                this.handleFetchError(message);
            },
            complete: () => {
                this.hideLoadingState();
            }
        });
    },

    handleFetchSuccess(ids, truncated, total) {
        this.state.setAllSelected(ids);

        $('.row-checkbox').prop('checked', true);
        this.elements.ensureInit();
        this.elements.selectAll.prop('checked', true);
        this.elements.allCheckbox.prop('checked', true);

        if (ids.length === 0) {
            toastr.warning('لا توجد سجلات مطابقة للتحديد حالياً');
        } else if (truncated) {
            toastr.warning(`تم تحديد أول ${ids.length} سجل من أصل ${total} (تم تجاوز الحد الأقصى للتصدير دفعة واحدة)`);
        } else {
            toastr.success(`تم تحديد ${ids.length} سجل بنجاح`, '', { timeOut: 2000 });
        }
    },

    handleFetchError(message) {
        this.state.reset();
        this.elements.ensureInit();
        this.elements.allCheckbox.prop('checked', false);

        toastr.error(message);
    },

    showLoadingState() {
        $('#select-all-items span').html('<i class="fas fa-spinner fa-spin me-2"></i>جاري التحميل...');
        $('#select-all-items').addClass('disabled');
    },

    hideLoadingState() {
        $('#select-all-items span').text('الكل');
        $('#select-all-items').removeClass('disabled');
    },

    updateMainCheckbox() {
        this.elements.ensureInit();

        const checkboxes = $('.row-checkbox');
        const total = checkboxes.length;
        const checked = checkboxes.filter(':checked').length;

        if (this.state.isSelectingAll) {
            this.elements.selectAll.prop({ indeterminate: false, checked: true });
            return;
        }

        if (checked === 0) {
            this.elements.selectAll.prop({ indeterminate: false, checked: false });
        } else if (checked === total && total > 0) {
            this.elements.selectAll.prop({ indeterminate: false, checked: true });
        } else {
            this.elements.selectAll.prop({ indeterminate: true, checked: false });
        }
    },

    updateAfterDraw() {
        this.elements.init();

        if (this.state.isSelectingAll || this.state.isPageSelected) {
            $('.row-checkbox').prop('checked', true);
            this.elements.selectAll.prop('checked', true);
        } else {
            this.updateMainCheckbox();
        }
    },

    clearAll() {
        this.state.reset();
        $('.row-checkbox, #select-all, #selectAllCheckbox, #selectPageCheckbox').prop('checked', false);
    },

    getSelectedIds() {
        if (this.state.isSelectingAll && this.state.allIds.size > 0) {
            return Array.from(this.state.allIds);
        }

        const ids = [];
        $('.row-checkbox:checked').each(function () {
            const id = $(this).val();
            if (id) ids.push(id);
        });

        return ids;
    },

    getCurrentFilters() {
        let search = '';
        if ($.fn.DataTable.isDataTable('#activity-logs-table')) {
            search = $('#activity-logs-table').DataTable().search() || '';
        }

        return {
            user_id: $('#filter-user').val() || '',
            log_name: $('#filter-log-name').val() || '',
            search: search
        };
    }
};

window.ActivityLogSelection = ActivityLogSelection;
