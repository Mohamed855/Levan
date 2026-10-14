/**
 * Contact & Trial Forms Interaction Handler
 * Integrates with Laravel SharedFormValidation and Google reCAPTCHA v3
 */

export function initContactForms() {
  // Smooth scroll to contact button on navbar
  const contactButton = document.getElementById('navbar-contact-btn');
  const contactSection = document.getElementById('contact');

  contactButton?.addEventListener('click', (e) => {
    e.preventDefault();
    contactSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  const form = document.getElementById('form');
  const submitBtn = document.getElementById('submit-form');
  if (!form || !submitBtn) return;

  const btnText = submitBtn.querySelector('.btn-text');
  const btnSpinner = submitBtn.querySelector('.spinner-border');
  const recaptchaInput = document.getElementById('g-recaptcha-response');

  let isSubmitting = false;

  function setLoadingState(loading, defaultText = 'إرسال الرسالة') {
    isSubmitting = loading;
    submitBtn.disabled = loading;

    if (loading) {
      if (btnText) btnText.textContent = 'جاري الإرسال...';
      if (btnSpinner) btnSpinner.classList.remove('hidden');
    } else {
      if (btnText) btnText.textContent = defaultText;
      if (btnSpinner) btnSpinner.classList.add('hidden');
    }
  }

  const initialButtonText = btnText ? btnText.textContent.trim() : 'إرسال';

  form.addEventListener(
    'submit',
    async function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      if (isSubmitting) return;

      // Determine active fields to validate dynamically from input names
      const fields = Array.from(
        form.querySelectorAll('input[name], textarea[name], select[name]')
      )
        .map((el) => el.getAttribute('name'))
        .filter(
          (name) =>
            name &&
            name !== '_token' &&
            name !== 'g-recaptcha-response' &&
            !name.startsWith('_')
        );

      let isValid = true;
      if (window.SharedFormValidation?.validateFields) {
        isValid = await window.SharedFormValidation.validateFields(form, fields);
      }

      if (!isValid) return;

      // reCAPTCHA verification if key is present
      if (window.recaptchaSiteKey) {
        if (typeof grecaptcha === 'undefined') {
          if (typeof toastr !== 'undefined') {
            toastr.error('reCAPTCHA لم يتم تحميلها بعد، يرجى تحديث الصفحة');
          }
          return;
        }

        setLoadingState(true, initialButtonText);

        grecaptcha.ready(function () {
          const action = form.getAttribute('action')?.includes('trial')
            ? 'trial_form'
            : 'contact_form';

          grecaptcha
            .execute(window.recaptchaSiteKey, { action })
            .then(function (token) {
              if (!token) throw new Error('Failed to generate token');
              if (recaptchaInput) recaptchaInput.value = token;
              HTMLFormElement.prototype.submit.call(form);
            })
            .catch(function (error) {
              console.error('reCAPTCHA error:', error);
              setLoadingState(false, initialButtonText);
              if (typeof toastr !== 'undefined') {
                toastr.error('فشل التحقق من reCAPTCHA، يرجى المحاولة مجدداً');
              }
            });
        });
      } else {
        // Without recaptcha
        setLoadingState(true, initialButtonText);
        HTMLFormElement.prototype.submit.call(form);
      }
    },
    true
  );
}
