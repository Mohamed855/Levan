'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[data-form-validate]');
  if (!form) return;

  const getInput = (id) => document.getElementById(id);
  const getValue = (id, fallback = '') => getInput(id)?.value?.trim() || fallback;
  const getSelectLabel = (id, fallback = '') => {
    const select = getInput(id);
    return select?.options?.[select.selectedIndex]?.text?.trim() || select?.dataset.planLabel || fallback;
  };
  const setText = (card, field, value) => {
    card.querySelectorAll(`[data-plan-field="${field}"]`).forEach((element) => {
      element.textContent = value;
    });
  };
  const planColors = {
    purple: '#372632',
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
    $(form).find('.select2').each(function () {
      const $select = $(this);
      if ($select.hasClass('select2-hidden-accessible')) return;

      $select.wrap('<div class="position-relative"></div>');
      const select2Options = {
        placeholder: $select.data('placeholder') || 'اختر خيارًا',
        dropdownParent: $select.parent(),
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
      };

      if (this.id === 'color') {
        select2Options.templateResult = formatPlanColor;
        select2Options.templateSelection = formatPlanColor;
      }

      $select.select2(select2Options).on('change', function () {
        this.dispatchEvent(new Event('input', { bubbles: true }));
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
  const unlimitedBranchesSelect = getInput('unlimited_branches');
  const branchesLimitContainer = getInput('branches_limit_container');

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

  function toggleBranchesLimit() {
    if (!unlimitedBranchesSelect || !branchesLimitContainer) return;
    const isUnlimited = unlimitedBranchesSelect.value === '1';
    branchesLimitContainer.hidden = isUnlimited;

    if (isUnlimited) {
      const input = branchesLimitContainer.querySelector('[name="branches_limit"]');
      if (input) window.SharedFormValidation?.clearFieldError?.(form, input.name);
    }

    refreshStepNextButton(unlimitedBranchesSelect);
    updatePackageCards();
  }

  if (limitsModeSelect) {
    limitsModeSelect.addEventListener('change', toggleLimitsMode);
  }

  if (unlimitedBranchesSelect) {
    unlimitedBranchesSelect.addEventListener('change', toggleBranchesLimit);
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

  function updatePackageCards() {
    const cards = document.querySelectorAll('[data-package-card]');
    if (!cards.length) return;

    const price = Number.parseFloat(getValue('price', '0')) || 0;
    const discount = Math.min(100, Math.max(0, Number.parseFloat(getValue('discount_percentage', '0')) || 0));
    const finalPrice = Math.max(0, price - (price * discount / 100));
    const isUnlimited = getValue('limits_mode', 'limited') === 'unlimited';
    const hasUnlimitedBranches = getValue('unlimited_branches') === '1';
    const formatPrice = (value) => value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const limitValue = (id, suffix) => isUnlimited ? 'غير محدود' : `${getValue(id, '—')} ${suffix}`;

    cards.forEach((card) => {
      card.dataset.planColor = getValue('color', 'purple');
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
      setText(card, 'branches', isUnlimited || hasUnlimitedBranches ? 'غير محدود' : `${getValue('branches_limit', '—')} فرع`);

      card.querySelectorAll('[data-plan-field="discount"], [data-plan-field="original-price"]').forEach((element) => {
        element.hidden = discount <= 0;
      });
      renderFeatures(card);
    });

    const reviewStep = getInput('review-validation');
    const previewAside = document.querySelector('[data-package-preview-aside]');
    const formColumn = document.querySelector('[data-plan-form-column]');
    const isReviewStep = reviewStep?.classList.contains('active');
    if (previewAside) previewAside.hidden = Boolean(isReviewStep);
    if (formColumn) formColumn.classList.toggle('col-lg-12', Boolean(isReviewStep));
    if (formColumn) formColumn.classList.toggle('col-lg-9', !isReviewStep);
  }

  form.addEventListener('input', updatePackageCards);
  form.addEventListener('change', updatePackageCards);
  form.addEventListener('stepChanged', updatePackageCards);
  toggleLimitsMode();
  toggleBranchesLimit();
  updatePackageCards();
});
