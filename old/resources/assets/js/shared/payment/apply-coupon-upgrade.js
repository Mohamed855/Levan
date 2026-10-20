document.addEventListener('DOMContentLoaded', function () {
    // ✅ استخدام البيانات من window.paymentData
    const originalSubtotal = parseFloat(window.paymentData.subtotal);
    const originalVat = parseFloat(window.paymentData.vatValue);
    const originalTotal = parseFloat(window.paymentData.total);
    const newLicenses = parseFloat(window.paymentData.newLicenses);
    const pricePerUser = parseFloat(window.paymentData.pricePerUser);

    // حساب basePrice و usersPrice
    const usersPrice = originalSubtotal;
    const basePrice = 0; // لا يوجد سعر باقة في الترقية

    // العناصر
    const form = document.getElementById('payment-form');
    const expDateDisplay = document.getElementById('exp_date_display');
    const cardNumberInput = document.getElementById('billings-card-num');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const couponCodeInput = document.getElementById('coupon_code_input');
    const couponMessage = document.getElementById('coupon-message');
    const discountRow = document.getElementById('discount-row');
    const afterDiscountRow = document.getElementById('after-discount-row');
    const discountDisplay = document.getElementById('discount-display');
    const afterDiscountDisplay = document.getElementById('after-discount-display');
    const removeCouponBtn = document.getElementById('remove-coupon');

    let appliedCoupon = null;

    function formatNumber(num) {
        const value = parseFloat(num);
        if (isNaN(value)) return '0.00';
        return value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // تطبيق الكوبون
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', function () {
            const couponCode = couponCodeInput.value.trim();

            if (!couponCode) {
                showCouponMessage('الرجاء إدخال كود الخصم', 'danger');
                return;
            }

            applyCouponBtn.disabled = true;
            applyCouponBtn.innerHTML =
                ' جاري التحقق...';

            const requestData = {
                coupon_code: couponCode,
                subtotal: originalSubtotal,
                base_price: basePrice,
                users_price: usersPrice
            };

            fetch('/api/central/coupon/validate-coupon', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            })
                .then(response => {
                    return response.json().then(data => ({
                        ok: response.ok,
                        status: response.status,
                        data: data
                    }));
                })
                .then(result => {
                    if (result.ok && result.data.success) {
                        appliedCoupon = result.data;
                        updatePrices(result.data);
                        showCouponMessage(result.data.message, 'success');

                        document.getElementById('coupon_code_hidden').value = result.data.coupon
                            .code;
                        document.getElementById('discount_amount_hidden').value = result.data
                            .raw_discount;

                        applyCouponBtn.classList.add('d-none');
                        couponCodeInput.disabled = true;
                    } else {
                        const errorMessage = result.data.message || 'كود الخصم غير صحيح';
                        showCouponMessage(errorMessage, 'danger');

                        if (result.data.errors) {
                            console.error('❌ Validation errors:', result.data.errors);
                        }
                    }
                })
                .catch(error => {
                    showCouponMessage('حدث خطأ أثناء التحقق من الكوبون', 'danger');
                })
                .finally(() => {
                    applyCouponBtn.disabled = false;
                    applyCouponBtn.innerHTML = '<i class="ti ti-discount-2 me-1"></i> تطبيق';
                });
        });
    }

    // إزالة الكوبون
    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', function () {
            discountRow.classList.add('d-none');
            afterDiscountRow.classList.add('d-none');

            document.getElementById('subtotal-display').innerHTML =
                formatNumber(originalSubtotal) + ' <span class="icon-saudi_riyal mx-2"></span>';
            document.getElementById('vat-display').innerHTML =
                formatNumber(originalVat) + ' <span class="icon-saudi_riyal mx-2"></span>';
            document.getElementById('total-final').innerHTML =
                formatNumber(originalTotal) + ' <span class="icon-saudi_riyal mx-2"></span>';

            couponMessage.innerHTML = '';
            applyCouponBtn.classList.remove('d-none');
            couponCodeInput.disabled = false;
            couponCodeInput.value = '';

            document.getElementById('coupon_code_hidden').value = '';
            document.getElementById('discount_amount_hidden').value = '';

            appliedCoupon = null;
        });
    }

    // تحديث الأسعار
    function updatePrices(data) {
        const discount = parseFloat(data.raw_discount) || 0;
        const newSubtotal = parseFloat(data.raw_new_subtotal) || 0;
        const vat = parseFloat(data.raw_vat) || 0;
        const totalWithVat = parseFloat(data.raw_total_with_vat) || 0;

        discountDisplay.textContent = formatNumber(discount);
        discountRow.classList.remove('d-none');

        afterDiscountDisplay.innerHTML =
            formatNumber(newSubtotal) + ' <span class="icon-saudi_riyal mx-2"></span>';
        afterDiscountRow.classList.remove('d-none');

        document.getElementById('vat-display').innerHTML =
            formatNumber(vat) + ' <span class="icon-saudi_riyal mx-2"></span>';
        document.getElementById('total-final').innerHTML =
            formatNumber(totalWithVat) + ' <span class="icon-saudi_riyal mx-2"></span>';
    }

    // رسالة حالة الكوبون
    function showCouponMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'ti-check' : 'ti-alert-circle';

        couponMessage.innerHTML = `
                    <div class="alert ${alertClass} py-2 px-3 mb-0">
                        <small><i class="ti ${icon} me-1"></i>${message}</small>
                    </div>
                `;

        if (type === 'danger') {
            setTimeout(() => couponMessage.innerHTML = '', 5000);
        }
    }

    // تنسيق حقول البطاقة
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\s/g, '');
            e.target.value = value.match(/.{1,4}/g)?.join(' ') || value;
        });
    }

    if (expDateDisplay) {
        expDateDisplay.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }

            e.target.value = value;
        });
    }

    const cvcInput = document.getElementById('cvc');
    if (cvcInput) {
        cvcInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }

    const cardNameInput = document.getElementById('card_name_display');
    if (cardNameInput) {
        cardNameInput.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
        });
    }

    // التحقق قبل الإرسال
    if (form) {
        form.addEventListener('submit', function (e) {
            if (cardNumberInput) {
                cardNumberInput.value = cardNumberInput.value.replace(/\s/g, '');
            }
        });
    }
});
