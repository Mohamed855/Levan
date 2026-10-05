/**
 * User Dashboard JavaScript
 * ========================
 * إدارة لوحة تحكم المستخدم وجدول المتابعات
 */

'use strict';

$(document).ready(function () {
    initializeUserDashboard();
});

/**
 * تهيئة لوحة تحكم المستخدم
 */
function initializeUserDashboard() {
    // تهيئة جدول المتابعات
    if ($('#my-upcoming-followups-table').length) {
        // console.log('User Table found, initializing...');
        initializeMyFollowupsTable();
    }

    // تهيئة Swiper للأهداف (إذا وجد)
    initializeTargetsSwiper();
}

/**
 * تهيئة جدول متابعات المستخدم
 */
function initializeMyFollowupsTable() {
    if ($.fn.DataTable.isDataTable('#my-upcoming-followups-table')) {
        $('#my-upcoming-followups-table').DataTable().destroy();
    }

    if (!window.userRoutes || !window.userRoutes.myUpcomingFollowUps) {
        console.error('User routes not defined');
        return;
    }

    $('#my-upcoming-followups-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.userRoutes.myUpcomingFollowUps,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            error: function (xhr, error) {
                $('#my-upcoming-followups-table tbody').html(
                    '<tr><td colspan="7" class="text-center text-danger py-5">'
                    + '<div class="mb-2">حدث خطأ في تحميل البيانات</div>'
                    + '<small class="text-muted">يرجى المحاولة مرة أخرى</small>'
                    + '</td></tr>'
                );
            }
        },
        columns: [
            { data: 'customer_name', name: 'customer_name', orderable: true, searchable: true },
            { data: 'account_number', name: 'account_number', orderable: true, searchable: true, className: 'text-center' },
            { data: 'identity_number', name: 'identity_number', orderable: true, searchable: true, className: 'text-center' },
            { data: 'company_name', name: 'company_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'portfolio_name', name: 'portfolio_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'follow_result', name: 'follow_result', orderable: true, searchable: true, className: 'text-center' },
            { data: 'remaining_debt', name: 'remaining_debt', orderable: true, searchable: false, className: 'text-center' }
        ],
        order: [[6, 'desc']], // مبدئياً حسب المديونية
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            url: window.assets?.arabicDataTable || '/static/i18n/ar.json',
            processing: 'جاري التحميل...',
            emptyTable: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام يومية</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>',
            zeroRecords: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام يومية</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>'
        },
        responsive: true,
        autoWidth: false,
        scrollX: false
    });
}
/**
 * تهيئة Swiper للأهداف
 */
function initializeTargetsSwiper() {
    const swiperElement = document.getElementById('targetsSwiper');
    if (!swiperElement) return;

    const swiper = new Swiper('#targetsSwiper', {
        slidesPerView: 1,
        spaceBetween: 10,
        loop: false,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 10,
            },
            992: {
                slidesPerView: 3,
                spaceBetween: 15,
            },
        },
        rtl: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        grabCursor: true,
        watchSlidesProgress: true,
        on: {
            init: function () {
                // console.log('Targets Swiper initialized with ' + this.slides.length + ' slides');

                // عرض/إخفاء أزرار التنقل بناءً على عدد الشرائح
                if (this.slides.length <= 1) {
                    document.querySelectorAll('.nav-arrow').forEach(arrow => {
                        arrow.style.display = 'none';
                    });
                }
            }
        }
    });

    // ربط أزرار التنقل
    bindSwiperNavigation(swiper);

    // تحديث Swiper بعد تحميل الصفحة
    window.addEventListener('load', function () {
        swiper.update();
        // console.log('Targets Swiper updated after window load');
    });
}

/**
 * ربط أزرار التنقل بـ Swiper
 */
function bindSwiperNavigation(swiper) {
    const prevButton = document.getElementById('prevTarget');
    const nextButton = document.getElementById('nextTarget');

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            swiper.slidePrev();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            swiper.slideNext();
        });
    }
}

/**
 * إعدادات لغة DataTable
 */
function getDataTableLanguage(type = 'user') {
    const baseLanguage = {
        url: window.assets?.arabicDataTable || '/static/i18n/ar.json',
        processing: "جاري تحميل البيانات...",
        loadingRecords: "جاري تحميل البيانات...",
        zeroRecords: "لا يوجد عملاء مقرر متابعتهم اليوم",
        emptyTable: "لا توجد متابعات مستحقة",
        info: "عرض _START_ إلى _END_ من أصل _TOTAL_ عميل",
        infoEmpty: "لا يوجد عملاء",
        infoFiltered: "(مرشح من _MAX_ عميل)",
        paginate: {
            first: "الأول",
            last: "الأخير",
            next: "التالي",
            previous: "السابق"
        }
    };

    if (type === 'user') {
        baseLanguage.processing = "جاري تحميل عملائك...";
        baseLanguage.loadingRecords = "جاري تحميل بيانات عملائك...";
        // baseLanguage.search = "بحث في عملائك:";
    }

    return baseLanguage;
}

/**
 * عرض رسالة خطأ في الجدول
 */
function showTableError(tableId, colspan) {
    $(tableId + ' tbody').html(
        `<tr><td colspan="${colspan}" class="text-center text-danger py-5">` +
        '<div class="mb-3"><i class="ti ti-alert-circle" style="font-size: 3rem;"></i></div>' +
        '<h6 class="text-danger mb-2">حدث خطأ في تحميل البيانات</h6>' +
        '<small class="text-muted">يرجى المحاولة مرة أخرى</small>' +
        '</td></tr>'
    );
}

/**
 * عرض حالة فارغة في الجدول
 */
function showEmptyState(tableId, colspan, type = 'general') {
    const messages = {
        user: {
            icon: 'ti-calendar-heart',
            title: 'لا يوجد عملاء مقرر متابعتهم اليوم',
            subtitle: 'استمتع بيومك الهادئ!'
        },
        general: {
            icon: 'ti-calendar-heart',
            title: 'لا يوجد عملاء مقرر متابعتهم اليوم',
            subtitle: 'جميع المتابعات محدثة ✅'
        }
    };

    const message = messages[type] || messages.general;

    $(tableId + ' tbody').html(
        `<tr><td colspan="${colspan}" class="text-center py-5">` +
        `<div class="mb-3"><i class="ti ${message.icon}" style="font-size: 3rem; color: #d9d9d9;"></i></div>` +
        `<h6 class="text-muted mb-2">${message.title}</h6>` +
        `<small class="text-muted">${message.subtitle}</small>` +
        '</td></tr>'
    );
}
