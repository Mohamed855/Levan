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

function stepperFor(form) {
  const wizard = wizardFor(form);
  if (!wizard || typeof Stepper === 'undefined') return null;

  return wizard.bsStepper || new Stepper(wizard, { linear: false });
}

function stepNumber(pane) {
  return [...pane.parentElement.children].indexOf(pane) + 1;
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
  payload.append('target_path', new URL(form.action).pathname);
  fieldsIn(pane).forEach((field) => payload.append('fields[]', field.name));

  const response = await fetch(`${draftUrl.replace(/\/$/, '')}/validate-step`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
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
      } else {
        element.value = Array.isArray(value) ? value : String(value ?? '');
      }
      element.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
}

async function restoreDraft(form) {
  if (!draftUrl) return;

  const resourceKey = new URL(form.action).pathname;
  const response = await fetch(`${draftUrl}?resource_key=${encodeURIComponent(resourceKey)}`, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) return;

  const draft = await response.json();
  if (!draft.draft_id) return;

  addHiddenField(form, '_wizard_draft_id', draft.draft_id);
  addHiddenField(form, '_wizard_resource_key', resourceKey);
  fillDraft(form, draft.data);

  const panes = [...form.querySelectorAll('.content, .bs-stepper-pane')];
  if (draft.step > 1 && panes[draft.step - 1]) stepperFor(form)?.to(draft.step - 1);
}

async function saveDraft(form, pane) {
  const payload = new FormData(form);
  const resourceKey = new URL(form.action).pathname;
  payload.set('resource_key', resourceKey);
  payload.set('step', String(stepNumber(pane)));

  const response = await fetch(draftUrl, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
    body: payload,
  });

  if (!response.ok) throw new Error('Unable to save the draft.');

  const draft = await response.json();
  addHiddenField(form, '_wizard_draft_id', draft.draft_id);
  addHiddenField(form, '_wizard_resource_key', resourceKey);
  window.toastr?.success?.(draft.message);
}

function updateNextState(form) {
  nextButtons(form).forEach((button) => {
    const pane = activePane(button);
    if (pane) button.disabled = !validatePane(pane);
  });
}

function installDraftButtons(form) {
  form.querySelectorAll('.content, .bs-stepper-pane').forEach((pane) => {
    const actionButton = pane.querySelector('button.btn-next, button.btn-preview, button.btn-execute, button[type="submit"]');
    if (!actionButton) return;

    if (actionButton.type === 'submit') actionButton.classList.add('btn-next');
    const actions = actionButton.parentElement;
    if (!actions || actions.querySelector('.btn-save-draft')) return;

    if (pane && !pane.querySelector('.btn-prev')) {
      const previousButton = document.createElement('button');
      previousButton.type = 'button';
      previousButton.className = 'btn btn-secondary btn-prev';
      previousButton.textContent = 'السابق';
      actions.prepend(previousButton);
    }

    const saveButton = document.createElement('button');
    saveButton.type = 'button';
    saveButton.className = 'btn btn-secondary btn-save-draft';
    saveButton.textContent = 'حفظ كمسودة';
    actions.insertBefore(saveButton, actionButton);
  });
}

function initializeForm(form) {
  if (
    form.dataset.wizardEnhanced ||
    !wizardFor(form) ||
    form.closest('#subscription-wizard') ||
    form.querySelector('input[name="_method"][value="PUT"], input[name="_method"][value="PATCH"]')
  ) return;
  form.dataset.wizardEnhanced = 'true';
  installDraftButtons(form);
  form.querySelectorAll('.btn-prev').forEach((button) => {
    const pane = activePane(button);
    if (pane && stepNumber(pane) === 1) button.disabled = false;
  });
  updateNextState(form);
  restoreDraft(form).finally(() => updateNextState(form));

  form.addEventListener('input', () => updateNextState(form));
  form.addEventListener('change', () => updateNextState(form));
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
        window.location.assign(form.action);
      } else {
        stepperFor(form)?.previous();
      }
      return;
    }

    if (nextButton.classList.contains('btn-submit') || nextButton.type === 'submit') {
      if (!validatePane(pane, true)) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    if (!validatePane(pane, true)) return;

    nextButton.disabled = true;
    try {
      if (await validateOnServer(form, pane)) stepperFor(form)?.next();
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
