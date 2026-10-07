(function ($) {
  'use strict';

  function toggleNameFields() {
    const hasSplit = Number($('#portfolio_id option:selected').data('has-split')) === 1;
    $('#full-name-wrapper').toggleClass('d-none', hasSplit);
    $('#split-names-wrapper').toggleClass('d-none', !hasSplit).css('display', hasSplit ? 'flex' : '');
    $('#name').prop('required', !hasSplit);
    $('#first_name, #family_name').prop('required', hasSplit);

    if (hasSplit) {
      $('#name').val('').trigger('input');
      return;
    }

    $('#first_name, #father_name, #grandfather_name, #family_name').val('').trigger('input');
  }

  $(function () {
    toggleNameFields();
    $('#portfolio_id').on('change', toggleNameFields);
  });
})(jQuery);
