'use strict';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const draftUrl = document.body?.dataset.wizardDraftsUrl;

function activePane(button) {
  return button.closest('.content, .bs-stepper-pane');
}

function fieldsIn(pane) {
  return [...pane.querySelectorAll('input[name], select[name], textarea[name]')]
    .filter((field) => !field.disabled && field.type !== 'button' && field.type !== 'submit');
}

function nextButtons(form) {
  return [...form.querySelectorAll('.btn-next, button[type="submit"]')];
}

function validatePane(pane, displayErrors = false) {
  const invalid = fieldsIn(pane).find((field) => !field.checkValidity());

  if (invalid && displayErrors) invalid.reportValidity();

  return !invalid;
}

function wizardFor(form) {
  return form.closest('.bs-stepper');
}

function hooksFor(form) {
  return window.wizardCreateHooks?.[form.id] || {};
}

function stepperFor(form) {
  const wizard = wizardFor(form);
  if (!wizard || typeof Stepper === 'undefined') return null;

  return wizard.bsStepper || new Stepper(wizard, { linear: false });
}

function stepNumber(pane) {
  const form = pane.closest('form');
  const panes = form ? [...form.querySelectorAll('.content, .bs-stepper-pane')] : [];

  return panes.indexOf(pane) + 1;
}

function csrfTokenFor(form) {
  return csrfToken || form.querySelector('input[name="_token"]')?.value || '';
}

function resourceKeyFor(form) {
  return form.querySelector('input[name="_wizard_resource_key"]')?.value
    || new URL(form.action).pathname;
}

function clearServerErrors(form) {
  form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
  form.querySelectorAll('.wizard-server-error').forEach((error) => error.remove());
}

function fieldNameFromError(key) {
  return key.split('.').reduce((name, segment, index) => (index ? `${name}[${segment}]` : segment), '');
}

function renderServerErrors(form, errors) {
  clearServerErrors(form);

  Object.entries(errors).forEach(([key, messages]) => {
    const field = form.elements.namedItem(fieldNameFromError(key));
    const element = field instanceof RadioNodeList ? field[0] : field;
    if (!element) return;

    element.classList.add('is-invalid');
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback d-block wizard-server-error';
    feedback.textContent = messages[0];
    element.insertAdjacentElement('afterend', feedback);
  });
}

async function validateOnServer(form, pane) {
  if (!draftUrl) return true;

  const payload = new FormData(form);
  const targetMethod = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
  payload.delete('_method');
  payload.append('target_path', new URL(form.action, window.location.origin).pathname);
  payload.append('target_method', targetMethod);
  fieldsIn(pane).forEach((field) => payload.append('fields[]', field.name));

  const response = await fetch(`${draftUrl.replace(/\/$/, '')}/validate-step`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfTokenFor(form), Accept: 'application/json' },
    body: payload,
  });

  if (response.ok) {
    clearServerErrors(form);
    return true;
  }

  if (response.status === 422) {
    const body = await response.json();
    renderServerErrors(form, body.errors || {});
    return false;
  }

  throw new Error('Unable to validate this step.');
}

async function validateCurrentStep(form, pane) {
  const hooks = hooksFor(form);

  if (typeof hooks.validateStep === 'function') {
    return hooks.validateStep({ form, pane, step: stepNumber(pane) });
  }

  return validatePane(pane, true);
}

function addHiddenField(form, name, value) {
  let input = form.querySelector(`input[name="${name}"]`);
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    form.append(input);
  }
  input.value = value;
}

function fillSelectElement(select, value) {
  if (value === undefined || value === null) return;

  const normalized = Array.isArray(value) ? value.map(String) : [String(value)];
  let matched = false;

  [...select.options].forEach((option) => {
    const isSelected = normalized.includes(String(option.value));
    option.selected = isSelected;
    if (isSelected) {
      option.setAttribute('selected', 'selected');
      matched = true;
    } else {
      option.removeAttribute('selected');
    }
  });

  if (!select.multiple) {
    select.value = matched ? normalized[0] : '';
  }

  if (window.jQuery) {
    const $select = window.jQuery(select);
    $select.val(select.multiple ? normalized : (matched ? normalized[0] : ''));
    $select.trigger('change');
    $select.trigger('change.select2');
  }

  select.dispatchEvent(new Event('change', { bubbles: true }));
}

function syncSelects(form) {
  if (!window.jQuery) return;
  form.querySelectorAll('select').forEach((select) => {
    const $select = window.jQuery(select);
    if ($select.data('select2') || select.classList.contains('select2')) {
      const val = select.multiple
        ? [...select.selectedOptions].map((opt) => opt.value)
        : select.value;
      if (val !== '' && val !== null && (!Array.isArray(val) || val.length > 0)) {
        $select.val(val).trigger('change.select2');
      }
    }
  });
}

function fillDraft(form, values, prefix = '') {
  Object.entries(values || {}).forEach(([key, value]) => {
    const name = prefix ? `${prefix}[${key}]` : key;
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      fillDraft(form, value, name);
      return;
    }

    const field = form.elements.namedItem(name);
    if (!field) return;

    const elements = field instanceof RadioNodeList ? [...field] : [field];
    elements.forEach((element) => {
      if (element.type === 'password' || element.type === 'file') return;
      if (element.type === 'checkbox' || element.type === 'radio') {
        element.checked = String(element.value) === String(value) || value === true || value === 1;
      } else if (element.tagName === 'SELECT') {
        fillSelectElement(element, value);
        return;
      } else {
        element.value = Array.isArray(value) ? value : String(value ?? '');
      }
      element.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
}

async function shouldRestoreDraft() {
  if (typeof window.confirmActionAsync === 'function') {
    return await window.confirmActionAsync({
      title: 'مسودة محفوظة',
      text: 'توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟',
      variant: 'primary',
      icon: 'ti-file-pencil',
      confirmText: 'نعم، استعادة المسودة',
      confirmClass: 'btn btn-primary px-4',
      cancelText: 'تجاهل والبدء من جديد',
    });
  }

  return window.confirm('توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟');
}

async function discardDraft(form, resourceKey) {
  const response = await fetch(`${draftUrl}?resource_key=${encodeURIComponent(resourceKey)}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': csrfTokenFor(form),
      Accept: 'application/json',
    },
  });

  if (!response.ok) throw new Error('Unable to discard the draft.');

  const draftId = form.querySelector('input[name="_wizard_draft_id"]');
  if (draftId) draftId.value = '';
}

async function restoreDraft(form) {
  if (!draftUrl) return;

  const resourceKey = resourceKeyFor(form);
  const response = await fetch(`${draftUrl}?resource_key=${encodeURIComponent(resourceKey)}`, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) return;

  const draft = await response.json();
  const values = draft.data || {};

  if (!draft.draft_id) return;

  if (!await shouldRestoreDraft()) {
    await discardDraft(form, resourceKey);
    return;
  }

  await hooksFor(form).beforeRestoreDraft?.({ form, values, localValues: {} });

  addHiddenField(form, '_wizard_draft_id', draft.draft_id);
  addHiddenField(form, '_wizard_resource_key', resourceKey);

  const hasServerErrors = Boolean(
    form.querySelector('.is-invalid, .wizard-server-error') || document.querySelector('.alert-danger')
  );

  if (!hasServerErrors) {
    fillDraft(form, values);
  }
  syncSelects(form);

  const step = Number(draft.step || 1);
  stepperFor(form)?.to(Math.max(0, step - 1));
}

async function saveDraft(form, pane, notify = true) {
  const payload = new FormData(form);
  payload.delete('_method');
  const resourceKey = resourceKeyFor(form);
  payload.set('resource_key', resourceKey);
  payload.set('step', String(stepNumber(pane)));

  const response = await fetch(draftUrl, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfTokenFor(form), Accept: 'application/json' },
    body: payload,
  });

  if (!response.ok) throw new Error('Unable to save the draft.');

  const draft = await response.json();
  addHiddenField(form, '_wizard_draft_id', draft.draft_id);
  addHiddenField(form, '_wizard_resource_key', resourceKey);
  if (notify) window.toastr?.success?.(draft.message);
}

function updateNextState(form) {
  nextButtons(form).forEach((button) => {
    const pane = activePane(button);
    if (pane) button.disabled = !validatePane(pane);
  });
}

function setButtonIcon(button, iconClass, position = 'start') {
  if (!button) return;

  const icon = button.querySelector('i') || document.createElement('i');
  icon.className = `ti ${iconClass} ${position === 'start' ? 'me-1' : 'ms-2'}`;
  icon.setAttribute('aria-hidden', 'true');

  if (position === 'start') {
    button.prepend(icon);
  } else {
    button.append(' ', icon);
  }
}

function actionRowFor(pane, actionButton) {
  const previousButton = pane.querySelector('.btn-prev');
  if (!previousButton) return actionButton.parentElement;

  for (let candidate = actionButton.parentElement; candidate && candidate !== pane; candidate = candidate.parentElement) {
    if (candidate.contains(previousButton) && (candidate.classList.contains('d-flex') || candidate.classList.contains('col-12'))) {
      return candidate;
    }
  }

  return actionButton.parentElement;
}

function existingBackAction(pane, actionButton) {
  for (let candidate = actionButton.parentElement; candidate && candidate !== pane; candidate = candidate.parentElement) {
    const backAction = [...candidate.children].find((child) =>
      child !== actionButton && child.matches?.('a.btn, button.btn') && !child.classList.contains('btn-save-draft')
    );

    if (backAction) return backAction;
  }

  return null;
}

function normalizeActionRow(actions) {
  if (!actions) return;

  actions.classList.remove('col-12', 'justify-content-end', 'gap-2');
  actions.classList.add('mt-4', 'd-flex', 'justify-content-between');

  const previousButton = actions.querySelector('.btn-prev');
  if (!previousButton || actions.querySelector(':scope > .wizard-next-actions')) return;

  const nextActions = document.createElement('div');
  nextActions.className = 'd-flex gap-2 wizard-next-actions';

  [...actions.children]
    .filter((child) => child !== previousButton)
    .forEach((child) => nextActions.append(child));

  actions.append(nextActions);
}

function installDraftButtons(form) {
  form.querySelectorAll('.content, .bs-stepper-pane').forEach((pane) => {
    const draftButtons = [...pane.querySelectorAll('.btn-save-draft')];
    draftButtons.slice(1).forEach((button) => button.remove());

    const actionButton = pane.querySelector('button.btn-next, button.btn-preview, button.btn-execute, button[type="submit"]');
    if (!actionButton) return;

    if (actionButton.type === 'submit') actionButton.classList.add('btn-next');
    let actions = actionButton.parentElement;
    if (!actions || pane.querySelector('.btn-save-draft')) return;

    if (pane && !pane.querySelector('.btn-prev')) {
      const existingBackButton = existingBackAction(pane, actionButton);
      if (existingBackButton) {
        existingBackButton.classList.add('btn-prev');
      } else {
        const previousButton = document.createElement('button');
        previousButton.type = 'button';
        previousButton.className = 'btn btn-secondary btn-prev';
        previousButton.textContent = 'السابق';
        actions.prepend(previousButton);
      }
    }

    const saveButton = document.createElement('button');
    saveButton.type = 'button';
    saveButton.className = 'btn btn-secondary btn-save-draft';
    saveButton.textContent = 'حفظ كمسودة';
    actions.insertBefore(saveButton, actionButton);

    actions = actionRowFor(pane, actionButton);
    normalizeActionRow(actions);

    const previousButton = actions.querySelector('.btn-prev');
    setButtonIcon(previousButton, 'ti-arrow-left');
    setButtonIcon(saveButton, 'ti-device-floppy');
    setButtonIcon(
      actionButton,
      actionButton.classList.contains('btn-submit') || actionButton.type === 'submit'
        ? 'ti-check'
        : 'ti-arrow-right',
      actionButton.classList.contains('btn-submit') || actionButton.type === 'submit' ? 'start' : 'end',
    );
  });
}

function initializeForm(form) {
  if (
    form.dataset.wizardEnhanced ||
    form.dataset.formValidate !== undefined ||
    !wizardFor(form) ||
    form.querySelector('input[name="_method"][value="PUT"], input[name="_method"][value="PATCH"]')
  ) return;
  form.dataset.wizardEnhanced = 'true';
  installDraftButtons(form);
  form.querySelectorAll('.btn-prev').forEach((button) => {
    const pane = activePane(button);
    if (pane && stepNumber(pane) === 1) button.disabled = false;
  });
  updateNextState(form);
  restoreDraft(form)
    .catch(() => window.toastr?.error?.('تعذر التعامل مع المسودة. يرجى المحاولة مرة أخرى.'))
    .finally(() => updateNextState(form));

  const updateValidationState = () => updateNextState(form);

  form.addEventListener('input', updateValidationState);
  form.addEventListener('change', updateValidationState);
  if (window.jQuery) {
    window.jQuery(form).on('change', 'select, input, textarea', updateValidationState);
    window.jQuery(form).on('change.select2', 'select', updateValidationState);
  }
  wizardFor(form)?.addEventListener('shown.bs-stepper', updateValidationState);
}

function initialize() {
  document.querySelectorAll('.bs-stepper form').forEach(initializeForm);

  document.addEventListener('click', async (event) => {
    const draftButton = event.target.closest('.btn-save-draft');
    const nextButton = event.target.closest('.btn-next');
    const previousButton = event.target.closest('.btn-prev');
    const button = draftButton || nextButton || previousButton;
    const form = button?.closest('form[data-wizard-enhanced="true"]');
    if (!form) return;

    const pane = activePane(button);
    if (!pane) return;

    if (draftButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      try {
        await saveDraft(form, pane);
      } catch {
        window.toastr?.error?.('تعذر حفظ المسودة. يرجى المحاولة مرة أخرى.');
      }
      return;
    }

    if (previousButton) {
      event.preventDefault();
      event.stopImmediatePropagation();
      if (stepNumber(pane) === 1) {
        const backUrl = previousButton instanceof HTMLAnchorElement
          ? previousButton.href
          : form.dataset.wizardBackUrl || form.action;
        window.location.assign(backUrl);
      } else {
        stepperFor(form)?.previous();
      }
      return;
    }

    if (nextButton.classList.contains('btn-submit') || nextButton.type === 'submit') {
      if (!await validateCurrentStep(form, pane)) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    if (!await validateCurrentStep(form, pane)) return;

    nextButton.disabled = true;
    try {
      if (await validateOnServer(form, pane)) {
        await hooksFor(form).afterStepValidated?.({ form, pane, step: stepNumber(pane) });
        stepperFor(form)?.next();
      }
    } catch {
      window.toastr?.error?.('تعذر التحقق من بيانات هذه الخطوة.');
    } finally {
      updateNextState(form);
    }
  }, true);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initialize);
} else {
  initialize();
}
