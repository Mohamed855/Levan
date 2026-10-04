'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[data-form-validate]');
  if (!form) return;

  // ==================== SELECT2 INITIALIZATION ====================
  if (window.jQuery) {
    const $ = window.jQuery;
    $(form).find('.select2').each(function () {
      const $this = $(this);
      if ($this.hasClass('select2-hidden-accessible')) return;

      $this.wrap('<div class="position-relative"></div>');
      $this.select2({
        placeholder: $this.data('placeholder') || 'اختر خيارًا',
        dropdownParent: $this.parent(),
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
      }).on('change', function () {
        this.dispatchEvent(new Event('change', { bubbles: true }));
        this.dispatchEvent(new Event('input', { bubbles: true }));
      });
    });
  }

  // ==================== CODE UPPERCASE FORMATTING ====================
  const codeInput = document.getElementById('code');
  if (codeInput) {
    codeInput.addEventListener('input', function () {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
      if (start !== null && end !== null) {
        this.setSelectionRange(start, end);
      }
    });
  }

  // ==================== LIMITS MODE TOGGLE ====================
  const limitsModeSelect = document.getElementById('limits_mode');
  const limitsFields = document.querySelectorAll('.limits-field');
  const unlimitedAlert = document.getElementById('unlimited-mode-alert');

  function toggleLimitsMode() {
    if (!limitsModeSelect) return;
    const isUnlimited = limitsModeSelect.value === 'unlimited';

    limitsFields.forEach((el) => {
      el.style.display = isUnlimited ? 'none' : 'block';
      if (isUnlimited) {
        const input = el.querySelector('input, select');
        if (input && window.SharedFormValidation?.clearFieldError) {
          window.SharedFormValidation.clearFieldError(form, input.name);
        }
      }
    });

    if (unlimitedAlert) {
      unlimitedAlert.style.display = isUnlimited ? 'block' : 'none';
    }

    const step = limitsModeSelect.closest('[data-form-step], .content');
    if (step && window.SharedFormValidation?.updateStepNextButton) {
      window.SharedFormValidation.updateStepNextButton(form, step);
    }
  }

  if (limitsModeSelect) {
    limitsModeSelect.addEventListener('change', toggleLimitsMode);
    if (window.jQuery) {
      window.jQuery(limitsModeSelect).on('change', toggleLimitsMode);
    }
    toggleLimitsMode();
  }

  // ==================== BRANCHES LIMIT TOGGLE ====================
  const unlimitedBranchesSelect = document.getElementById('unlimited_branches');
  const branchesLimitContainer = document.getElementById('branches_limit_container');

  function toggleBranchesLimit() {
    if (!unlimitedBranchesSelect || !branchesLimitContainer) return;
    const isUnlimited = unlimitedBranchesSelect.value === '1';
    branchesLimitContainer.style.display = isUnlimited ? 'none' : 'block';

    if (isUnlimited) {
      const input = branchesLimitContainer.querySelector('input');
      if (input && window.SharedFormValidation?.clearFieldError) {
        window.SharedFormValidation.clearFieldError(form, input.name);
      }
    }

    const step = unlimitedBranchesSelect.closest('[data-form-step], .content');
    if (step && window.SharedFormValidation?.updateStepNextButton) {
      window.SharedFormValidation.updateStepNextButton(form, step);
    }
  }

  if (unlimitedBranchesSelect) {
    unlimitedBranchesSelect.addEventListener('change', toggleBranchesLimit);
    if (window.jQuery) {
      window.jQuery(unlimitedBranchesSelect).on('change', toggleBranchesLimit);
    }
    toggleBranchesLimit();
  }

  // ==================== FEATURES MANAGEMENT ====================
  const featuresList = document.getElementById('features-list');
  const addFeatureBtn = document.getElementById('btn-add-feature');
  let featureIndex = featuresList ? featuresList.querySelectorAll('.feature-item').length : 0;

  function updateFeaturesStepButton() {
    const step = document.getElementById('features-validation');
    if (step && window.SharedFormValidation?.updateStepNextButton) {
      window.SharedFormValidation.updateStepNextButton(form, step);
    }
  }

  function addFeature(value = '') {
    const template = document.getElementById('feature-template');
    if (!template || !featuresList) return;

    const clone = template.content.cloneNode(true);
    const item = clone.querySelector('.feature-item');
    const input = item.querySelector('input[name*="features"]');
    input.name = `features[${featureIndex}]`;
    input.value = value;

    featuresList.appendChild(item);
    featureIndex++;

    input.addEventListener('input', () => {
      updateFeaturesStepButton();
    });

    updateFeaturesStepButton();
    input.focus();
  }

  if (addFeatureBtn && featuresList) {
    addFeatureBtn.addEventListener('click', () => addFeature());

    featuresList.addEventListener('click', function (e) {
      const removeBtn = e.target.closest('.btn-remove-feature');
      if (!removeBtn) return;

      const item = removeBtn.closest('.feature-item');
      if (item) {
        item.remove();
        if (featuresList.querySelectorAll('.feature-item').length === 0) {
          addFeature();
        } else {
          updateFeaturesStepButton();
        }
      }
    });

    featuresList.querySelectorAll('input[name*="features"]').forEach((input) => {
      input.addEventListener('input', () => {
        updateFeaturesStepButton();
      });
    });

    if (featuresList.querySelectorAll('.feature-item').length === 0) {
      addFeature();
    }
  }

  // ==================== LIVE PREVIEW IN STEP 3 ====================
  function updateReviewCard() {
    const name = document.getElementById('name')?.value || '-';
    const code = document.getElementById('code')?.value || '-';

    const planTypeSelect = document.getElementById('plan_type');
    const planTypeLabel = planTypeSelect && planTypeSelect.options[planTypeSelect.selectedIndex]
      ? planTypeSelect.options[planTypeSelect.selectedIndex].text
      : '-';

    const billingCycleSelect = document.getElementById('subscription_billing_cycle');
    const billingCycleLabel = billingCycleSelect && billingCycleSelect.options[billingCycleSelect.selectedIndex]
      ? billingCycleSelect.options[billingCycleSelect.selectedIndex].text
      : '-';

    const limitsModeVal = document.getElementById('limits_mode')?.value || 'limited';
    const isUnlimited = limitsModeVal === 'unlimited';

    const price = parseFloat(document.getElementById('price')?.value || '0');
    const discount = parseFloat(document.getElementById('discount_percentage')?.value || '0');
    const finalPrice = discount > 0 ? price - (price * discount) / 100 : price;

    const yearlyRenewal = document.getElementById('yearly_renewal_price')?.value;
    const annualOfferText = yearlyRenewal && parseFloat(yearlyRenewal) > 0 ? `${parseFloat(yearlyRenewal).toFixed(2)} ر.س` : 'غير متوفر';

    const includedUsers = document.getElementById('included_users')?.value || '-';
    const propertiesLimit = document.getElementById('properties_limit')?.value || '-';
    const storageQuota = document.getElementById('storage_quota_gb')?.value || '-';
    const auctionsLimit = document.getElementById('active_auctions_limit')?.value || '-';

    const unlimitedBranchesVal = document.getElementById('unlimited_branches')?.value;
    const branchesLimit = document.getElementById('branches_limit')?.value || '1';
    const branchesText = isUnlimited || unlimitedBranchesVal === '1' ? '∞ غير محدود' : `${branchesLimit} فرع`;

    const domainSelect = document.getElementById('domain_management_enabled');
    const domainsText = domainSelect?.value === '1' ? 'مفعّلة' : 'غير مفعّلة';

    const availableFrom = document.getElementById('available_from')?.value;
    const availableText = availableFrom ? availableFrom : 'متاحة فوراً';

    // Populate DOM
    const nameEl = document.getElementById('review-plan-name');
    if (nameEl) nameEl.textContent = name;

    const codeEl = document.getElementById('review-plan-code');
    if (codeEl) codeEl.textContent = code;

    const typeEl = document.getElementById('review-plan-type');
    if (typeEl) typeEl.textContent = planTypeLabel;

    const cycleEl = document.getElementById('review-billing-cycle');
    if (cycleEl) cycleEl.textContent = `دورة: ${billingCycleLabel}`;

    const limitsModeEl = document.getElementById('review-limits-mode');
    if (limitsModeEl) limitsModeEl.textContent = isUnlimited ? 'غير محدود (∞)' : 'محدود';

    const finalPriceEl = document.getElementById('review-final-price');
    if (finalPriceEl) finalPriceEl.textContent = `${finalPrice.toFixed(2)} ر.س`;

    const priceBreakdownEl = document.getElementById('review-price-breakdown');
    if (priceBreakdownEl) {
      priceBreakdownEl.textContent = discount > 0
        ? `السعر الأساسي: ${price.toFixed(2)} ر.س (خصم ${discount}%)`
        : `السعر الأساسي: ${price.toFixed(2)} ر.س`;
    }

    const annualOfferEl = document.getElementById('review-annual-offer');
    if (annualOfferEl) annualOfferEl.textContent = annualOfferText;

    const usersEl = document.getElementById('review-users');
    if (usersEl) usersEl.textContent = isUnlimited ? '∞ غير محدود' : `${includedUsers} مستخدم`;

    const propsEl = document.getElementById('review-properties');
    if (propsEl) propsEl.textContent = isUnlimited ? '∞ غير محدود' : `${propertiesLimit} عقار`;

    const storageEl = document.getElementById('review-storage');
    if (storageEl) storageEl.textContent = isUnlimited ? '∞ غير محدود' : `${storageQuota} GB`;

    const auctionsEl = document.getElementById('review-auctions');
    if (auctionsEl) auctionsEl.textContent = isUnlimited ? '∞ غير محدود' : `${auctionsLimit} مزاد`;

    const branchesEl = document.getElementById('review-branches');
    if (branchesEl) branchesEl.textContent = branchesText;

    const domainsEl = document.getElementById('review-domains');
    if (domainsEl) domainsEl.textContent = domainsText;

    const availableFromEl = document.getElementById('review-available-from');
    if (availableFromEl) availableFromEl.textContent = availableText;

    // Features list
    const reviewFeaturesUl = document.getElementById('review-features-list');
    if (reviewFeaturesUl && featuresList) {
      reviewFeaturesUl.innerHTML = '';
      featuresList.querySelectorAll('input[name*="features"]').forEach((input) => {
        if (input.value.trim()) {
          const li = document.createElement('li');
          li.textContent = input.value.trim();
          reviewFeaturesUl.appendChild(li);
        }
      });
    }

    // Publication Alert
    const isActive = document.getElementById('is_active')?.value === '1';
    const alertBox = document.getElementById('publish-status-alert');
    const alertText = document.getElementById('publish-status-text');
    const alertIcon = document.getElementById('publish-status-icon');

    if (alertBox && alertText && alertIcon) {
      if (!isActive) {
        alertBox.className = 'alert alert-secondary d-flex align-items-center mb-0';
        alertIcon.className = 'ti ti-eye-off fs-3 me-2';
        alertText.textContent = 'الباقة غير مفعلة ولن تظهر للعملاء في صفحات التسجيل أو الـ Checkout حتى يتم تفعيلها.';
      } else if (availableFrom && new Date(availableFrom) > new Date()) {
        alertBox.className = 'alert alert-info d-flex align-items-center mb-0';
        alertIcon.className = 'ti ti-clock fs-3 me-2';
        alertText.textContent = `الباقة مفعلة ومجدولة للإتاحة تلقائياً في تاريخ: ${availableFrom}. لن تظهر قبل هذا التاريخ.`;
      } else {
        alertBox.className = 'alert alert-success d-flex align-items-center mb-0';
        alertIcon.className = 'ti ti-check-circle fs-3 me-2';
        alertText.textContent = 'الباقة نشطة ومتاحة الآن، ستظهر للعملاء في قوائم الاختيار والـ Checkout فور النشر.';
      }
    }
  }

  window.updateReviewCard = updateReviewCard;

  form.addEventListener('stepChanged', function (e) {
    if (e.detail?.stepIndex === 2) {
      updateReviewCard();
    }
  });

  form.addEventListener('input', function () {
    if (document.getElementById('review-validation')?.classList.contains('active')) {
      updateReviewCard();
    }
  });
});
