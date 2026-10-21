'use strict';

function initPlanFormUI() {
  const form = document.querySelector('form[data-form-validate]');
  if (!form) return;

  const getInput = (id) => document.getElementById(id) || form.querySelector(`[name="${id}"]`);
  const getValue = (id, fallback = '') => getInput(id)?.value?.trim() || fallback;
  const getSelectLabel = (id, fallback = '') => {
    const select = getInput(id) || form.querySelector(`select[name="${id}"]`);
    if (!select) return fallback;
    const option = select.options?.[select.selectedIndex];
    const text = option?.text?.trim();
    return text || select.dataset.planLabel || fallback;
  };
  const setText = (card, field, value) => {
    card.querySelectorAll(`[data-plan-field="${field}"]`).forEach((element) => {
      element.textContent = value;
    });
  };
  const planColors = {
    purple: '#1D4F44',
    blue: '#00a8c0',
    green: '#218c5b',
    orange: '#d97706',
    red: '#c2414b',
    gray: '#64748b'
  };
  const formatPlanColor = (option) => {
    if (!option.id || !planColors[option.id]) return option.text;

    const wrapper = document.createElement('span');
    const swatch = document.createElement('span');
    const label = document.createElement('span');
    wrapper.className = 'plan-color-option';
    swatch.className = 'plan-color-option__swatch';
    swatch.style.backgroundColor = planColors[option.id];
    swatch.setAttribute('aria-hidden', 'true');
    label.textContent = option.text;
    wrapper.append(swatch, label);
    return wrapper;
  };

  if (window.jQuery) {
    const $ = window.jQuery;

    // Listen to changes on all selects via jQuery (catches Select2 selection events)
    $(form).on('change.planPreview select2:select select2:clear', 'select', function () {
      if (this.id === 'limits_mode') {
        toggleLimitsMode();
      }
      updatePackageCards();
    });

    $(form).find('.select2').each(function () {
      const $select = $(this);
      const isColor = this.id === 'color';

      if ($select.hasClass('select2-hidden-accessible')) {
        if (isColor) {
          $select.select2('destroy');
        } else {
          $select.on('change select2:select select2:clear', updatePackageCards);
          return;
        }
      }

      $select.wrap('<div class="position-relative"></div>');
      const select2Options = {
        placeholder: $select.data('placeholder') || 'اختر خيارًا',
        dropdownParent: $select.parent(),
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
      };

      if (isColor) {
        select2Options.templateResult = formatPlanColor;
        select2Options.templateSelection = formatPlanColor;
      }

      $select.select2(select2Options).on('change select2:select select2:clear', function () {
        this.dispatchEvent(new Event('input', { bubbles: true }));
        this.dispatchEvent(new Event('change', { bubbles: true }));
        updatePackageCards();
      });
    });
  }

  const codeInput = getInput('code');
  if (codeInput && !codeInput.readOnly) {
    codeInput.addEventListener('input', function () {
      const start = this.selectionStart;
      const end = this.selectionEnd;
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
      if (start !== null && end !== null) this.setSelectionRange(start, end);
    });
  }

  const limitsModeSelect = getInput('limits_mode');
  const unlimitedAlert = getInput('unlimited-mode-alert');

  function refreshStepNextButton(control) {
    const step = control?.closest('[data-form-step], .content');
    if (step && window.SharedFormValidation?.updateStepNextButton) {
      window.SharedFormValidation.updateStepNextButton(form, step);
    }
  }

  function toggleLimitsMode() {
    if (!limitsModeSelect) return;
    const isUnlimited = limitsModeSelect.value === 'unlimited';

    document.querySelectorAll('.limits-field').forEach((element) => {
      element.hidden = isUnlimited;
      if (isUnlimited) {
        element.querySelectorAll('[name]').forEach((control) => {
          window.SharedFormValidation?.clearFieldError?.(form, control.name);
        });
      }
    });

    if (unlimitedAlert) unlimitedAlert.hidden = !isUnlimited;
    refreshStepNextButton(limitsModeSelect);
    updatePackageCards();
  }

  if (limitsModeSelect) {
    limitsModeSelect.addEventListener('change', toggleLimitsMode);
  }

  const featuresList = getInput('features-list');
  const addFeatureButton = getInput('btn-add-feature');
  let featureIndex = Array.from(featuresList?.querySelectorAll('input[name^="features["]') ?? []).reduce((nextIndex, input) => {
    const match = input.name.match(/^features\[(\d+)]$/);
    return Math.max(nextIndex, match ? Number(match[1]) + 1 : nextIndex);
  }, 0);

  function updateFeaturesStepButton() {
    refreshStepNextButton(featuresList);
    updatePackageCards();
  }

  function syncFeatureRemovalButtons() {
    if (!featuresList) return;
    const canRemove = featuresList.querySelectorAll('.feature-item').length > 1;
    featuresList.querySelectorAll('.btn-remove-feature').forEach((button) => {
      button.hidden = !canRemove;
    });
  }

  function addFeature(value = '', shouldFocus = true) {
    const template = getInput('feature-template');
    if (!template || !featuresList) return;

    const clone = template.content.cloneNode(true);
    const item = clone.querySelector('.feature-item');
    const input = item.querySelector('input[name*="features"]');
    input.name = `features[${featureIndex}]`;
    input.value = value;
    featuresList.appendChild(item);
    featureIndex += 1;
    syncFeatureRemovalButtons();
    if (shouldFocus) input.focus();
    updateFeaturesStepButton();
  }

  function restoreDraftFeatures(features) {
    if (!featuresList) return;

    const restoredFeatures = Array.isArray(features)
      ? features.filter((feature) => typeof feature === 'string' && feature.trim() !== '')
      : [];

    featuresList.replaceChildren();
    featureIndex = 0;
    restoredFeatures.forEach((feature) => addFeature(feature, false));
    if (!restoredFeatures.length) addFeature('', false);
    syncFeatureRemovalButtons();
  }

  if (addFeatureButton && featuresList) {
    addFeatureButton.addEventListener('click', () => addFeature());
    featuresList.addEventListener('input', updateFeaturesStepButton);
    featuresList.addEventListener('click', (event) => {
      const removeButton = event.target.closest('.btn-remove-feature');
      if (!removeButton) return;

      if (featuresList.querySelectorAll('.feature-item').length <= 1) return;
      removeButton.closest('.feature-item')?.remove();
      syncFeatureRemovalButtons();
      updateFeaturesStepButton();
    });

    if (!featuresList.querySelector('.feature-item')) addFeature('', false);
    else syncFeatureRemovalButtons();
  }

  form.addEventListener('wizardDraftRestored', (event) => {
    if (Object.prototype.hasOwnProperty.call(event.detail?.data || {}, 'features')) {
      restoreDraftFeatures(event.detail.data.features);
    }
    updatePackageCards();
  });

  function renderFeatures(card) {
    const list = card.querySelector('[data-plan-features]');
    if (!list) return;

    const features = Array.from(featuresList?.querySelectorAll('input[name*="features"]') ?? [])
      .map((input) => input.value.trim())
      .filter(Boolean);
    list.replaceChildren();

    if (!features.length) {
      const item = document.createElement('li');
      item.className = 'package-card__empty-feature';
      item.textContent = 'أضف ميزات الباقة لتظهر هنا.';
      list.appendChild(item);
      return;
    }

    features.forEach((feature) => {
      const item = document.createElement('li');
      const icon = document.createElement('i');
      const text = document.createElement('span');
      icon.className = 'ti ti-circle-check';
      icon.setAttribute('aria-hidden', 'true');
      text.textContent = feature;
      item.append(icon, text);
      list.appendChild(item);
    });
  }

  function updatePlanReviewCards() {
    const price = Number.parseFloat(getValue('price', '0')) || 0;
    const discount = Math.min(100, Math.max(0, Number.parseFloat(getValue('discount_percentage', '0')) || 0));
    const finalPrice = Math.max(0, price - (price * discount / 100));
    const renewalPrice = Number.parseFloat(getValue('yearly_renewal_price', '0')) || 0;
    const extraPrice = Number.parseFloat(getValue('extra_user_license_price', '0')) || 0;
    const isUnlimited = getValue('limits_mode', 'limited') === 'unlimited';
    const formatPrice = (value) => value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const limitValue = (id, suffix) => isUnlimited ? 'غير محدود' : `${getValue(id, '—')} ${suffix}`;

    const setReview = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val || '—';
    };

    setReview('plan-review-name', getValue('name'));
    setReview('plan-review-code', getValue('code'));
    setReview('plan-review-type', getSelectLabel('plan_type'));
    setReview('plan-review-color', getSelectLabel('color'));
    setReview('plan-review-status', getSelectLabel('is_active', getValue('is_active') === '1' ? 'مفعل' : 'معطل'));
    setReview('plan-review-available-from', getValue('available_from') || 'متاح فوراً');
    setReview('plan-review-description', getValue('description'));

    const featuresEl = document.getElementById('plan-review-features');
    if (featuresEl) {
      const features = Array.from(featuresList?.querySelectorAll('input[name*="features"]') ?? [])
        .map((input) => input.value.trim())
        .filter(Boolean);
      if (features.length === 0) {
        featuresEl.textContent = 'لا توجد ميزات مضافة';
      } else {
        featuresEl.replaceChildren();
        features.forEach((feature) => {
          const badge = document.createElement('span');
          badge.className = 'badge bg-label-primary me-1 mb-1';
          badge.textContent = feature;
          featuresEl.appendChild(badge);
        });
      }
    }

    setReview('plan-review-price', `${formatPrice(price)} ر.س`);
    setReview('plan-review-billing-cycle', getSelectLabel('subscription_billing_cycle'));
    setReview('plan-review-discount', `${discount}%`);
    setReview('plan-review-final-price', `${formatPrice(finalPrice)} ر.س`);
    setReview('plan-review-renewal-price', renewalPrice > 0 ? `${formatPrice(renewalPrice)} ر.س` : 'غير محدد');

    setReview('plan-review-limits-mode', isUnlimited ? 'غير محدود' : 'محدد');
    setReview('plan-review-users', limitValue('included_users', 'مستخدم'));
    setReview('plan-review-extra-user-price', isUnlimited ? 'غير مطبق' : `${formatPrice(extraPrice)} ر.س`);
    setReview('plan-review-properties', limitValue('properties_limit', 'عقار'));
    setReview('plan-review-storage', limitValue('storage_quota_gb', 'GB'));
    setReview('plan-review-auctions', limitValue('active_auctions_limit', 'مزاد'));
    setReview('plan-review-domain', getSelectLabel('domain_management_enabled', getValue('domain_management_enabled') === '1' ? 'مفعل' : 'معطل'));
  }

  function updatePackageCards(event) {
    const cards = document.querySelectorAll('[data-package-card]');
    if (!cards.length) return;

    const price = Number.parseFloat(getValue('price', '0')) || 0;
    const discount = Math.min(100, Math.max(0, Number.parseFloat(getValue('discount_percentage', '0')) || 0));
    const finalPrice = Math.max(0, price - (price * discount / 100));
    const isUnlimited = getValue('limits_mode', 'limited') === 'unlimited';
    const formatPrice = (value) => value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const limitValue = (id, suffix) => isUnlimited ? 'غير محدود' : `${getValue(id, '—')} ${suffix}`;
    const selectedColor = getValue('color', 'purple');

    cards.forEach((card) => {
      card.dataset.planColor = selectedColor;
      card.setAttribute('data-plan-color', selectedColor);

      setText(card, 'name', getValue('name', 'اسم الباقة'));
      setText(card, 'type', getSelectLabel('plan_type', 'باقة مخصصة'));
      setText(card, 'description', getValue('description', 'أضف وصفاً واضحاً يشرح قيمة الباقة لعملائك.'));
      setText(card, 'discount-value', String(discount));
      setText(card, 'final-price', formatPrice(finalPrice));
      setText(card, 'billing-cycle', `لكل ${getSelectLabel('subscription_billing_cycle', 'شهري')}`);
      setText(card, 'original-price', `${formatPrice(price)} ر.س`);
      setText(card, 'users', limitValue('included_users', 'مستخدم'));
      setText(card, 'properties', limitValue('properties_limit', 'عقار'));
      setText(card, 'storage', limitValue('storage_quota_gb', 'GB'));

      card.querySelectorAll('[data-plan-field="discount"], [data-plan-field="original-price"]').forEach((element) => {
        element.hidden = discount <= 0;
      });
      renderFeatures(card);
    });

    updatePlanReviewCards();

    const reviewStep = getInput('review-validation');
    const previewAside = document.querySelector('[data-package-preview-aside]');
    const formColumn = document.querySelector('[data-plan-form-column]');

    let isReviewStep = false;
    if (event?.detail?.step) {
      isReviewStep = event.detail.step.id === 'review-validation';
    } else if (event?.detail?.to !== undefined || event?.detail?.stepIndex !== undefined) {
      const stepper = document.getElementById('plan-wizard');
      const targetIndex = event.detail.to !== undefined ? event.detail.to : event.detail.stepIndex;
      const targetStep = stepper?.querySelectorAll('[data-form-step], .content')?.[targetIndex];
      isReviewStep = targetStep?.id === 'review-validation';
    } else {
      isReviewStep = Boolean(
        reviewStep?.classList.contains('active') ||
        reviewStep?.classList.contains('dstepper-block')
      );
    }

    if (previewAside) {
      previewAside.hidden = isReviewStep;
      previewAside.classList.toggle('d-lg-none', isReviewStep);
      previewAside.classList.toggle('d-lg-block', !isReviewStep);
      if (isReviewStep) {
        previewAside.style.setProperty('display', 'none', 'important');
      } else {
        previewAside.style.removeProperty('display');
      }
    }

    if (formColumn) {
      formColumn.classList.toggle('col-lg-12', isReviewStep);
      formColumn.classList.toggle('col-lg-9', !isReviewStep);
    }
  }

  form.addEventListener('input', updatePackageCards);
  form.addEventListener('change', updatePackageCards);
  form.addEventListener('stepChanged', updatePackageCards);

  if (window.jQuery) {
    window.jQuery(form).on('input change select2:select select2:clear', updatePackageCards);
  }

  // Also bind directly on all inputs, selects, and textareas to guarantee reactivity
  form.querySelectorAll('input, select, textarea').forEach((control) => {
    control.addEventListener('input', updatePackageCards);
    control.addEventListener('change', updatePackageCards);
  });

  const stepperElement = document.getElementById('plan-wizard');
  if (stepperElement) {
    stepperElement.addEventListener('show.bs-stepper', updatePackageCards);
    stepperElement.addEventListener('shown.bs-stepper', updatePackageCards);
  }

  toggleLimitsMode();
  updatePackageCards();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPlanFormUI);
} else {
  initPlanFormUI();
}
