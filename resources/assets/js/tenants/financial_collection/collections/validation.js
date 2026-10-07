'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const $ = window.jQuery;
  const form = document.querySelector('#wizard-validation-form');

  if (!form || !$) return;

  const customerSelect = $('#customer_id');
  if (customerSelect.length && !customerSelect.hasClass('select2-hidden-accessible')) {
    customerSelect.select2({
      placeholder: customerSelect.data('placeholder') || 'ابحث عن العميل برقم الحساب',
      allowClear: false,
      width: '100%',
      dir: 'rtl',
      minimumInputLength: 0,
      ajax: {
        url: window.routes.searchCustomers,
        dataType: 'json',
        delay: 300,
        data: (params) => ({q: params.term || '', page: params.page || 1}),
        processResults: (data) => ({results: data, pagination: {more: data.length === 20}}),
        cache: true,
      },
    });
  }

  $('.flatpickr-date').flatpickr({
    dateFormat: 'Y-m-d',
    locale: 'ar',
    allowInput: true,
    maxDate: 'today',
    onClose: (_, __, instance) => $(instance.element).trigger('change'),
  });

  $('#collected_amount').on('input', function () {
    const parts = String(this.value || '').replace(/[^0-9.]/g, '').split('.');
    this.value = parts.length > 1
      ? `${parts.shift()}.${parts.join('').slice(0, 2)}`
      : parts[0];
  });

  $('#generate-receipt').on('click', function () {
    const button = $(this);
    const original = button.html();
    button.prop('disabled', true).html('<i class="ti ti-loader-2 ti-spin"></i>');

    $.get(window.routes.generateReceiptNumber)
      .done((response) => {
        if (response.success) $('#receipt_number').val(response.receipt_number).trigger('input');
      })
      .always(() => button.prop('disabled', false).html(original));
  });

  $('#attachment').on('change', function () {
    const file = this.files?.[0];
    $('#file-info').remove();
    if (file) {
      $('<small>', {
        id: 'file-info',
        class: 'text-muted mt-1 d-block',
        text: `تم اختيار: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} ميجابايت)`,
      }).insertAfter(this);
    }
  });

  $('#remove_attachment').on('change', function () {
    $('#attachment').prop('disabled', this.checked);
    $('.current-file').toggleClass('text-decoration-line-through opacity-50', this.checked);
  });

  form.addEventListener('formValidationPassed', () => {
    const submit = form.querySelector('.btn-submit');
    if (submit) {
      submit.disabled = true;
      submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';
    }
  });
});
