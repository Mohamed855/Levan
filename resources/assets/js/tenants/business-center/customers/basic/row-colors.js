/**
 * Customer Row Colors Management
 * ==============================
 * إدارة تلوين صفوف العملاء حسب حالة العميل
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Customer Row Colors Module
|--------------------------------------------------------------------------
| وحدة إدارة تلوين الصفوف مع تحسينات للأداء
*/
const CustomerRowColors = {
    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    | إعدادات التلوين
    */
    config: {
        // فئة CSS للصفوف الملونة
        coloredRowClass: 'customer-row-colored',

        // انتقال لوني سلس
        transition: 'background-color 0.3s ease',

        // عناصر يجب الحفاظ على تصميمها الأصلي
        preserveElements: '.row-checkbox, #select-all',

        // تأخير لتحسين الأداء
        debounceDelay: 100
    },

    /*
    |--------------------------------------------------------------------------
    | State Management
    |--------------------------------------------------------------------------
    | إدارة الحالة
    */
    state: {
        isProcessing: false,
        lastProcessTime: 0,
        colorCache: new Map()
    },

    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    | تهيئة الوحدة
    */
    initialize() {
        this.bindEvents();
        this.applyColors();
    },

    /*
    |--------------------------------------------------------------------------
    | Bind Events
    |--------------------------------------------------------------------------
    | ربط الأحداث
    */
    bindEvents() {
        // ربط حدث إعادة رسم الجدول
        if (typeof $('#customers-table').DataTable === 'function') {
            $('#customers-table').on('draw.dt', () => {
                this.applyColorsDebounced();
            });
        }

        // ربط حدث تحديث الصفوف (للاستخدام المستقبلي)
        $(document).on('customerRowUpdate', () => {
            this.applyColorsDebounced();
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Apply Colors with Debounce
    |--------------------------------------------------------------------------
    | تطبيق الألوان مع تأخير لتحسين الأداء
    */
    applyColorsDebounced() {
        // منع التنفيذ المتكرر
        if (this.state.isProcessing) {
            return;
        }

        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.applyColors();
        }, this.config.debounceDelay);
    },

    /*
    |--------------------------------------------------------------------------
    | Apply Row Colors
    |--------------------------------------------------------------------------
    | تطبيق ألوان الصفوف الرئيسي
    */
    applyColors() {
        // التحقق من وجود الجدول
        if (!$('#customers-table').length || typeof $('#customers-table').DataTable !== 'function') {
            return;
        }

        try {
            const table = $('#customers-table').DataTable();

            // التحقق من تهيئة الجدول
            if (!table || !table.rows) {
                return;
            }

            this.state.isProcessing = true;
            this.state.lastProcessTime = Date.now();

            // تطبيق الألوان على كل صف
            table.rows().every((index) => {
                this.processRow(table, index);
            });

        } catch (error) {
            // console.warn('خطأ في تطبيق ألوان الصفوف:', error);
        } finally {
            this.state.isProcessing = false;
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Process Single Row
    |--------------------------------------------------------------------------
    | معالجة صف واحد
    */
    processRow(table, index) {
        try {
            const data = table.row(index).data();
            const rowNode = table.row(index).node();

            if (!data || !rowNode) {
                return;
            }

            // مسح الألوان السابقة
            this.clearRowColors($(rowNode));

            // تطبيق اللون الجديد إذا كان متوفراً
            if (data.status_color) {
                this.applyRowColor($(rowNode), data.status_color);
            }

        } catch (error) {
            // console.warn('خطأ في معالجة الصف:', error);
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Clear Row Colors
    |--------------------------------------------------------------------------
    | مسح ألوان الصف
    */
    clearRowColors($rowNode) {
        $rowNode
            .removeClass(this.config.coloredRowClass)
            .css('background-color', '');
    },

    /*
    |--------------------------------------------------------------------------
    | Apply Row Color
    |--------------------------------------------------------------------------
    | تطبيق لون الصف
    */
    applyRowColor($rowNode, color) {
        // التحقق من صحة اللون
        if (!this.isValidColor(color)) {
            return;
        }

        // تطبيق اللون
        $rowNode
            .addClass(this.config.coloredRowClass)
            .css({
                'background-color': color,
                'transition': this.config.transition
            });

        // الحفاظ على تصميم العناصر المحددة
        this.preserveElementStyles($rowNode);
    },

    /*
    |--------------------------------------------------------------------------
    | Preserve Element Styles
    |--------------------------------------------------------------------------
    | الحفاظ على أنماط العناصر المحددة
    */
    preserveElementStyles($rowNode) {
        $rowNode.find(this.config.preserveElements).css({
            'background-color': '',
            'border': ''
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Color Validation
    |--------------------------------------------------------------------------
    | التحقق من صحة اللون
    */
    isValidColor(color) {
        if (!color || typeof color !== 'string') {
            return false;
        }

        // تحقق سريع من الألوان الشائعة
        const hexPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        const rgbPattern = /^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/;
        const rgbaPattern = /^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)$/;

        return hexPattern.test(color) ||
            rgbPattern.test(color) ||
            rgbaPattern.test(color) ||
            this.isNamedColor(color);
    },

    /*
    |--------------------------------------------------------------------------
    | Named Color Check
    |--------------------------------------------------------------------------
    | التحقق من الألوان المسماة
    */
    isNamedColor(color) {
        const namedColors = [
            'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink',
            'brown', 'black', 'white', 'gray', 'grey', 'transparent'
        ];
        return namedColors.includes(color.toLowerCase());
    },

    /*
    |--------------------------------------------------------------------------
    | Color Utility Functions
    |--------------------------------------------------------------------------
    | دوال مساعدة للألوان
    */

    /**
     * تفتيح لون معين
     * @param {string} color - اللون الأصلي
     * @param {number} amount - مقدار التفتيح (0-1)
     * @returns {string} اللون المفتح
     */
    lightenColor(color, amount = 0.1) {
        // التحقق من صحة المدخلات
        if (!this.isValidColor(color) || amount < 0 || amount > 1) {
            return color;
        }

        // التحقق من الكاش أولاً
        const cacheKey = `${color}_${amount}`;
        if (this.state.colorCache.has(cacheKey)) {
            return this.state.colorCache.get(cacheKey);
        }

        let lightenedColor = color;

        try {
            // معالجة الألوان hex
            if (color.startsWith('#')) {
                lightenedColor = this.lightenHexColor(color, amount);
            }
            // معالجة الألوان rgb/rgba
            else if (color.startsWith('rgb')) {
                lightenedColor = this.lightenRgbColor(color, amount);
            }

            // حفظ في الكاش
            this.state.colorCache.set(cacheKey, lightenedColor);

        } catch (error) {
            // console.warn('خطأ في تفتيح اللون:', error);
            lightenedColor = color; // استخدام اللون الأصلي في حالة الخطأ
        }

        return lightenedColor;
    },

    /**
     * تفتيح لون hex
     * @param {string} hex - اللون hex
     * @param {number} amount - مقدار التفتيح
     * @returns {string} اللون المفتح
     */
    lightenHexColor(hex, amount) {
        // إزالة الرمز #
        const cleanHex = hex.replace('#', '');

        // تحويل إلى RGB
        const r = parseInt(cleanHex.substr(0, 2), 16);
        const g = parseInt(cleanHex.substr(2, 2), 16);
        const b = parseInt(cleanHex.substr(4, 2), 16);

        // تطبيق التفتيح
        const newR = Math.round(r + (255 - r) * amount);
        const newG = Math.round(g + (255 - g) * amount);
        const newB = Math.round(b + (255 - b) * amount);

        // تحويل العودة إلى hex
        return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB.toString(16).padStart(2, '0')}`;
    },

    /**
     * تفتيح لون RGB
     * @param {string} rgb - اللون RGB
     * @param {number} amount - مقدار التفتيح
     * @returns {string} اللون المفتح
     */
    lightenRgbColor(rgb, amount) {
        // استخراج القيم الرقمية
        const matches = rgb.match(/\d+/g);
        if (!matches || matches.length < 3) {
            return rgb;
        }

        const r = parseInt(matches[0]);
        const g = parseInt(matches[1]);
        const b = parseInt(matches[2]);
        const a = rgb.includes('rgba') && matches[3] ? parseFloat(matches[3]) : 1;

        // تطبيق التفتيح
        const newR = Math.round(r + (255 - r) * amount);
        const newG = Math.round(g + (255 - g) * amount);
        const newB = Math.round(b + (255 - b) * amount);

        // إرجاع اللون بالتنسيق المناسب
        return rgb.includes('rgba')
            ? `rgba(${newR}, ${newG}, ${newB}, ${a})`
            : `rgb(${newR}, ${newG}, ${newB})`;
    },

    /**
     * تدرج لوني بين لونين
     * @param {string} color1 - اللون الأول
     * @param {string} color2 - اللون الثاني
     * @param {number} ratio - نسبة المزج (0-1)
     * @returns {string} اللون المختلط
     */
    blendColors(color1, color2, ratio = 0.5) {
        // هذه دالة متقدمة يمكن تطويرها مستقبلاً
        return this.lightenColor(color1, ratio * 0.3);
    },

    /*
    |--------------------------------------------------------------------------
    | Manual Color Application
    |--------------------------------------------------------------------------
    | تطبيق يدوي للألوان
    */

    /**
     * تطبيق لون على صف محدد
     * @param {number} customerId - معرف العميل
     * @param {string} color - اللون المطلوب تطبيقه
     */
    applyColorToCustomer(customerId, color) {
        if (!customerId || !this.isValidColor(color)) {
            return;
        }

        try {
            const table = $('#customers-table').DataTable();

            // البحث عن الصف
            table.rows().every((index) => {
                const data = table.row(index).data();
                if (data && data.id == customerId) {
                    const $rowNode = $(table.row(index).node());
                    this.clearRowColors($rowNode);
                    this.applyRowColor($rowNode, color);
                    return false; // إيقاف البحث
                }
            });

        } catch (error) {
            // console.warn('خطأ في تطبيق اللون على العميل:', error);
        }
    },

    /**
     * مسح لون صف محدد
     * @param {number} customerId - معرف العميل
     */
    clearCustomerColor(customerId) {
        if (!customerId) {
            return;
        }

        try {
            const table = $('#customers-table').DataTable();

            // البحث عن الصف
            table.rows().every((index) => {
                const data = table.row(index).data();
                if (data && data.id == customerId) {
                    const $rowNode = $(table.row(index).node());
                    this.clearRowColors($rowNode);
                    return false; // إيقاف البحث
                }
            });

        } catch (error) {
            // console.warn('خطأ في مسح لون العميل:', error);
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Cache Management
    |--------------------------------------------------------------------------
    | إدارة الكاش
    */

    /**
     * مسح كاش الألوان
     */
    clearColorCache() {
        this.state.colorCache.clear();
    },

    /**
     * الحصول على حجم الكاش
     * @returns {number} عدد العناصر في الكاش
     */
    getCacheSize() {
        return this.state.colorCache.size;
    },

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    | مراقبة الأداء
    */

    /**
     * الحصول على إحصائيات الأداء
     * @returns {object} إحصائيات الأداء
     */
    getPerformanceStats() {
        return {
            isProcessing: this.state.isProcessing,
            lastProcessTime: this.state.lastProcessTime,
            cacheSize: this.getCacheSize(),
            timeSinceLastProcess: Date.now() - this.state.lastProcessTime
        };
    },

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    | تنظيف الموارد
    */

    /**
     * تنظيف الموارد والأحداث
     */
    cleanup() {
        // مسح المؤقتات
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // مسح الكاش
        this.clearColorCache();

        // إلغاء ربط الأحداث
        $('#customers-table').off('draw.dt');
        $(document).off('customerRowUpdate');

        // إعادة تعيين الحالة
        this.state.isProcessing = false;
        this.state.lastProcessTime = 0;
    }
};

/*
|--------------------------------------------------------------------------
| Export to Global Scope
|--------------------------------------------------------------------------
| تصدير للنطاق العام
*/
window.CustomerRowColors = CustomerRowColors;