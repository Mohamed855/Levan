'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const $ = window.jQuery;

  window.validateSaudiPhone = (value) => /^05[0-9]{8}$/.test(String(value || '').replace(/\s/g, ''));
  window.validateAdditionalPhones = () => true;

  if ($?.fn.flatpickr) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d', locale: 'ar', allowInput: true, maxDate: 'today',
      onClose(_, __, instance) {
        instance.element.dispatchEvent(new Event('change', { bubbles: true }));
      },
    });
  }

  $('#company_id').on('change', function () {
    const portfolioSelect = $('#portfolio_id');
    const companyId = $(this).val();
    portfolioSelect.val('').trigger('change');
    if (!companyId) return;

    $.get(window.routes?.portfoliosByCompany, { company_id: companyId })
      .done((response) => {
        if (!response.success) return;
        portfolioSelect.empty().append('<option value=""></option>');
        response.data.forEach((portfolio) => portfolioSelect.append($('<option>', {
          value: portfolio.id,
          text: portfolio.name,
          'data-has-split': portfolio.has_split_names ? 1 : 0,
        })));
        portfolioSelect.trigger('change');
      })
      .fail(() => window.toastr?.error?.('حدث خطأ في تحميل المحافظ'));
  });

  $('#identity_number').on('input', function () {
    this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
  });
  $('#debt_amount, #commission_rate, #claims_count').on('input', function () {
    this.value = this.value.replace(this.id === 'claims_count' ? /[^0-9]/g : /[^0-9.]/g, '');
  });
  $(document).on('input', '.phone-input', function () {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
  });
});
