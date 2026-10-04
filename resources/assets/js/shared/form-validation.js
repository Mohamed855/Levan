'use strict';

const formStates = new WeakMap();

function getFormState(form) {
  if (!formStates.has(form)) {
    formStates.set(form, {
      controllers: new Map(),
      fieldVersions: new Map(),
      timers: new Map(),
      isSubmitting: false,
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
  return Array.from(form.querySelectorAll('[data-form-control][name]')).filter((control) =>
    fieldMatches(control.name, fieldName)
  );
}

function clearFieldError(form, fieldName) {
  findFieldWrappers(form, fieldName).forEach((wrapper) => {
    wrapper.classList.remove('is-invalid');
    const error = wrapper.querySelector('[data-form-error]');
    if (error) {
      error.textContent = '';
      error.hidden = true;
    }
  });

  findControls(form, fieldName).forEach((control) => {
    control.classList.remove('is-invalid');
    control.setAttribute('aria-invalid', 'false');
    control.nextElementSibling?.classList?.remove('is-invalid');
  });
}

function setFieldError(form, fieldName, message) {
  const wrappers = findFieldWrappers(form, fieldName);

  wrappers.forEach((wrapper) => {
    wrapper.classList.add('is-invalid');
    const error = wrapper.querySelector('[data-form-error]');
    if (error) {
      error.textContent = message;
      error.hidden = false;
    }
  });

  findControls(form, fieldName).forEach((control) => {
    control.classList.add('is-invalid');
    control.setAttribute('aria-invalid', 'true');
    control.nextElementSibling?.classList?.add('is-invalid');
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

function validationFieldsFromStep(step) {
  try {
    const fields = JSON.parse(step?.dataset.validationFields || '[]');
    return Array.isArray(fields) ? fields.filter(Boolean) : [];
  } catch (_) {
    return [];
  }
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
        'X-Requested-With': 'XMLHttpRequest'
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

    fields.forEach((field) => clearFieldError(form, field));

    if (response.ok && payload.success) {
      return true;
    }

    const errors = payload.errors || {};
    Object.entries(errors).forEach(([field, messages]) => {
      const message = Array.isArray(messages) ? messages[0] : messages;
      if (message) {
        setFieldError(form, field, message);
      }
    });

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
      validateFields(form, fields);
      state.timers.delete(key);
    }, 350)
  );
}

function initStepper(form) {
  const stepperElement = form.closest('[data-form-stepper]');
  if (!stepperElement || typeof window.Stepper !== 'function') {
    return;
  }

  const state = getFormState(form);
  state.stepper = new window.Stepper(stepperElement, { linear: false });

  const getCurrentIndex = () => {
    const steps = Array.from(stepperElement.querySelectorAll('[data-form-step]'));
    const activeIndex = steps.findIndex((step) => step.classList.contains('active'));
    return activeIndex >= 0 ? activeIndex : 0;
  };

  const moveTo = async (targetIndex) => {
    const currentIndex = getCurrentIndex();
    if (targetIndex === currentIndex) {
      return;
    }

    if (targetIndex > currentIndex) {
      const currentStep = stepperElement.querySelectorAll('[data-form-step]')[currentIndex];
      const valid = await validateFields(form, validationFieldsFromStep(currentStep));
      if (!valid) {
        return;
      }
    }

    state.stepper.to(targetIndex + 1);
  };

  stepperElement.addEventListener(
    'click',
    (event) => {
      const trigger = event.target.closest('.step-trigger');
      if (!trigger) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      const targetId = trigger.closest('.step')?.dataset.target?.replace(/^#/, '');
      const targetIndex = Array.from(stepperElement.querySelectorAll('[data-form-step]')).findIndex(
        (step) => step.id === targetId
      );
      if (targetIndex >= 0) {
        moveTo(targetIndex);
      }
    },
    true
  );

  stepperElement.addEventListener('click', (event) => {
    const nextButton = event.target.closest('[data-wizard-next]');
    if (nextButton) {
      event.preventDefault();
      moveTo(getCurrentIndex() + 1);
      return;
    }

    const previousButton = event.target.closest('[data-wizard-prev]');
    if (!previousButton) {
      return;
    }

    event.preventDefault();
    const currentIndex = getCurrentIndex();
    if (currentIndex > 0) {
      state.stepper.previous();
    } else if (previousButton.dataset.wizardBack === 'true') {
      window.history.back();
    }
  });
}

function initForm(form) {
  if (form.dataset.formValidationInitialized === 'true') {
    return;
  }

  form.dataset.formValidationInitialized = 'true';
  initStepper(form);

  form.querySelectorAll('[data-form-control][name]').forEach((control) => {
    const fields = () => [control.name, ...parseDependencies(control)];
    const immediateEvents = ['select', 'file', 'checkbox', 'radio'];
    const shouldValidateOnChange = immediateEvents.includes(control.type) || control.tagName === 'SELECT';

    if (!shouldValidateOnChange) {
      control.addEventListener('input', () => scheduleValidation(form, fields()));
    }

    control.addEventListener('change', () => validateFields(form, fields()));
    control.addEventListener('blur', () => validateFields(form, fields()));
  });

  form.addEventListener('submit', async (event) => {
    const state = getFormState(form);
    if (state.isSubmitting) {
      return;
    }

    event.preventDefault();
    const fields = Array.from(form.querySelectorAll('[data-form-control][name]')).map((control) => control.name);
    const valid = await validateFields(form, fields);
    if (!valid) {
      return;
    }

    state.isSubmitting = true;
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

window.SharedFormValidation = { validateFields };
