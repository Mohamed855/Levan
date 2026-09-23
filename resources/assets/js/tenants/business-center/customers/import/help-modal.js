// resources/assets/js/tenants/business-center/customers/import/help-modal.js

(function () {
    'use strict';

    // ✅ تحقق من المتغيرات العامة المطلوبة
    function checkRequiredGlobals() {
        return typeof window.routes !== 'undefined' &&
            typeof window.routes.getMetadata !== 'undefined';
    }

    // ✅ انتظر حتى تصبح المتغيرات متاحة
    function initializeWhenReady() {
        if (checkRequiredGlobals()) {
            loadMetadataForImport();
        } else {
            // جرب كل 100 ملي ثانية
            const interval = setInterval(() => {
                if (checkRequiredGlobals()) {
                    clearInterval(interval);
                    loadMetadataForImport();
                }
            }, 100);

            // توقف بعد 10 ثواني
            setTimeout(() => {
                clearInterval(interval);
                console.error('Failed to load metadata: window.routes not available');
                showErrorState();
            }, 10000);
        }
    }

    // ✅ شغّل عند تحميل الـ DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWhenReady);
    } else {
        initializeWhenReady();
    }

    // ✅ تفويض الأحداث (event delegation) بدل onclick المضمّن في الـ HTML
    document.addEventListener('click', function (event) {
        const tabBtn = event.target.closest('.js-switch-tab-btn');
        if (tabBtn) {
            switchTab(event, tabBtn.dataset.tab);
            return;
        }

        const copyBtn = event.target.closest('.metadata-item-copy');
        if (copyBtn) {
            copyToClipboard(copyBtn.dataset.copyValue, copyBtn);
        }
    });

    // ✅ دالة التبويبات - اجعلها في window حتى تكون متاحة للـ onclick
    window.switchTab = function (event, tabId) {
        event.preventDefault();

        // اخفِ جميع التبويبات
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });

        // أزل الـ active class من جميع الأزرار
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // اعرض التبويب المختار
        const selectedTab = document.getElementById(tabId);
        if (selectedTab) {
            selectedTab.style.display = 'block';
        }

        // أضف الـ active class للزر الحالي
        event.target.classList.add('active');
    };

    // ✅ أقرب حاوية قابلة للتمرير (احتياط دفاعي، لم يعد مطلوباً بشدة بعد إزالة الـ textarea المخفي)
    function getScrollableAncestor(element) {
        return element ? element.closest('.metadata-list') : null;
    }

    // ✅ دالة نسخ النص - Clipboard API أولاً، ثم تحديد النص المرئي كخطة بديلة
    window.copyToClipboard = function (text, element) {
        // الطريقة الحديثة (Clipboard API) - تحتاج سياق آمن (HTTPS)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    showCopySuccess(element, text);
                })
                .catch(error => {
                    console.error('Clipboard API failed:', error);
                    selectAndCopyFallback(text, element);
                });
        } else {
            // لا تتوفر Clipboard API (غالباً بسبب عدم استخدام HTTPS)
            selectAndCopyFallback(text, element);
        }
    };

    // ✅ خطة بديلة: نحدّد النص المرئي فعلياً على الشاشة (بدون أي عنصر مخفي خارج الشاشة)
    // هذا يمنع أي قفزة تمرير لأن العنصر أصلاً ظاهر ومكانه لا يتغير.
    // document.execCommand('copy') أصبحت مهملة (deprecated) وغير مضمونة العمل في المتصفحات
    // الحديثة، لذلك نجرّبها كمحاولة إضافية فقط، وإن فشلت يبقى النص محدداً للنسخ اليدوي (Ctrl+C)
    function selectAndCopyFallback(text, element) {
        const row = element.closest('.metadata-item');
        const idSpan = row ? row.querySelector('.metadata-item-id') : null;

        if (!idSpan) {
            // حالة غير متوقعة: لم نجد العنصر لنحدد نصه، ولا يوجد ما نقدمه للمستخدم
            showCopyError(element);
            return;
        }

        let copiedAutomatically = false;

        try {
            const range = document.createRange();
            range.selectNodeContents(idSpan);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            copiedAutomatically = !!(document.execCommand && document.execCommand('copy'));
        } catch (error) {
            console.warn('Automatic copy unavailable, text selected for manual copy (Ctrl+C):', error);
        }

        if (copiedAutomatically) {
            showCopySuccess(element, text);
        } else {
            showManualCopyHint(element, text);
        }
    }

    // ✅ اعرض رسالة النجاح
    function showCopySuccess(element, text) {
        const originalIcon = element.innerHTML;
        element.innerHTML = '<i class="ti ti-check"></i>';
        element.style.color = '#28a745';

        setTimeout(() => {
            element.innerHTML = originalIcon;
            element.style.color = '#667eea';
        }, 2000);

        // ✅ تحقق من وجود toastr قبل استدعائها - بدون هذا التحقق، أي خطأ هنا
        // كان يُفسَّر خطأً على أنه "فشل النسخ" رغم أن النسخ تم بنجاح فعلياً
        if (typeof toastr !== 'undefined') {
            toastr.success(`تم نسخ الرقم: ${text}`);
        } else {
            console.warn('toastr غير معرّف، تم تخطي إشعار النجاح (النسخ تم بنجاح)');
        }
    }

    // ✅ اعرض تلميحاً بأن النص مُحدَّد ويحتاج نسخ يدوي (Ctrl+C) عند تعذّر النسخ التلقائي
    function showManualCopyHint(element, text) {
        const originalIcon = element.innerHTML;
        element.innerHTML = '<i class="ti ti-hand-click"></i>';
        element.style.color = '#f0ad4e';

        setTimeout(() => {
            element.innerHTML = originalIcon;
            element.style.color = '#667eea';
        }, 2500);

        if (typeof toastr !== 'undefined') {
            toastr.info(`الرقم محدَّد الآن، اضغط Ctrl+C للنسخ: ${text}`);
        } else {
            console.info(`الرقم محدَّد الآن، اضغط Ctrl+C للنسخ: ${text}`);
        }
    }

    // ✅ اعرض رسالة الخطأ
    function showCopyError(element) {
        const originalIcon = element.innerHTML;
        element.innerHTML = '<i class="ti ti-x"></i>';
        element.style.color = '#dc3545';

        setTimeout(() => {
            element.innerHTML = originalIcon;
            element.style.color = '#667eea';
        }, 2000);

        if (typeof toastr !== 'undefined') {
            toastr.error('فشل نسخ الرقم');
        }
    }

    // ✅ دالة تحميل البيانات
    function loadMetadataForImport() {
        if (!window.routes || !window.routes.getMetadata) {
            console.error('Routes not defined');
            showErrorState();
            return;
        }

        fetch(window.routes.getMetadata)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.nationalities) {
                    displayNationalities(data.nationalities);
                }
                if (data.statuses) {
                    displayCustomerStatuses(data.statuses);
                }
                if (data.managers) {
                    displayRelationshipManagers(data.managers);
                }
            })
            .catch(error => {
                console.error('Error loading metadata:', error);
                showErrorState();
            });
    }

    // ✅ عرض الجنسيات
    function displayNationalities(nationalities) {
        const container = document.getElementById('nationalities-list');
        if (!container) {
            console.error('nationalities-list container not found');
            return;
        }

        if (!nationalities || nationalities.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><p>لا توجد جنسيات متاحة</p></div>';
            return;
        }

        container.innerHTML = nationalities.map(n => `
            <div class="metadata-item">
                <span class="metadata-item-id">${n.id}</span>
                <span class="metadata-item-name">${n.name}</span>
                <span class="metadata-item-copy" data-copy-value="${n.id}" title="انسخ الرقم">
                    <i class="ti ti-copy"></i>
                </span>
            </div>
        `).join('');
    }

    // ✅ عرض حالات العملاء
    function displayCustomerStatuses(statuses) {
        const container = document.getElementById('statuses-list');
        if (!container) {
            console.error('statuses-list container not found');
            return;
        }

        if (!statuses || statuses.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><p>لا توجد حالات متاحة</p></div>';
            return;
        }

        container.innerHTML = statuses.map(s => `
            <div class="metadata-item">
                <span class="metadata-item-id">${s.id}</span>
                <span class="metadata-item-name">${s.name}</span>
                <span class="metadata-item-copy" data-copy-value="${s.id}" title="انسخ الرقم">
                    <i class="ti ti-copy"></i>
                </span>
            </div>
        `).join('');
    }

    // ✅ عرض مسؤولو العلاقة
    function displayRelationshipManagers(managers) {
        const container = document.getElementById('managers-list');
        if (!container) {
            console.error('managers-list container not found');
            return;
        }

        if (!managers || managers.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><p>لا يوجد مسؤولو علاقة متاحين</p></div>';
            return;
        }

        container.innerHTML = managers.map(m => `
            <div class="metadata-item">
                <span class="metadata-item-id">${m.employee_number}</span>
                <span class="metadata-item-name">${m.name}</span>
                <span class="metadata-item-copy" data-copy-value="${m.employee_number}" title="انسخ الرقم">
                    <i class="ti ti-copy"></i>
                </span>
            </div>
        `).join('');
    }

    // ✅ عرض حالة الخطأ
    function showErrorState() {
        const errorHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>حدث خطأ في تحميل البيانات</p></div>';

        ['nationalities-list', 'statuses-list', 'managers-list'].forEach(id => {
            const container = document.getElementById(id);
            if (container) {
                container.innerHTML = errorHTML;
            }
        });
    }
})();
