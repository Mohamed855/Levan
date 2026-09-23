'use strict';

let currentCustomerId;

$(document).ready(function () {
    currentCustomerId = window.customerId;
    
    // يمكن إضافة تحديث تلقائي للقائمة إذا لزم الأمر
    // setInterval(refreshPaymentHistoryList, 60000); // كل دقيقة
});

/**
 * تحديث قائمة سجل السدادات
 */
function refreshPaymentHistoryList() {
    if (!currentCustomerId) {
        console.error('معرف العميل غير موجود');
        return;
    }

    $.ajax({
        url: getRouteUrl('customers.payment-history.list', { customer: currentCustomerId }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#payment-history-list').html(response.html);
                
                // تحديث العداد
                if (response.count !== undefined) {
                    $('#payments-count').text(response.count + ' سداد');
                }
            } else {
                toastr.error('حدث خطأ في تحديث القائمة');
            }
        },
        error: function (xhr) {
            console.error('خطأ في تحديث القائمة:', xhr);
            toastr.error('حدث خطأ في تحديث القائمة');
        }
    });
}

/**
 * توليد رابط بناءً على اسم المسار
 */
function getRouteUrl(routeName, params = {}) {
    if (typeof window.routes === 'undefined' || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }
    
    let url = window.routes[routeName];
    
    if (params.customer) {
        url = url.replace(/CUSTOMER_ID/g, params.customer);
    }
    
    if (!url.startsWith('http') && !url.startsWith('/')) {
        url = '/' + url;
    }
    
    return url;
}

// تصدير للنافذة العامة
window.refreshPaymentHistoryList = refreshPaymentHistoryList;