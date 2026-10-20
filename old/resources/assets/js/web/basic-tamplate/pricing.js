document.addEventListener('DOMContentLoaded', function () {
    const plans = window.plansData || [];
    let isYearly = false;

    // Toggle بين الشهري والسنوي
    const durationToggler = document.querySelector('.price-duration-toggler');
    if (durationToggler) {
        durationToggler.addEventListener('change', function () {
            isYearly = this.checked;

            // تحديث العرض
            document.querySelectorAll('.price-monthly').forEach(el => {
                el.classList.toggle('d-none', isYearly);
            });
            document.querySelectorAll('.price-yearly').forEach(el => {
                el.classList.toggle('d-none', !isYearly);
            });

            // تحديث جداول النطاقات والأسعار
            plans.forEach(plan => {
                updateRangesTable(plan.id);
                const input = document.getElementById('user-count-' + plan.id);
                if (input) {
                    updatePricing(plan.id, parseInt(input.value) || 0);
                }
            });
        });
    }

    // تحديث جدول النطاقات (الأسعار ثابتة - لا تتأثر بالخصم السنوي)
    function updateRangesTable(planId) {
        const plan = plans.find(p => p.id == planId);
        if (!plan || !plan.user_ranges || plan.user_ranges.length === 0) return;

        const table = document.getElementById('ranges-table-' + planId);
        if (!table) return;

        const tbody = table.querySelector('tbody');

        plan.user_ranges.forEach(range => {
            const row = tbody.querySelector(`tr[data-range-id="${range.id}"]`);
            if (!row) return;

            const priceCell = row.querySelector('.price-cell');
            const originalPrice = parseFloat(range.price);

            // أسعار التراخيص ثابتة ولا تتأثر بالخصم
            priceCell.innerHTML = `<span class="fw-semibold">${originalPrice.toFixed(2)} <span class="icon-saudi_riyal mx-2"></span></span>`;
        });
    }

    // إيجاد النطاق المناسب
    function findPriceRange(plan, userCount) {
        // userCount هنا يمثل التراخيص الإضافيين فقط
        if (userCount <= 0) {
            return {
                price: 0,
                from: 0,
                to: 0,
                name: 'بدون ترخيصين إضافيين',
                isDefault: true
            };
        }

        if (!plan.user_ranges || plan.user_ranges.length === 0) {
            return {
                price: 0,
                from: 0,
                to: 0,
                name: 'لا توجد نطاقات',
                isDefault: true
            };
        }

        // ترتيب النطاقات حسب from
        const sortedRanges = [...plan.user_ranges].sort((a, b) => a.from - b.from);

        for (let range of sortedRanges) {
            const to = range.to || 999999; // إذا كان null يعتبر غير محدود
            if (userCount >= range.from && userCount <= to) {
                return {
                    ...range,
                    price: parseFloat(range.price),
                    to: to,
                    isDefault: false
                };
            }
        }

        // إذا لم يجد نطاق، استخدم آخر نطاق
        const lastRange = sortedRanges[sortedRanges.length - 1];
        return {
            ...lastRange,
            price: parseFloat(lastRange.price),
            to: lastRange.to || 999999,
            isDefault: false
        };
    }

    // تمييز النطاق النشط
    function highlightActiveRange(planId, userCount) {
        const table = document.getElementById('ranges-table-' + planId);
        if (!table) return;

        table.querySelectorAll('tbody tr').forEach(row => {
            row.classList.remove('range-highlight');

            if (userCount > 0) {
                const from = parseInt(row.dataset.from);
                const to = row.dataset.to ? parseInt(row.dataset.to) : 999999;

                if (userCount >= from && userCount <= to) {
                    row.classList.add('range-highlight');
                }
            }
        });
    }

    // تحديث الأسعار
    function updatePricing(planId, userCount) {
        const plan = plans.find(p => p.id == planId);
        if (!plan) return;

        // التأكد من أن userCount صحيح (التراخيص الإضافيين)
        if (userCount < 0) userCount = 0;

        const basePlanPrice = parseFloat(plan.price_monthly);
        const range = findPriceRange(plan, userCount);
        const usersMonthlyPrice = range.price * userCount; // سعر التراخيص الإضافيين
        const yearlyDiscount = parseFloat(plan.yearly_discount) || 0;

        // حساب السعر الشهري
        const monthlyTotal = basePlanPrice + usersMonthlyPrice;

        // حساب السعر السنوي (الخصم فقط على سعر الباقة الأساسية)
        const basePlanYearly = (basePlanPrice * 12) * (1 - yearlyDiscount / 100);
        const usersYearly = usersMonthlyPrice * 12; // الترخيصون الإضافيون بدون خصم
        const yearlyTotal = basePlanYearly + usersYearly;
        const yearlyMonthlyEquivalent = yearlyTotal / 12;

        // المبلغ الكلي قبل الخصم (للمقارنة)
        const yearlyTotalBeforeDiscount = monthlyTotal * 12;

        // حساب الضريبة المضافة 15%
        const vatRate = 0.15;
        const totalBeforeVat = isYearly ? yearlyTotal : monthlyTotal;
        const vatAmount = totalBeforeVat * vatRate;
        const totalWithVat = totalBeforeVat + vatAmount;

        highlightActiveRange(planId, userCount);

        // تحديث السعر في قسم الإجمالي
        const totalPriceElement = document.getElementById('total-price-' + planId);
        if (totalPriceElement) {
            totalPriceElement.textContent = (isYearly ? yearlyTotal : monthlyTotal).toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // تحديث قيمة الضريبة
        const vatAmountElement = document.getElementById('vat-amount-' + planId);
        if (vatAmountElement) {
            vatAmountElement.textContent = vatAmount.toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // تحديث الإجمالي شامل الضريبة
        const totalWithVatElement = document.getElementById('total-with-vat-' + planId);
        if (totalWithVatElement) {
            totalWithVatElement.textContent = totalWithVat.toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // السعر أعلى البطاقة
        const displayMonthly = document.getElementById('display-price-monthly-' + planId);
        const displayYearly = document.getElementById('display-price-yearly-' + planId);

        if (displayMonthly) {
            displayMonthly.textContent = monthlyTotal.toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        if (displayYearly) {
            displayYearly.textContent = yearlyMonthlyEquivalent.toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // السعر السنوي الكامل
        const yearlyTotalElement = document.getElementById('yearly-total-' + planId);
        if (yearlyTotalElement && yearlyDiscount > 0) {
            yearlyTotalElement.textContent = yearlyTotal.toLocaleString({
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + '  / سنوياً';
        }

        // تفصيل الأسعار
        const rangeInfo = document.getElementById('range-info-' + planId);
        if (rangeInfo) {
            // سعر الباقة مع أو بدون خصم
            const basePriceMonthly = basePlanPrice;
            const basePriceYearlyWithDiscount = basePlanYearly / 12;
            const basePriceDisplay = isYearly && yearlyDiscount > 0
                ? basePriceYearlyWithDiscount.toFixed(2)
                : basePriceMonthly.toFixed(2);

            // سعر التراخيص ثابت (بدون خصم)
            const userPriceOriginal = range.price;

            // حساب التوفير (فقط على سعر الباقة الأساسية)
            const savingsAmount = yearlyTotalBeforeDiscount - yearlyTotal;
            const savingsText = isYearly && yearlyDiscount > 0 ? `
                <div class="alert alert-success py-2 px-3 mb-0 mt-2">
                    <i class="ti ti-discount-check me-1"></i>
                    <strong>توفير: ${savingsAmount.toFixed(2)} <span class="icon-saudi_riyal mx-2"></span> سنوياً</strong>
                    <br><small>خصم ${yearlyDiscount}% على سعر الباقة الأساسية فقط</small>
                </div>` : '';

            if (userCount === 0) {
                // عرض الباقة الأساسية فقط
                rangeInfo.innerHTML = `
                    <div class="alert  py-2 px-3 mb-0">
                        <div class="mb-2 pb-2 border-bottom">
                            <small class="text-primary fw-semibold">
                                <i class="ti ti-user-check me-1"></i>
                                الباقة الأساسية (تشمل ترخيص واحد)
                            </small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small">سعر الباقة:</span>
                            <strong class="small">
                                ${isYearly && yearlyDiscount > 0
                        ? `<span class="text-price">${basePriceDisplay}</span> <small class="text-muted text-decoration-line-through">${basePriceMonthly.toFixed(2)}</small>`
                        : basePriceDisplay
                    } <span class="icon-saudi_riyal mx-2"></span>/شهر
                            </strong>
                        </div>
                    </div>
                    ${savingsText}
                `;
            } else {
                // عرض الباقة + التراخيص الإضافيين
                rangeInfo.innerHTML = `
                    <div class="alert alert-light py-2 px-3 mb-0">
                        <div class="mb-2 pb-2 border-bottom">
                            <small class="text-primary fw-semibold">
                                <i class="ti ti-info-circle me-1"></i>
                                نطاق: ${range.name}
                            </small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small">
                                <i class="ti ti-package me-1"></i>
                                الباقة الأساسية (مع ترخيص واحد):
                            </span>
                            <strong class="small">
                                ${isYearly && yearlyDiscount > 0
                        ? `<span class="text-price">${basePriceDisplay}</span> <small class="text-muted text-decoration-line-through">${basePriceMonthly.toFixed(2)}</small>`
                        : basePriceDisplay
                    } <span class="icon-saudi_riyal mx-2"></span>/شهر
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small">
                                <i class="ti ti-users me-1"></i>
                                ${userCount} ترخيص إضافي × ${userPriceOriginal.toFixed(2)}:
                            </span>
                            <strong class="small">${usersMonthlyPrice.toFixed(2)} <span class="icon-saudi_riyal mx-2"></span>/شهر</strong>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="fw-bold">الإجمالي ${isYearly ? 'الشهري' : ''}:</span>
                            <strong class="text-primary">${(isYearly ? yearlyMonthlyEquivalent : monthlyTotal).toFixed(2)} <span class="icon-saudi_riyal mx-2"></span></strong>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="ti ti-info-circle me-1"></i>
                            إجمالي التراخيص: ${userCount + 1} (1 أساسي + ${userCount} إضافي)
                        </small>
                        ${isYearly && yearlyDiscount > 0 ? '<small class="text-muted d-block mt-1"><i class="ti ti-discount me-1"></i>الخصم السنوي يُطبق فقط على سعر الباقة الأساسية</small>' : ''}
                    </div>
                    ${savingsText}
                `;
            }
        }

        // تحديث زر الاشتراك - حفظ البيانات في data attributes
        const subscribeBtn = document.getElementById('subscription-btn-' + planId);
        if (subscribeBtn) {
            // تخزين البيانات في data attributes للاستخدام عند الضغط
            subscribeBtn.setAttribute('data-users', userCount);
            subscribeBtn.setAttribute('data-billing', isYearly ? 'yearly' : 'monthly');
        }
    }

    // ربط حقل عدد التراخيص
    document.querySelectorAll('.user-count-input').forEach(input => {
        input.addEventListener('input', function () {
            const planId = this.dataset.planId;
            let userCount = parseInt(this.value);

            // السماح بـ 0 أو أكثر (التراخيص الإضافيين)
            if (isNaN(userCount) || userCount < 0) {
                userCount = 0;
                this.value = 0;
            }

            updatePricing(planId, userCount);
        });

        // تهيئة أولية - البداية بـ 0 ترخيص إضافي
        const planId = input.dataset.planId;
        updateRangesTable(planId);
        updatePricing(planId, 0);
    });

    // ============================================
    // إضافة Event Listener لأزرار الاشتراك
    // ============================================
    document.querySelectorAll('.subscription-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const planId = this.getAttribute('data-plan-id');
            const users = this.getAttribute('data-users') || 0;
            const billing = this.getAttribute('data-billing') || 'monthly';
            const actionUrl = this.getAttribute('href');

            // التحقق من القيم
            if (!planId || !actionUrl) {
                console.error('Missing required data');
                return;
            }

            // إنشاء form ديناميكي
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            form.style.display = 'none';

            // إضافة CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }

            // إضافة عدد التراخيص
            const usersInput = document.createElement('input');
            usersInput.type = 'hidden';
            usersInput.name = 'users';
            usersInput.value = users;
            form.appendChild(usersInput);

            // إضافة نوع الفوترة
            const billingInput = document.createElement('input');
            billingInput.type = 'hidden';
            billingInput.name = 'billing';
            billingInput.value = billing;
            form.appendChild(billingInput);

            // إضافة الـ form للصفحة وإرساله
            document.body.appendChild(form);
            form.submit();
        });
    });
});
