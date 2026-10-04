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
    const unit = document.getElementById('discountUnit');
    const valueInput = document.getElementById('discount_value');

    if (!typeEl || !maxField || !unit || !valueInput) return;

    const type = typeEl.value;

    if (type === 'percentage') {
      maxField.style.display = 'block';
      unit.textContent = ' (%)';
      valueInput.setAttribute('max', '100');
    } else {
      maxField.style.display = 'none';
      unit.textContent = '';
      valueInput.removeAttribute('max');
      const maxInput = maxField.querySelector('input');
      if (maxInput) {
        maxInput.value = '';
        if (window.SharedFormValidation?.clearFieldError) {
          window.SharedFormValidation.clearFieldError(form, 'max_discount');
        }
      }
    }
  }

  window.updateDiscountFields = updateDiscountFields;

  const discountTypeSelect = document.getElementById('discount_type');
  if (discountTypeSelect) {
    discountTypeSelect.addEventListener('change', updateDiscountFields);
    if (window.jQuery) {
      window.jQuery(discountTypeSelect).on('change', updateDiscountFields);
    }
    updateDiscountFields();
  }
});
