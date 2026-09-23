document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('payment-form');
    const expDateDisplay = document.getElementById('exp_date_display');
    const cardNumberInput = document.getElementById('billings-card-num');

    // عناصر الكوبون
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const couponCodeInput = document.getElementById('coupon_code_input');
    const couponMessage = document.getElementById('coupon-message');
    const discountRow = document.getElementById('discount-row');
    const afterDiscountRow = document.getElementById('after-discount-row');
    const discountDisplay = document.getElementById('discount-display');
    const afterDiscountDisplay = document.getElementById('after-discount-display');
    const removeCouponBtn = document.getElementById('remove-coupon');

    // عناصر عرض الإجمالي
    const totalFinal = document.getElementById('total-final');

    // القيم الأصلية من السيرفر (نعمل فقط على الإجمالي)
    const originalTotal = parseFloat(window.paymentData.total);

    let appliedCoupon = null;

    // تنسيق الأرقام
    function formatNumber(num) {
        const value = parseFloat(num);
        if (isNaN(value)) {
            return '0.00';
        }

        return value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // تطبيق الكوبون
    applyCouponBtn.addEventListener('click', function () {
        const couponCode = couponCodeInput.value.trim();

        if (!couponCode) {
            showCouponMessage('الرجاء إدخال كود الخصم', 'danger');
            return;
        }

        applyCouponBtn.disabled = true;
        applyCouponBtn.innerHTML =
            '<span class="small">جاري التحقق...</span>';

        fetch('/api/central/coupon/validate-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                total: originalTotal,
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    appliedCoupon = data;
                    updatePrices(data);

                    showCouponMessage(data.message, 'success');

                    // حفظ البيانات في الحقول المخفية
                    document.getElementById('coupon_code_hidden').value = data.coupon.code;

                    // إخفاء زر التطبيق وتعطيل الحقل
                    applyCouponBtn.classList.add('hidden');
                    couponCodeInput.disabled = true;
                } else {
                    showCouponMessage(data.message || 'الكوبون غير صالح', 'danger');
                }
            })
            .catch(error => {
                console.log(error);
                showCouponMessage('حدث خطأ أثناء التحقق من الكوبون', 'danger');
            })
            .finally(() => {
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerHTML = '<i class="ti ti-discount-2 me-1"></i> تطبيق';
            });
    });

    // إزالة الكوبون
    removeCouponBtn.addEventListener('click', function () {
        // إخفاء صفوف الخصم
        discountRow.classList.add('hidden');


        if (totalFinal) {
            totalFinal.innerHTML =
                formatNumber(originalTotal) + ' <span class="icon-saudi_riyal mx-1 text-xs"></span>';
        }

        // مسح الرسائل
        couponMessage.innerHTML = '';

        // إظهار زر التطبيق وتفعيل الحقل
        applyCouponBtn.classList.remove('hidden');
        couponCodeInput.disabled = false;
        couponCodeInput.value = '';

        // مسح البيانات المخفية
        document.getElementById('coupon_code_hidden').value = '';

        appliedCoupon = null;
    });

    // تحديث الأسعار بعد تطبيق الكوبون (على الإجمالي فقط)
    function updatePrices(data) {

        const discount = parseFloat(data.raw_discount) || 0;
        const totalAfterDiscount = parseFloat(data.raw_total_after_discount) || 0;

        if (isNaN(discount) || isNaN(totalAfterDiscount)) {
            showCouponMessage('حدث خطأ في حساب الخصم', 'danger');
            return;
        }

        // عرض قيمة الخصم
        discountDisplay.textContent = formatNumber(discount);
        discountRow.classList.remove('hidden');

        // تحديث الإجمالي النهائي في أعلى/أسفل الصفحة
        if (totalFinal) {
            totalFinal.innerHTML =
                formatNumber(totalAfterDiscount) + ' <span class="icon-saudi_riyal mx-1 text-xs"></span>';
        }


    }

    // رسالة حالة الكوبون
    function showCouponMessage(message, type) {
        const isSuccess = type === 'success';
        const colorClass = isSuccess
            ? 'bg-green-50 border-green-200 text-green-700'
            : 'bg-red-50 border-red-200 text-red-700';

        couponMessage.innerHTML = `
        <div class="${colorClass} flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-semibold ">
           ${message}
        </div>
    `;

        // إخفاء رسائل الخطأ بعد 5 ثواني
        if (type === 'danger') {
            setTimeout(() => {
                couponMessage.innerHTML = '';
            }, 5000);
        }
    }

    // تجهيز بيانات البطاقة قبل الإرسال
    if (form) {
        form.addEventListener('submit', function () {
            // إزالة المسافات من رقم البطاقة
            if (cardNumberInput && cardNumberInput.value) {
                const cardNumber = cardNumberInput.value.replace(/\s/g, '');
                cardNumberInput.value = cardNumber;
            }
        });
    }

    // تنسيق رقم البطاقة
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }

    // تنسيق تاريخ الانتهاء
    if (expDateDisplay) {
        expDateDisplay.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }

            e.target.value = value;
        });
    }
});
