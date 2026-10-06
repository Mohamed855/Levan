'use strict';

const formStates = new WeakMap();

function getFormState(form) {
  if (!formStates.has(form)) {
    formStates.set(form, {
      controllers: new Map(),
      fieldVersions: new Map(),
      timers: new Map(),
      isSubmitting: false,
      isNavigating: false,
      stepper: null
    });
  }

  return formStates.get(form);
}

function normalizeFieldName(name) {
  return String(name || '')
    .replace(/\[([^\]]*)\]/g, '.$1')
    .replace(/\.+/g, '.')
    .replace(/^\.|\.$/g, '');
}

function fieldMatches(fieldName, requestedName) {
  return normalizeFieldName(fieldName) === normalizeFieldName(requestedName);
}

function findFieldWrappers(form, fieldName) {
  return Array.from(form.querySelectorAll('[data-form-field]')).filter((wrapper) =>
    fieldMatches(wrapper.dataset.fieldName, fieldName)
  );
}

function findControls(form, fieldName) {
  return Array.from(form.querySelectorAll('[data-form-control][name], input[name], select[name], textarea[name]')).filter((control) =>
    fieldMatches(control.name, fieldName)
  );
}

function stateTargets(control) {
  const targets = [control];
  const inputGroup = control.closest('.input-group');

  if (inputGroup) {
    targets.push(inputGroup);
  }

  if (control.tagName === 'SELECT') {
    const select2Container = control.nextElementSibling?.matches('.select2')
      ? control.nextElementSibling
      : null;

    if (select2Container) {
      targets.push(select2Container);
    }
  }

  return [...new Set(targets)];
}

function shouldShowValidState(control) {
  if (control.type === 'checkbox' || control.type === 'radio') {
    return control.checked;
  }

  if (control.type === 'file') {
    return Boolean(control.files?.length);
  }

  return String(control.value || '').trim() !== '';
}

function clearFieldValidState(form, fieldName) {
  findFieldWrappers(form, fieldName).forEach((wrapper) => {
    wrapper.classList.remove('is-valid');
  });

  findControls(form, fieldName).forEach((control) => {
    stateTargets(control).forEach((target) => target.classList.remove('is-valid'));
  });
}

function ensureValidationContract(form) {
  form.querySelectorAll('[data-form-control][name], input[name], select[name], textarea[name]').forEach((control) => {
    if (control.type === 'hidden' || control.matches('[data-validation-skip]')) {
      return;
    }

    control.dataset.formControl = '';
    const wrapper = control.closest('[data-form-field]') || control.parentElement;
    if (!wrapper || !control.name) {
      return;
    }

    wrapper.dataset.formField = '';
    wrapper.dataset.fieldName = control.name;
    const error = wrapper.querySelector('[data-form-error], .invalid-feedback');
    if (error) {
      error.dataset.formError = '';
      if (error.textContent.trim() === '') {
        error.hidden = true;
        error.classList.add('hidden');
      }
      return;
    }

    const generatedError = document.createElement('p');
    generatedError.className = 'invalid-feedback hidden';
    generatedError.dataset.formError = '';
    generatedError.setAttribute('aria-live', 'polite');
    generatedError.hidden = true;
    wrapper.append(generatedError);
  });
}

/**
 * Single-spot error clearing:
 * Resets the single dedicated error element ([data-form-error] or .invalid-feedback)
 */
function clearFieldError(form, fieldName) {
  findFieldWrappers(form, fieldName).forEach((wrapper) => {
    wrapper.classList.remove('is-invalid');
    const error = wrapper.querySelector('[data-form-error]');
    if (error) {
      error.textContent = '';
      error.hidden = true;
      error.classList.add('hidden');
      error.style.display = 'none';
    }
  });

  findControls(form, fieldName).forEach((control) => {
    stateTargets(control).forEach((target) => target.classList.remove('is-invalid'));
    control.setAttribute('aria-invalid', 'false');

    // Fallback for fields not wrapped with [data-form-field]
    const wrapper = control.closest('[data-form-field]');
    if (!wrapper) {
      const feedback = control.parentElement?.querySelector('.invalid-feedback') ||
                       control.closest('.col-sm-6, .col-md-6, .col-md-4, .col-12')?.querySelector('.invalid-feedback');
      if (feedback) {
        feedback.textContent = '';
        feedback.hidden = true;
        feedback.style.display = 'none';
      }
    }
  });
}

/**
 * Single-spot error display:
 * Overwrites the exact same container whether error comes from client-side or backend.
 * They will never display together or duplicate.
 */
function setFieldError(form, fieldName, message) {
  const wrappers = findFieldWrappers(form, fieldName);

  wrappers.forEach((wrapper) => {
    wrapper.classList.remove('is-valid');
    wrapper.classList.add('is-invalid');
    const error = wrapper.querySelector('[data-form-error]');
    if (error) {
      error.textContent = message;
      error.hidden = false;
      error.classList.remove('hidden');
      error.setAttribute('aria-live', 'polite');
      error.style.display = 'block';
    }
  });

  findControls(form, fieldName).forEach((control) => {
    stateTargets(control).forEach((target) => {
      target.classList.remove('is-valid');
      target.classList.add('is-invalid');
    });
    control.setAttribute('aria-invalid', 'true');

    // Fallback for fields not wrapped with [data-form-field]
    if (wrappers.length === 0) {
      let feedback = control.parentElement?.querySelector('.invalid-feedback');
      if (!feedback) {
        feedback = control.closest('.col-sm-6, .col-md-6, .col-md-4, .col-12')?.querySelector('.invalid-feedback');
      }
      if (feedback) {
        feedback.textContent = message;
        feedback.hidden = false;
        feedback.style.display = 'block';
      }
    }
  });
}

function setFieldValid(form, fieldName) {
  clearFieldError(form, fieldName);

  const controls = findControls(form, fieldName);
  if (!controls.some(shouldShowValidState)) {
    clearFieldValidState(form, fieldName);
    return;
  }

  findFieldWrappers(form, fieldName).forEach((wrapper) => {
    wrapper.classList.add('is-valid');
  });

  controls.forEach((control) => {
    stateTargets(control).forEach((target) => target.classList.add('is-valid'));
    control.setAttribute('aria-invalid', 'false');
  });
}

function parseDependencies(control) {
  return String(control.dataset.validationDependents || '')
    .split(/[\s,]+/)
    .map((field) => field.trim())
    .filter(Boolean);
}

function getTargetMethod(form) {
  return String(
    form.dataset.validationMethod ||
      form.querySelector('input[name="_method"]')?.value ||
      form.method ||
      'POST'
  ).toUpperCase();
}

function getTargetPath(form) {
  return new URL(form.action, window.location.origin).pathname;
}

function draftResourceKey(form) {
  return form.dataset.draftResourceKey || getTargetPath(form);
}

function addHiddenField(form, name, value) {
  let input = form.querySelector('input[name="' + name + '"]');
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    form.append(input);
  }
  input.value = value;
}

function currentStepNumber(form) {
  const state = getFormState(form);
  const currentIndex = Number.isInteger(state.stepper?._currentIndex) ? state.stepper._currentIndex : 0;

  return currentIndex + 1;
}

function restoreDraftValues(form, values, prefix = '') {
  Object.entries(values || {}).forEach(([key, value]) => {
    const name = prefix ? prefix + '[' + key + ']' : key;
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      restoreDraftValues(form, value, name);
      return;
    }

    const field = form.elements.namedItem(name);
    if (!field) {
      return;
    }

    const controls = field instanceof RadioNodeList ? Array.from(field) : [field];
    controls.forEach((control) => {
      if (control.type === 'password' || control.type === 'file') {
        return;
      }

      if (control.type === 'checkbox' || control.type === 'radio') {
        control.checked = String(control.value) === String(value) || value === true || value === 1 || value === '1';
      } else {
        control.value = Array.isArray(value) ? value : String(value ?? '');
      }

      if (window.jQuery && control.tagName === 'SELECT') {
        window.jQuery(control).trigger('change.select2');
      }
      control.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
}

async function discardDraft(form) {
  const draftUrl = form.dataset.draftUrl;
  if (!draftUrl) {
    return;
  }

  await fetch(draftUrl + '?resource_key=' + encodeURIComponent(draftResourceKey(form)), {
    method: 'DELETE',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
  });
}

async function restoreDraft(form) {
  const draftUrl = form.dataset.draftUrl;
  if (!draftUrl || form.querySelector('.is-invalid, [data-form-error]:not([hidden])')) {
    return;
  }

  try {
    const response = await fetch(draftUrl + '?resource_key=' + encodeURIComponent(draftResourceKey(form)), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
      return;
    }

    const draft = await response.json();
    if (!draft.draft_id) {
      return;
    }

    let shouldRestore = true;
    if (window.Swal) {
      const result = await window.Swal.fire({
        title: 'مسودة محفوظة',
        text: 'توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، استعادة المسودة',
        cancelButtonText: 'تجاهل والبدء من جديد',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
        },
        buttonsStyling: false,
      });
      shouldRestore = result.isConfirmed;
    }

    if (!shouldRestore) {
      await discardDraft(form);
      return;
    }

    addHiddenField(form, '_wizard_draft_id', draft.draft_id);
    addHiddenField(form, '_wizard_resource_key', draftResourceKey(form));
    restoreDraftValues(form, draft.data || {});
    form.dispatchEvent(new CustomEvent('wizardDraftRestored', {
      detail: { data: draft.data || {}, draftId: draft.draft_id }
    }));
    getFormState(form).stepper?.to(Math.max(1, Number(draft.step || 1)));
  } catch (_) {
    window.toastr?.error?.('تعذر استعادة المسودة. يرجى المحاولة مرة أخرى.');
  }
}

function initDrafts(form) {
  const draftUrl = form.dataset.draftUrl;
  if (!draftUrl) {
    return;
  }

  form.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-wizard-draft]');
    if (!button) {
      return;
    }

    event.preventDefault();
    button.disabled = true;
    try {
      const data = new FormData(form);
      data.set('resource_key', draftResourceKey(form));
      data.set('step', String(currentStepNumber(form)));

      const response = await fetch(draftUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload.draft_id) {
        throw new Error('Unable to save draft.');
      }

      addHiddenField(form, '_wizard_draft_id', payload.draft_id);
      addHiddenField(form, '_wizard_resource_key', draftResourceKey(form));
      window.toastr?.success?.(payload.message || 'تم حفظ المسودة بنجاح.');
    } catch (_) {
      window.toastr?.error?.('تعذر حفظ المسودة. يرجى المحاولة مرة أخرى.');
    } finally {
      button.disabled = false;
    }
  });

  restoreDraft(form);
}

function validationFieldsFromStep(step) {
  try {
    const fields = JSON.parse(step?.dataset.validationFields || '[]');
    if (Array.isArray(fields) && fields.length > 0) {
      return fields.filter(Boolean);
    }
  } catch (_) {}

  // Fallback: collect control names in step
  if (!step) return [];
  const controls = Array.from(step.querySelectorAll('[name]'));
  return [...new Set(controls.map((c) => c.name).filter(Boolean))];
}

/**
 * Validates a single control on the client side (instant browser check).
 * Returns true if valid, false if invalid.
 */
function validateControlClientSide(form, control) {
  if (!control || control.disabled || control.closest('.d-none') || control.closest('[hidden]') || (control.type !== 'hidden' && control.offsetParent === null)) {
    return true;
  }

  const name = control.name;
  if (!name) return true;

  const value = control.value;
  const isRequired = control.required || control.hasAttribute('required') || control.getAttribute('aria-required') === 'true';

  // 1. Required check
  if (isRequired) {
    if (control.type === 'checkbox' && !control.checked) {
      setFieldError(form, name, 'يرجى تحديد هذا الخيار');
      return false;
    }
    if (control.type === 'file' && (!control.files || control.files.length === 0)) {
      setFieldError(form, name, 'يرجى اختيار ملف');
      return false;
    }
    if (!value || value.trim() === '') {
      setFieldError(form, name, 'هذا الحقل مطلوب');
      return false;
    }
  }

  // 2. Format checks (if value is provided)
  if (value && value.trim() !== '') {
    const trimmed = value.trim();

    // Number min/max checks
    if (control.type === 'number') {
      const numVal = parseFloat(trimmed);
      if (isNaN(numVal)) {
        setFieldError(form, name, 'يجب إدخال رقم صحيح');
        return false;
      }
      if (control.min !== '' && !isNaN(parseFloat(control.min)) && numVal < parseFloat(control.min)) {
        setFieldError(form, name, `القيمة يجب ألا تقل عن ${control.min}`);
        return false;
      }
      if (control.max !== '' && !isNaN(parseFloat(control.max)) && numVal > parseFloat(control.max)) {
        setFieldError(form, name, `القيمة يجب ألا تزيد عن ${control.max}`);
        return false;
      }
    }

    // Date min check
    if (control.type === 'date' && control.min && trimmed < control.min) {
      setFieldError(form, name, `التاريخ يجب أن يكون ${control.min} أو بعده`);
      return false;
    }

    // Email
    if (control.type === 'email' || name.toLowerCase().includes('email')) {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        setFieldError(form, name, 'البريد الإلكتروني غير صحيح');
        return false;
      }
    }

    // HTML5 pattern attribute
    if (control.pattern) {
      try {
        const regex = new RegExp('^' + control.pattern + '$');
        if (!regex.test(trimmed)) {
          setFieldError(form, name, control.title || 'صيغة الحقل غير صحيحة');
          return false;
        }
      } catch (_) {}
    }


    // Minlength
    if (control.minLength && control.minLength > 0 && trimmed.length < control.minLength) {
      setFieldError(form, name, `يجب ألا يقل عن ${control.minLength} أحرف`);
      return false;
    }

    // Password confirmation match
    if (name === 'password_confirmation') {
      const pwd = form.querySelector('[name="password"]')?.value;
      if (pwd && value !== pwd) {
        setFieldError(form, name, 'تأكيد كلمة المرور غير مطابق');
        return false;
      }
    }
  }

  setFieldValid(form, name);
  return true;
}

/**
 * Checks whether all fields in a given step are currently valid.
 */
function isStepValid(form, step) {
  if (!step) return true;

  const controls = Array.from(step.querySelectorAll('[name]')).filter((control) => {
    if (['submit', 'button', 'reset', 'hidden'].includes(control.type)) {
      return false;
    }
    if (control.disabled || control.closest('.d-none') || control.closest('[hidden]') || (control.type !== 'hidden' && control.offsetParent === null)) {
      return false;
    }
    return true;
  });

  for (const control of controls) {
    // 1. Is it currently marked as invalid?
    if (control.classList.contains('is-invalid') || control.getAttribute('aria-invalid') === 'true') {
      return false;
    }
    const wrapper = control.closest('[data-form-field]');
    if (wrapper && wrapper.classList.contains('is-invalid')) {
      return false;
    }

    // 2. Is it required and empty?
    const isRequired = control.required || control.hasAttribute('required') || control.getAttribute('aria-required') === 'true';
    if (isRequired) {
      if (control.type === 'checkbox' && !control.checked) {
        return false;
      }
      if (control.type === 'file' && (!control.files || control.files.length === 0)) {
        return false;
      }
      if (!control.value || control.value.trim() === '') {
        return false;
      }
    }

    // 3. Client format rules if filled
    if (control.value && control.value.trim() !== '') {
      const val = control.value.trim();

      if (control.type === 'number') {
        const numVal = parseFloat(val);
        if (isNaN(numVal)) return false;
        if (control.min !== '' && !isNaN(parseFloat(control.min)) && numVal < parseFloat(control.min)) return false;
        if (control.max !== '' && !isNaN(parseFloat(control.max)) && numVal > parseFloat(control.max)) return false;
      }
      if (control.type === 'date' && control.min && val < control.min) {
        return false;
      }
      if (control.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        return false;
      }
      if (control.name === 'password_confirmation') {

        const pwd = form.querySelector('[name="password"]')?.value;
        if (pwd && val !== pwd) {
          return false;
        }
      }
      if (control.pattern) {
        try {
          if (!new RegExp('^' + control.pattern + '$').test(val)) return false;
        } catch (_) {}
      }
      if (control.minLength && control.minLength > 0 && val.length < control.minLength) {
        return false;
      }
    }
  }

  return true;
}

/**
 * Updates the disabled state of the Next button in a given step.
 * "زر الnext يبقى disabled لحد م الcurrent step تبقى valid بالكامل"
 */
function updateStepNextButton(form, step) {
  if (!step) return;
  const nextButtons = step.querySelectorAll('[data-wizard-next], .btn-next:not(.btn-submit)');
  const valid = isStepValid(form, step);

  nextButtons.forEach((btn) => {
    btn.disabled = !valid;
    if (valid) {
      btn.removeAttribute('disabled');
      btn.classList.remove('disabled');
    } else {
      btn.setAttribute('disabled', 'disabled');
      btn.classList.add('disabled');
    }
  });
}

async function validateFields(form, fieldNames) {
  const fields = [...new Set(fieldNames.filter(Boolean))];
  const validationUrl = form.dataset.validationUrl;

  if (!validationUrl || fields.length === 0) {
    return true;
  }

  const state = getFormState(form);
  const requestKey = fields.map(normalizeFieldName).sort().join('|');
  const fieldVersions = new Map(
    fields.map((field) => {
      const key = normalizeFieldName(field);
      const version = (state.fieldVersions.get(key) || 0) + 1;
      state.fieldVersions.set(key, version);

      return [key, version];
    })
  );
  state.controllers.get(requestKey)?.abort();

  const controller = new AbortController();
  state.controllers.set(requestKey, controller);

  const data = new FormData(form);
  data.append('target_path', getTargetPath(form));
  data.append('target_method', getTargetMethod(form));
  fields.forEach((field) => data.append('fields[]', field));

  try {
    const response = await fetch(validationUrl, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      signal: controller.signal
    });

    const payload = await response.json().catch(() => ({}));

    const isStale = fields.some(
      (field) => state.fieldVersions.get(normalizeFieldName(field)) !== fieldVersions.get(normalizeFieldName(field))
    );
    if (controller.signal.aborted || isStale) {
      return false;
    }

    if (response.ok && payload.success) {
      fields.forEach((field) => setFieldValid(form, field));
      return true;
    }

    const errors = payload.errors || {};
    fields.forEach((field) => {
      const hasError = Object.keys(errors).some((errorField) => fieldMatches(errorField, field));
      if (!hasError) {
        setFieldValid(form, field);
      }
    });
    Object.entries(errors).forEach(([field, messages]) => {
      const message = Array.isArray(messages) ? messages[0] : messages;
      if (message) {
        setFieldError(form, field, message);
      }
    });

    if (Object.keys(errors).length === 0 && !response.ok) {
      setFieldError(form, fields[0], payload.message || 'تعذر التحقق من الحقل الآن. يرجى المحاولة مرة أخرى.');
    }

    return false;
  } catch (error) {
    if (error.name === 'AbortError') {
      return false;
    }

    setFieldError(form, fields[0], 'تعذر التحقق من الحقل الآن. يرجى المحاولة مرة أخرى.');
    return false;
  } finally {
    if (state.controllers.get(requestKey) === controller) {
      state.controllers.delete(requestKey);
    }
  }
}

function scheduleValidation(form, fields) {
  const state = getFormState(form);
  const key = fields.map(normalizeFieldName).sort().join('|');
  window.clearTimeout(state.timers.get(key));
  state.timers.set(
    key,
    window.setTimeout(() => {
      validateFields(form, fields).then(() => {
        const stepperElement = form.closest('[data-form-stepper]');
        if (stepperElement) {
          const steps = Array.from(stepperElement.querySelectorAll('[data-form-step], .content'));
          const currentStep = steps.find((s) => s.classList.contains('active')) || steps[0];
          updateStepNextButton(form, currentStep);
        }
      });
      state.timers.delete(key);
    }, 400)
  );
}

function initStepper(form) {
  const stepperElement = form.closest('[data-form-stepper]');
  if (!stepperElement || typeof window.Stepper !== 'function') {
    return;
  }

  const state = getFormState(form);
  state.stepper = new window.Stepper(stepperElement, { linear: true });

  const getSteps = () => Array.from(stepperElement.querySelectorAll('[data-form-step], .content'));

  const getCurrentIndex = () => {
    const steps = getSteps();
    const activeIndex = steps.findIndex((step) => step.classList.contains('active'));
    return activeIndex >= 0 ? activeIndex : 0;
  };

  const updateCurrentStepButton = () => {
    const steps = getSteps();
    const currentIndex = getCurrentIndex();
    const currentStep = steps[currentIndex];
    if (currentStep) {
      updateStepNextButton(form, currentStep);
    }
  };

  const moveTo = async (targetIndex) => {
    if (state.isNavigating) {
      return;
    }

    const currentIndex = getCurrentIndex();
    if (targetIndex === currentIndex) {
      return;
    }

    state.isNavigating = true;
    const steps = getSteps();
    const currentStep = steps[currentIndex];
    const nextBtn = currentStep?.querySelector('[data-wizard-next], .btn-next');
    if (nextBtn) {
      nextBtn.disabled = true;
    }

    try {
      if (targetIndex > currentIndex) {
        const valid = await validateFields(form, validationFieldsFromStep(currentStep));
        if (!valid) {
          updateCurrentStepButton();
          return;
        }
      }

      state.stepper.to(targetIndex + 1);
      form.dispatchEvent(new CustomEvent('stepChanged', { detail: { stepIndex: targetIndex, step: steps[targetIndex] } }));
      setTimeout(updateCurrentStepButton, 100);
    } finally {
      state.isNavigating = false;
      updateCurrentStepButton();
    }
  };

  stepperElement.addEventListener(
    'click',
    (event) => {
      const trigger = event.target.closest('.step-trigger');
      const nextButton = event.target.closest('[data-wizard-next], .btn-next:not(.btn-submit)');
      const previousButton = event.target.closest('[data-wizard-prev], .btn-prev');

      if (!trigger && !nextButton && !previousButton) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      // Header clicks are informational only and cannot jump steps ahead
      if (trigger) {
        return;
      }

      if (nextButton) {
        if (nextButton.disabled || nextButton.classList.contains('disabled')) {
          return;
        }
        moveTo(getCurrentIndex() + 1);
        return;
      }

      const currentIndex = getCurrentIndex();
      if (currentIndex > 0) {
        state.stepper.previous();
        const steps = getSteps();
        form.dispatchEvent(new CustomEvent('stepChanged', { detail: { stepIndex: currentIndex - 1, step: steps[currentIndex - 1] } }));
        setTimeout(updateCurrentStepButton, 100);
      } else if (previousButton && (previousButton.dataset.wizardBack === 'true' || previousButton.getAttribute('href'))) {
        if (previousButton.getAttribute('href')) {
          window.location.href = previousButton.getAttribute('href');
        } else {
          window.history.back();
        }
      }
    },
    true
  );

  // Initial check on load
  setTimeout(updateCurrentStepButton, 150);
}

function initForm(form) {
  if (form.dataset.formValidationInitialized === 'true') {
    return;
  }

  form.dataset.formValidationInitialized = 'true';
  form.noValidate = true;
  ensureValidationContract(form);
  initStepper(form);
  initDrafts(form);

  const getStepForControl = (control) => control.closest('[data-form-step], .content');

  form.querySelectorAll('[data-form-control][name], input[name], select[name], textarea[name]').forEach((control) => {
    if (['submit', 'button', 'reset', 'hidden'].includes(control.type)) {
      return;
    }

    const fields = () => [control.name, ...parseDependencies(control)];
    const immediateEvents = ['select', 'file', 'checkbox', 'radio'];
    const shouldValidateOnChange = immediateEvents.includes(control.type) || control.tagName === 'SELECT';

    control.addEventListener('input', () => {
      // 1. Instant client-side feedback
      validateControlClientSide(form, control);

      // 2. Reactively update the Next button disabled state
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }

      // 3. Debounced backend check if appropriate
      if (!shouldValidateOnChange) {
        scheduleValidation(form, fields());
      }
    });

    control.addEventListener('change', () => {
      validateControlClientSide(form, control);
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }
      validateFields(form, fields()).then(() => {
        if (step) updateStepNextButton(form, step);
      });
    });

    control.addEventListener('blur', () => {
      validateControlClientSide(form, control);
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }
      validateFields(form, fields()).then(() => {
        if (step) updateStepNextButton(form, step);
      });
    });
  });

  // Select2 change integration
  if (window.jQuery) {
    window.jQuery(form).on('change.select2', 'select', function () {
      validateControlClientSide(form, this);
      const step = getStepForControl(this);
      if (step) {
        updateStepNextButton(form, step);
      }
      validateFields(form, [this.name, ...parseDependencies(this)]).then(() => {
        if (step) updateStepNextButton(form, step);
      });
    });
  }

  form.addEventListener('submit', async (event) => {
    const state = getFormState(form);
    if (state.isSubmitting) {
      return;
    }

    event.preventDefault();
    const fields = Array.from(form.querySelectorAll('[name]'))
      .filter((control) => !control.matches('[data-validation-skip]'))
      .map((control) => control.name);
    const valid = await validateFields(form, fields);
    if (!valid) {
      const stepperElement = form.closest('[data-form-stepper]');
      if (stepperElement) {
        const steps = Array.from(stepperElement.querySelectorAll('[data-form-step], .content'));
        const currentStep = steps.find((s) => s.classList.contains('active')) || steps[0];
        updateStepNextButton(form, currentStep);
      }
      return;
    }

    state.isSubmitting = true;
    form.dispatchEvent(new CustomEvent('formValidationPassed'));

    if (form.dataset.validationSubmit === 'ajax') {
      state.isSubmitting = false;
      return;
    }

    HTMLFormElement.prototype.submit.call(form);
  });
}

function initPasswordToggles() {
  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');
    if (!toggle) {
      return;
    }

    const input = document.getElementById(toggle.dataset.passwordToggle);
    if (!input) {
      return;
    }

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    toggle.setAttribute('aria-label', isPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
    toggle.querySelector('i')?.classList.toggle('ti-eye', isPassword);
    toggle.querySelector('i')?.classList.toggle('ti-eye-off', !isPassword);
  });
}

function initImagePreviews() {
  document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-image-input]');
    const file = input?.files?.[0];
    const preview = input
      ? document.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`)
      : null;

    if (!file || !preview || !file.type.startsWith('image/')) {
      return;
    }

    const reader = new FileReader();
    reader.addEventListener('load', () => {
      preview.src = String(reader.result || '');
    });
    reader.readAsDataURL(file);
  });
}

function boot() {
  document.querySelectorAll('form[data-form-validate]').forEach(initForm);
  initPasswordToggles();
  initImagePreviews();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}

window.SharedFormValidation = {
  validateFields,
  setFieldError,
  setFieldValid,
  clearFieldError,
  updateStepNextButton,
  isStepValid,
};
