/**
 * Admin Collections Selection Management - Fast & Optimized
 * ==========================================================
 * نظام تحديد تحصيلات المستخدمين محسن للسرعة القصوى
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Admin Collections Selection Module - Ultra Fast
|--------------------------------------------------------------------------
| وحدة إدارة تحديد تحصيلات المستخدمين فائقة السرعة
*/
const AdminCollectionsSelection = {
    /*
    |--------------------------------------------------------------------------
    | Selection State - Minimal & Fast
    |--------------------------------------------------------------------------
    | إدارة حالة التحديد - سريع ومبسط
    */
    state: {
        allIds: new Set(),           // استخدام Set للسرعة القصوى
        isSelectingAll: false,       // هل في وضع تحديد الكل
        isPageSelected: false,       // هل الصفحة محددة

        // إعادة تعيين سريع
        reset() {
            this.allIds.clear();
            this.isSelectingAll = false;
            this.isPageSelected = false;
        },

        // تعيين تحديد الكل
        setAllSelected(ids) {
            this.allIds = new Set(ids);
            this.isSelectingAll = true;
            this.isPageSelected = false;
        },

        // تعيين تحديد الصفحة
        setPageSelected() {
            this.isPageSelected = true;
            this.isSelectingAll = false;
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Cache Elements for Speed
    |--------------------------------------------------------------------------
    | تخزين العناصر مؤقتاً للسرعة
    */
    elements: {
        selectAll: null,
        pageCheckbox: null,
        allCheckbox: null,
        actionButton: null,

        // تهيئة العناصر
        init() {
            this.selectAll = $('#select-all');
            this.pageCheckbox = $('#selectPageCheckbox');
            this.allCheckbox = $('#selectAllCheckbox');
            this.actionButton = $('.btn-export .dt-button-text');
        },

        // التحقق من التهيئة
        ensureInit() {
            if (!this.selectAll || this.selectAll.length === 0) {
                this.init();
            }
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Initialization - Super Fast
    |--------------------------------------------------------------------------
    | تهيئة فائقة السرعة
    */
    initialize() {
        this.enableCheckboxes();
        this.elements.init();
        this.bindEvents();
    },

    /*
    |--------------------------------------------------------------------------
    | Enable Checkboxes Instantly
    |--------------------------------------------------------------------------
    | تمكين الـ checkboxes فوراً
    */
    enableCheckboxes() {
        $('#select-all, #selectPageCheckbox, #selectAllCheckbox').prop('disabled', false);
    },

    /*
    |--------------------------------------------------------------------------
    | Event Binding - Optimized
    |--------------------------------------------------------------------------
    | ربط الأحداث محسن
    */
    bindEvents() {
        // تحديد صف واحد - فوري
        $(document).on('change', '.row-checkbox', (e) => {
            this.handleSingleRow(e.target);
        });

        // تحديد الصفحة - فوري
        $('#select-page').on('click', (e) => {
            e.preventDefault();
            this.togglePageSelection();
        });

        // تحديد الكل - فوري
        $('#select-all-items').on('click', (e) => {
            e.preventDefault();
            this.toggleAllSelection();
        });

        // الـ checkbox الرئيسي - فوري
        $('#select-all').on('change', (e) => {
            if (e.target.checked) {
                this.selectPage();
            } else {
                this.deselectPage();
            }
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Single Row Handler - Lightning Fast
    |--------------------------------------------------------------------------
    | معالج الصف الواحد فائق السرعة
    */
    handleSingleRow(checkbox) {
        // إلغاء أي وضع تحديد كامل فوراً
        if (this.state.isSelectingAll || this.state.isPageSelected) {
            this.state.reset();
            this.elements.ensureInit();
            this.elements.allCheckbox.prop('checked', false);
            this.elements.pageCheckbox.prop('checked', false);
        }

        // تحديث فوري للحالة
        this.updateMainCheckbox();
        this.updateActionButton();
    },

    /*
    |--------------------------------------------------------------------------
    | Page Selection - Ultra Fast
    |--------------------------------------------------------------------------
    | تحديد الصفحة فائق السرعة
    */
    togglePageSelection() {
        if (this.state.isPageSelected) {
            this.deselectPage();
        } else {
            this.selectPage();
        }
    },

    selectPage() {
        // إلغاء تحديد الكل فوراً
        this.state.reset();

        // تحديد كل عناصر الصفحة دفعة واحدة
        $('.row-checkbox').prop('checked', true);

        // تحديث الحالة فوراً
        this.state.setPageSelected();
        this.elements.ensureInit();
        this.elements.selectAll.prop('checked', true);
        this.elements.pageCheckbox.prop('checked', true);
        this.elements.allCheckbox.prop('checked', false);

        this.updateActionButton();
    },

    deselectPage() {
        // إلغاء كل التحديدات فوراً
        $('.row-checkbox, #select-all').prop('checked', false);

        this.state.reset();
        this.elements.ensureInit();
        this.elements.pageCheckbox.prop('checked', false);

        this.updateActionButton();
    },

    /*
    |--------------------------------------------------------------------------
    | All Selection - Fast Without Numbers
    |--------------------------------------------------------------------------
    | تحديد الكل سريع بدون أرقام
    */
    toggleAllSelection() {
        if (this.state.isSelectingAll) {
            this.clearAll();
        } else {
            this.selectAll();
        }
    },

    selectAll() {
        // إظهار حالة التحميل فوراً
        this.showLoadingState();

        // إلغاء تحديد الصفحة فوراً
        this.state.isPageSelected = false;
        this.elements.ensureInit();
        this.elements.pageCheckbox.prop('checked', false);

        // جلب البيانات
        this.fetchAllIds();
    },

    /*
    |--------------------------------------------------------------------------
    | Fetch All IDs - Optimized
    |--------------------------------------------------------------------------
    | جلب جميع المعرفات محسن
    */
    fetchAllIds() {
        const filters = this.getCurrentFilters();

        $.ajax({
            url: window.routes.adminCollectionsGetAllIds,
            type: 'GET',
            data: filters,
            timeout: 10000,
            success: (response) => {
                if (response.success && response.collection_ids) {
                    this.handleFetchSuccess(response.collection_ids);
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

    /*
    |--------------------------------------------------------------------------
    | Handle Fetch Results - Super Fast
    |--------------------------------------------------------------------------
    | معالجة نتائج الجلب فائقة السرعة
    */
    handleFetchSuccess(ids) {
        this.state.setAllSelected(ids);

        $('.row-checkbox').prop('checked', true);
        this.elements.ensureInit();
        this.elements.selectAll.prop('checked', true);
        this.elements.allCheckbox.prop('checked', true);

        this.updateActionButton();

        if (ids.length === 0) {
            toastr.warning('لا توجد عناصر متاحة للتحديد حالياً', '', {
                timeOut: 3000,
                progressBar: true
            });
        } else {
            toastr.success(`تم تحديد ${ids.length} تحصيل بنجاح`, '', {
                timeOut: 2000,
                progressBar: true
            });
        }
    },

    handleFetchError(message) {
        this.state.reset();
        this.elements.ensureInit();
        this.elements.allCheckbox.prop('checked', false);

        toastr.error(message, '', {
            timeOut: 3000
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Loading States - Fast & Clean
    |--------------------------------------------------------------------------
    | حالات التحميل سريعة ونظيفة
    */
    showLoadingState() {
        $('#select-all-items span').html('<i class="fas fa-spinner fa-spin me-2"></i>جاري التحميل...');
        $('#select-all-items').addClass('disabled');
    },

    hideLoadingState() {
        $('#select-all-items span').text('تحديد الكل');
        $('#select-all-items').removeClass('disabled');
    },

    /*
    |--------------------------------------------------------------------------
    | Update UI Elements - Lightning Fast
    |--------------------------------------------------------------------------
    | تحديث عناصر الواجهة فائق السرعة
    */
    updateMainCheckbox() {
        this.elements.ensureInit();

        const checkboxes = $('.row-checkbox');
        const total = checkboxes.length;
        const checked = checkboxes.filter(':checked').length;

        // إذا كان في وضع تحديد الكل
        if (this.state.isSelectingAll) {
            this.elements.selectAll.prop({
                indeterminate: false,
                checked: true
            });
            return;
        }

        // تحديث عادي
        if (checked === 0) {
            this.elements.selectAll.prop({
                indeterminate: false,
                checked: false
            });
        } else if (checked === total && total > 0) {
            this.elements.selectAll.prop({
                indeterminate: false,
                checked: true
            });
        } else {
            this.elements.selectAll.prop({
                indeterminate: true,
                checked: false
            });
        }
    },

    updateActionButton() {
        this.elements.ensureInit();

        const selectedCount = this.getSelectedIds().length;

        if (selectedCount > 0) {
            this.elements.actionButton.html(`الإجراءات (${selectedCount})`);
        } else {
            this.elements.actionButton.html('الإجراءات');
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Update After Table Draw - Optimized
    |--------------------------------------------------------------------------
    | تحديث بعد إعادة رسم الجدول محسن
    */
    updateAfterDraw() {
        // تحديث العناصر
        this.elements.init();

        if (this.state.isSelectingAll) {
            // تحديد جميع العناصر المرئية
            $('.row-checkbox').prop('checked', true);
            this.elements.selectAll.prop('checked', true);
        } else if (this.state.isPageSelected) {
            // تحديد عناصر الصفحة
            $('.row-checkbox').prop('checked', true);
            this.elements.selectAll.prop('checked', true);
        } else {
            // تحديث عادي
            this.updateMainCheckbox();
        }

        this.updateActionButton();
    },

    /*
    |--------------------------------------------------------------------------
    | Clear All - Instant
    |--------------------------------------------------------------------------
    | مسح الكل فوري
    */
    clearAll() {
        // إعادة تعيين فوري
        this.state.reset();

        // مسح جميع العناصر
        $('.row-checkbox, #select-all, #selectAllCheckbox, #selectPageCheckbox').prop('checked', false);

        // تحديث الزر
        this.updateActionButton();
    },

/*
|--------------------------------------------------------------------------
| Get Selected IDs - Final, Robust & Sanitized Solution
|--------------------------------------------------------------------------
| الحصول على المعرفات المحددة - حل نهائي وقوي مع تنظيف البيانات
*/
getSelectedIds() {
    // الحالة الأولى: تحديد الكل (تم بالفعل جلب IDs رقمية من الخادم)
    if (this.state.isSelectingAll && this.state.allIds.size > 0) {
        // إرجاع مصفوفة من الأرقام الصحيحة مباشرة من الحالة المخزنة
        return Array.from(this.state.allIds);
    }

    // الحالة الثانية: تحديد الصفحة (يجب جمع IDs من الـ checkboxes وتحويلها وتنظيفها)
    const rawIds = [];
    $('.row-checkbox:checked').each(function () {
        const val = $(this).val();
        if (val) { // التأكد من أن القيمة ليست فارغة أو null
            rawIds.push(val);
        }
    });

    // **الخطوة الحاسمة: تحويل وتنظيف المصفوفة**
    const sanitizedIds = rawIds
        .map(id => parseInt(id, 10)) // 1. تحويل كل عنصر إلى رقم صحيح
        .filter(id => !isNaN(id));   // 2. إزالة أي عناصر لم ينجح تحويلها (مثل 'abc')

    return sanitizedIds;
},

    /*
    |--------------------------------------------------------------------------
    | Helper: Parse Checkbox Value - For Robustness
    |--------------------------------------------------------------------------
    | دالة مساعدة لتحويل قيمة الـ checkbox إلى رقم صحيح بأمان
    | (هذه الدالة اختيارية ولكنها تزيد من قوة الكود وقابليته للصيانة)
    */
    _parseIdFromString(idString) {
        const id = parseInt(idString, 10);
        // التحقق من أن التحويل تم بنجاح وأن القيمة ليست NaN (Not-a-Number)
        return isNaN(id) ? null : id;
    },

    /*
    |--------------------------------------------------------------------------
    | Get Current Filters - Fast
    |--------------------------------------------------------------------------
    | الحصول على الفلاتر الحالية سريع
    */
    getCurrentFilters() {
        return {
            status: $('#filter-status').val() || '',
            customer_id: $('#filter-customer').val() || '',
            payment_method_id: $('#filter-payment-method').val() || '',
            collection_type: $('#filter-collection-type').val() || '',
            payment_type: $('#filter-payment-type').val() || '',
            added_by: $('#filter-added-by').val() || ''
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    | مراقبة الأداء
    */
    getStats() {
        return {
            selectedCount: this.getSelectedIds().length,
            allIdsCount: this.state.allIds.size,
            isSelectingAll: this.state.isSelectingAll,
            isPageSelected: this.state.isPageSelected
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    | تنظيف
    */
    cleanup() {
        this.state.reset();
        this.elements = {
            selectAll: null,
            pageCheckbox: null,
            allCheckbox: null,
            actionButton: null,
            init() { },
            ensureInit() { }
        };
    }
};

/*
|--------------------------------------------------------------------------
| Export to Global Scope
|--------------------------------------------------------------------------
| تصدير للنطاق العام
*/
window.AdminCollectionsSelection = AdminCollectionsSelection;
