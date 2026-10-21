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

    if (window.paymentData && window.paymentData.appliedCoupon) {
        const pre = window.paymentData.appliedCoupon;
        appliedCoupon = {
            coupon: { code: pre.coupon_code },
            raw_discount: pre.discount_amount,
            raw_total_after_discount: pre.final_total,
        };
        const hiddenCode = document.getElementById('coupon_code_hidden');
        if (hiddenCode) {
            hiddenCode.value = pre.coupon_code;
        }
        if (couponCodeInput) {
            couponCodeInput.value = pre.coupon_code;
            couponCodeInput.disabled = true;
        }
        if (applyCouponBtn) {
            applyCouponBtn.classList.add('hidden');
        }
        if (discountRow) {
            discountRow.classList.remove('hidden');
        }
    }

    // تطبيق الكوبون
    applyCouponBtn?.addEventListener('click', function () {
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
                    const hiddenCoupon = document.getElementById('coupon_code_hidden');
                    if (hiddenCoupon) {
                        hiddenCoupon.value = data.coupon.code;
                    }

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
    removeCouponBtn?.addEventListener('click', function () {
        // إشعار السيرفر بإزالة الكوبون من الجلسة
        fetch('/api/central/coupon/remove-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        }).catch(() => {});

        // إخفاء صفوف الخصم
        discountRow?.classList.add('hidden');

        if (totalFinal) {
            totalFinal.innerHTML =
                formatNumber(originalTotal) + ' <span class="icon-saudi_riyal mx-1 text-xs"></span>';
        }

        const taxRate = 0.15;
        const vat = Math.round(originalTotal * taxRate * 100) / 100;
        const subtotal = Math.round((originalTotal - vat) * 100) / 100;

        const subtotalDisplay = document.getElementById('subtotal-display');
        if (subtotalDisplay) {
            subtotalDisplay.innerHTML = formatNumber(subtotal) + ' <span class="icon-saudi_riyal"></span>';
        }
        const vatDisplay = document.getElementById('vat-display');
        if (vatDisplay) {
            vatDisplay.innerHTML = formatNumber(vat) + ' <span class="icon-saudi_riyal"></span>';
        }

        // مسح الرسائل
        if (couponMessage) {
            couponMessage.innerHTML = '';
        }

        // إظهار زر التطبيق وتفعيل الحقل
        applyCouponBtn?.classList.remove('hidden');
        if (couponCodeInput) {
            couponCodeInput.disabled = false;
            couponCodeInput.value = '';
        }

        // مسح البيانات المخفية
        const hiddenCoupon = document.getElementById('coupon_code_hidden');
        if (hiddenCoupon) {
            hiddenCoupon.value = '';
        }

        appliedCoupon = null;
    });

    // تحديث الأسعار بعد تطبيق الكوبون
    function updatePrices(data) {
        const discount = parseFloat(data.raw_discount) || 0;
        const totalAfterDiscount = parseFloat(data.raw_total_after_discount) || 0;

        if (isNaN(discount) || isNaN(totalAfterDiscount)) {
            showCouponMessage('حدث خطأ في حساب الخصم', 'danger');
            return;
        }

        // عرض قيمة الخصم
        if (discountDisplay) {
            discountDisplay.textContent = formatNumber(discount);
        }
        discountRow?.classList.remove('hidden');

        // تحديث الإجمالي النهائي
        if (totalFinal) {
            totalFinal.innerHTML =
                formatNumber(totalAfterDiscount) + ' <span class="icon-saudi_riyal mx-1 text-xs"></span>';
        }

        // تحديث الضريبة والمبلغ قبل الضريبة
        const taxRate = 0.15;
        const vat = Math.round(totalAfterDiscount * taxRate * 100) / 100;
        const subtotal = Math.round((totalAfterDiscount - vat) * 100) / 100;

        const subtotalDisplay = document.getElementById('subtotal-display');
        if (subtotalDisplay) {
            subtotalDisplay.innerHTML = formatNumber(subtotal) + ' <span class="icon-saudi_riyal"></span>';
        }
        const vatDisplay = document.getElementById('vat-display');
        if (vatDisplay) {
            vatDisplay.innerHTML = formatNumber(vat) + ' <span class="icon-saudi_riyal"></span>';
        }
    }

    // رسالة حالة الكوبون
    function showCouponMessage(message, type) {
        const isSuccess = type === 'success';
        const colorClass = isSuccess
            ? 'site-alert site-alert-success'
            : 'site-alert site-alert-danger';

        couponMessage.innerHTML = `
        <div class="${colorClass} d-flex align-items-center gap-2">
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
