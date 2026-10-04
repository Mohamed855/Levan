'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('company-wizard-form') || document.getElementById('company-edit-form');
  if (!form) {
    return;
  }

  const plans = window.companyPlans || [];
  const paymentType = form.querySelector('[name="payment_type"]');
  const paymentMethod = form.querySelector('[name="payment_method"]');
  const discountType = form.querySelector('[name="has_discount"]');
  const planId = form.querySelector('[name="plan_id"]');
  const startDate = form.querySelector('[name="start_date"]');

  const setFieldVisibility = (fieldName, visible) => {
    const wrapper = form.querySelector('[data-form-field][data-field-name="' + fieldName + '"]');
    const control = form.querySelector('[data-form-control][name="' + fieldName + '"]');

    if (wrapper) {
      wrapper.classList.toggle('d-none', !visible);
    }

    if (control) {
      control.disabled = !visible;
      if (window.jQuery && control.tagName === 'SELECT') {
        window.jQuery(control).trigger('change.select2');
      }
    }
  };

  const syncPaymentFields = () => {
    const isPaid = paymentType?.value === 'paid';
    const isTrial = paymentType?.value === 'trial';
    const isBankTransfer = isPaid && paymentMethod?.value === 'bank_transfer';

    setFieldVisibility('payment_method', isPaid);
    setFieldVisibility('receipt_upload', isBankTransfer);
    setFieldVisibility('trial_end_date', isTrial);
    form.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const syncDiscountField = () => {
    setFieldVisibility('discount_value', discountType?.value === 'yes');
    form.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return year + '-' + month + '-' + day;
  };

  const syncEndDate = () => {
    const selectedPlan = plans.find((plan) => String(plan.id) === String(planId?.value));
    const endDate = form.querySelector('[name="end_date"]');
    if (!selectedPlan || !startDate?.value || !endDate) {
      return;
    }

    const start = new Date(startDate.value + 'T00:00:00');
    if (Number.isNaN(start.getTime())) {
      return;
    }

    const end = new Date(start);
    if (
      selectedPlan.subscription_duration_type === 'limited' &&
      selectedPlan.subscription_billing_cycle === 'monthly'
    ) {
      end.setMonth(end.getMonth() + 1);
    } else {
      end.setFullYear(end.getFullYear() + 1);
    }

    endDate.value = formatDate(end);
    endDate.dispatchEvent(new Event('change', { bubbles: true }));
  };

  const setReviewValue = (id, value) => {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value || '-';
    }
  };

  const selectedText = (name) => {
    const select = form.querySelector('[name="' + name + '"]');
    const option = select?.options[select.selectedIndex];

    return option?.textContent?.trim() || '-';
  };

  const updateReview = () => {
    const selectedPlan = plans.find((plan) => String(plan.id) === String(planId?.value));
    const paymentLabels = {
      paid: 'تم الدفع',
      unpaid: 'لم يتم الدفع',
      trial: 'فترة تجربة',
    };

    setReviewValue('review-company-name', form.querySelector('[name="company_name"]')?.value);
    setReviewValue('review-commercial-number', form.querySelector('[name="commercial_number"]')?.value);
    setReviewValue('review-tax-number', form.querySelector('[name="tax_number"]')?.value);
    setReviewValue('review-city', selectedText('city_id'));
    setReviewValue('review-address', form.querySelector('[name="address"]')?.value);
    setReviewValue('review-admin-name', form.querySelector('[name="name"]')?.value);
    setReviewValue('review-admin-phone', form.querySelector('[name="phone"]')?.value);
    setReviewValue('review-admin-email', form.querySelector('[name="email"]')?.value);
    setReviewValue('review-plan-name', selectedPlan?.name);
    setReviewValue('review-start-date', startDate?.value);
    setReviewValue('review-end-date', form.querySelector('[name="end_date"]')?.value);
    setReviewValue('review-payment-status', paymentLabels[paymentType?.value] || '-');
    setReviewValue('review-payment-method', paymentType?.value === 'paid' ? selectedText('payment_method') : '-');

    const reviewActivation = document.getElementById('review-activation-status');
    if (reviewActivation) {
      const isImmediate = form.querySelector('[name="activate_immediately"]')?.checked;
      reviewActivation.className = 'badge ' + (isImmediate ? 'bg-label-success' : 'bg-label-warning');
      reviewActivation.textContent = isImmediate ? 'مفعل فوراً' : 'قيد المراجعة';
    }
  };

  if (window.jQuery?.fn.select2) {
    window.jQuery(form)
      .find('.select2')
      .select2({
        placeholder: (index, element) => window.jQuery(element).data('placeholder') || 'اختر',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl',
      });
  }

  paymentType?.addEventListener('change', syncPaymentFields);
  paymentMethod?.addEventListener('change', syncPaymentFields);
  discountType?.addEventListener('change', syncDiscountField);
  planId?.addEventListener('change', syncEndDate);
  startDate?.addEventListener('change', syncEndDate);

  form.addEventListener('change', updateReview);
  form.closest('[data-form-stepper]')?.addEventListener('shown.bs-stepper', (event) => {
    if (event.detail?.indexStep === 3) {
      updateReview();
    }
  });

  syncPaymentFields();
  syncDiscountField();
  syncEndDate();
});
