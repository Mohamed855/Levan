/*
|--------------------------------------------------------------------------
| Customer Phone Request JavaScript
|--------------------------------------------------------------------------
| يحتوي على وظائف JavaScript للتعامل مع نموذج إضافة رقم هاتف جديد
| مع التحقق من صحة الرقم السعودي وعرض رسائل الخطأ المناسبة
*/
$(document).ready(function () {
    $('.phone-input').on('input', function () {
        this.value = this.value.replace(/[^0-9+]/g, '');

        if (this.value.length > 10) {
            this.value = this.value.substring(0, 10);
        }

        if (this.value.length > 0) {
            $(this).removeClass('is-invalid');
            $('#phone-error').text('');
        }
    });

    $('#addPhoneForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const phoneInput = form.find('input[name="phone"]');
        const phoneError = $('#phone-error');
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        if (phoneInput.val().trim() === '') {
            phoneInput.addClass('is-invalid');
            phoneError.text('حقل رقم الهاتف مطلوب');
            return;
        }

        if (!/^[0-9+]+$/.test(phoneInput.val())) {
            phoneInput.addClass('is-invalid');
            phoneError.text('يجب إدخال رقم هاتف صحيح');
            return;
        }

        if (phoneInput.val().length !== 10) {
            phoneInput.addClass('is-invalid');
            phoneError.text('يجب أن يكون رقم الهاتف 10 أرقام بالضبط');
            return;
        }

        phoneInput.removeClass('is-invalid');
        phoneError.text('');

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري المعالجة...');

        $.ajax({
            url: getRouteUrl('customers.phone-requests.store', {
                customer: form.find('input[name="customer_id"]').val()
            }),

            type: 'POST',
            data: form.serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);

                    form[0].reset();

                    refreshPhoneRequestsList();
                } else {
                    toastr.error(response.message || 'حدث خطأ');
                }
            },
            error: function (xhr) {
                if (xhr.responseJSON) {
                    const message = xhr.responseJSON.message || 'حدث خطأ أثناء المعالجة';
                    phoneInput.addClass('is-invalid');
                    phoneError.text(message);
                    toastr.error(message);
                } else {
                    toastr.error('حدث خطأ أثناء المعالجة');
                }
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});

function getRouteUrl(routeName, params = {}) {
    if (!window.routes || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }

    let url = window.routes[routeName];

    if (params.customer) url = url.replace(/CUSTOMER_ID/g, params.customer);

    if (!url.startsWith('http') && !url.startsWith('/')) url = '/' + url;
    return url;
}

function refreshPhoneRequestsList() {
    const customerId = $('input[name="customer_id"]').val();

    $.ajax({
        url: getRouteUrl('customers.phone-requests.list', {
            customer: customerId
        }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#phone-requests-list').html(response.html);

                const requestCount = $('#phone-requests-list .table tbody tr').length || 0;
                $('.badge.bg-primary.rounded-pill').text(requestCount + ' طلب');
            } else {
                toastr.error('حدث خطأ في تحديث القائمة');
                location.reload();
            }
        },
        error: function () {
            toastr.error('حدث خطأ في تحديث القائمة');
            location.reload();
        }
    });
}