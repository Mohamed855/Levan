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

  // ==================== COUPON CODE GENERATOR ====================
  window.generateCouponCode = function () {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
      code += chars.charAt(Math.floor(Math.random() * chars.length));
    }

    const codeInput = document.getElementById('code');
    if (codeInput) {
      codeInput.value = code;
      codeInput.dispatchEvent(new Event('input', { bubbles: true }));
      codeInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const btn = document.getElementById('btn-generate-code') || document.querySelector('.btn-generate-code');
    const icon = btn ? btn.querySelector('i') : null;
    if (icon) {
      icon.classList.add('ti-spin');
      setTimeout(() => icon.classList.remove('ti-spin'), 500);
    }

    return code;
  };
  window.generateCode = window.generateCouponCode;

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

  const generateBtn = document.getElementById('btn-generate-code');
  if (generateBtn) {
    generateBtn.addEventListener('click', function (e) {
      e.preventDefault();
      window.generateCouponCode();
    });
  }

  // ==================== DISCOUNT TYPE TOGGLE ====================
  function updateDiscountFields() {
    const typeEl = document.getElementById('discount_type');
    const maxField = document.getElementById('maxDiscountField');
    if (!typeEl || !maxField) return;

    const rawType = (typeEl.value || (window.jQuery ? window.jQuery(typeEl).val() : '') || '').toString().trim().toLowerCase();
    const isPercentage = rawType === 'percentage';
    const valueInput = document.getElementById('discount_value');
    const maxInput = maxField.querySelector('input');

    if (isPercentage) {
      maxField.style.display = 'block';
      if (maxInput) {
        maxInput.disabled = false;
      }
      if (valueInput) {
        valueInput.setAttribute('max', '100');
      }
    } else {
      maxField.style.display = 'none';
      if (valueInput) {
        valueInput.removeAttribute('max');
      }
      if (maxInput) {
        maxInput.value = '';
        if (window.SharedFormValidation?.clearFieldError) {
          window.SharedFormValidation.clearFieldError(form, 'max_discount');
        }
      }
    }

    const discountLabel = form.querySelector('label[for="discount_value"]');
    if (discountLabel) {
      let unit = document.getElementById('discountUnit') || discountLabel.querySelector('.discount-unit');
      if (!unit && isPercentage) {
        unit = document.createElement('span');
        unit.id = 'discountUnit';
        unit.className = 'discount-unit text-muted small ms-1';
        const reqStar = discountLabel.querySelector('.text-danger');
        if (reqStar) {
          discountLabel.insertBefore(unit, reqStar);
        } else {
          discountLabel.appendChild(unit);
        }
      }
      if (unit) {
        unit.textContent = isPercentage ? ' (%)' : '';
      }
    }
  }

  window.updateDiscountFields = updateDiscountFields;

  const discountTypeSelect = document.getElementById('discount_type');
  if (discountTypeSelect) {
    discountTypeSelect.addEventListener('change', updateDiscountFields);
    discountTypeSelect.addEventListener('input', updateDiscountFields);

    if (window.jQuery) {
      const $ = window.jQuery;
      $(discountTypeSelect).on('change change.select2 select2:select select2:clear', updateDiscountFields);
      $(form).on('change select2:select select2:clear', '#discount_type', updateDiscountFields);
    }

    form.addEventListener('wizardDraftRestored', updateDiscountFields);

    updateDiscountFields();
  }
});
