'use strict';

const INPUT_VALIDATION_DEBOUNCE_MS = 250;
const formStates = new WeakMap();

function getFormState(form) {
  if (!formStates.has(form)) {
    formStates.set(form, {
      controllers: new Map(),
      fieldVersions: new Map(),
      successfulValidations: new Map(),
      isSubmitting: false,
      isNavigating: false,
      isValidatingStep: false,
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
  const normField = normalizeFieldName(fieldName);
  const normReq = normalizeFieldName(requestedName);
  return normField === normReq || normReq.startsWith(normField + '.') || normField.startsWith(normReq + '.');
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

function validationSignature(form, fields) {
  const values = [...new Set(fields.map(normalizeFieldName))]
    .sort()
    .map((fieldName) => {
      const controls = findControls(form, fieldName);
      const fieldValues = controls.map((control) => {
        if (control.type === 'file') {
          return Array.from(control.files || []).map((file) => [file.name, file.size, file.lastModified]);
        }

        if (control.type === 'checkbox' || control.type === 'radio') {
          return [control.value, control.checked];
        }

        return control.value;
      });

      return [fieldName, fieldValues];
    });

  return JSON.stringify(values);
}

function stateTargets(control) {
  const targets = [control];
  const inputGroup = control.closest('.input-group');

  if (inputGroup) {
    targets.push(inputGroup);
    inputGroup.querySelectorAll('[data-password-toggle]').forEach((toggle) => targets.push(toggle));
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
      error.setAttribute('hidden', 'true');
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
        feedback.setAttribute('hidden', 'true');
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
      error.removeAttribute('hidden');
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
        feedback.removeAttribute('hidden');
        feedback.style.display = 'block';
      }
    }
  });

  if (wrappers.length === 0 && findControls(form, fieldName).length === 0) {
    const rootName = fieldName.split('.')[0];
    const rootWrappers = findFieldWrappers(form, rootName);
    if (rootWrappers.length > 0) {
      setFieldError(form, rootName, message);
      return;
    }

    const activeStep = form.querySelector('[data-form-step].active') || form.querySelector('[data-form-step]');
    const stepError = activeStep?.querySelector('[data-form-error]');
    if (stepError) {
      stepError.textContent = message;
      stepError.hidden = false;
      stepError.removeAttribute('hidden');
      stepError.style.display = 'block';
    }
  }
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

function passwordConfirmationDependencies(control) {
  const name = String(control.name || '');
  const suffix = '_confirmation';

  return name.endsWith(suffix) ? [name.slice(0, -suffix.length)] : [];
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

function isCreatePage() {
  return window.location.pathname.split('/').filter(Boolean).includes('create');
}

function isCreateForm(form) {
  if (!isCreatePage() || form.closest('template')) {
    return false;
  }

  const spoofedMethod = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
  const method = (spoofedMethod || form.method || 'GET').toUpperCase();

  return method === 'POST' && Boolean(form.action);
}

function isAuthForm(form) {
  if (!form) {
    return false;
  }

  if (form.id === 'formAuthentication' || form.id === 'twoStepsForm') {
    return true;
  }

  if (form.closest('.auth-split-container, .tenant-login-container, .auth-form-side, [data-auth-form]')) {
    return true;
  }

  const action = form.getAttribute('action') || '';
  return /\b(login|register|forgot-password|reset-password|password\/confirm|two-factor)\b/.test(action);
}

function draftButtonFromTemplate() {
  const template = document.querySelector('template[data-create-draft-button-template]');
  return template?.content.firstElementChild?.cloneNode(true) || null;
}

function installDraftButton(container, beforeButton) {
  if (!container || container.querySelector('[data-wizard-draft]')) {
    return;
  }

  const draftButton = draftButtonFromTemplate();
  if (!draftButton) {
    return;
  }

  container.insertBefore(draftButton, beforeButton);
}

function prepareCreateFormContract(form) {
  if (!isCreateForm(form)) {
    return;
  }

  const draftUrl = document.body?.dataset.wizardDraftsUrl;
  if (!draftUrl) {
    return;
  }

  form.dataset.formValidate = '';
  form.dataset.draftUrl ||= draftUrl;
  form.dataset.draftFileUrl ||= `${draftUrl.replace(/\/$/, '')}/file`;
  form.dataset.draftResourceKey ||= `${document.body.dataset.appBoundary || 'app'}:${new URL(form.action).pathname}`;

  const steps = [...form.querySelectorAll('[data-form-step], .content, .bs-stepper-pane')];
  if (steps.length > 0) {
    steps.forEach((step) => {
      const primaryButton = step.querySelector('[data-wizard-next], .btn-next, button[type="submit"]');
      if (!primaryButton) return;
      installDraftButton(primaryButton.parentElement, primaryButton);
    });
    return;
  }

  const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
  if (submitButton) {
    installDraftButton(submitButton.parentElement, submitButton);
  }
}

function flattenDraftFiles(files, prefix = '') {
  return Object.entries(files || {}).flatMap(([key, value]) => {
    const fileKey = prefix ? `${prefix}.${key}` : key;
    if (value && typeof value === 'object' && !Array.isArray(value) && !value.name) {
      return flattenDraftFiles(value, fileKey);
    }
    if (Array.isArray(value)) {
      return value.flatMap((item, index) => item?.name ? [{ ...item, key: item.key || `${fileKey}.${index}` }] : []);
    }
    return value?.name ? [{ ...value, key: value.key || fileKey }] : [];
  });
}

function renderArchivedDraftFiles(form, files) {
  form.querySelectorAll('[data-archived-draft-files]').forEach((element) => element.remove());

  flattenDraftFiles(files).forEach((file) => {
    const rootName = String(file.key || '').split('.')[0];
    const input = form.querySelector(`input[type="file"][name="${CSS.escape(rootName)}"], input[type="file"][name="${CSS.escape(rootName)}[]"]`);
    if (!input) return;

    let list = input.parentElement?.querySelector('[data-archived-draft-files]');
    if (!list) {
      list = document.createElement('div');
      list.dataset.archivedDraftFiles = '';
      list.className = 'd-flex flex-column gap-2 mt-2';
      input.parentElement?.append(list);
    }

    const item = document.createElement('div');
    item.className = 'd-flex align-items-center justify-content-between gap-2 rounded border p-2';
    const name = document.createElement('span');
    name.className = 'text-truncate';
    name.textContent = file.name;
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-sm btn-icon btn-outline-danger';
    remove.dataset.draftFileKey = file.key;
    remove.setAttribute('aria-label', `حذف الملف المؤرشف ${file.name}`);
    remove.innerHTML = '<i class="ti ti-trash" aria-hidden="true"></i>';
    item.append(name, remove);
    list.append(item);
  });
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

  let canUseForm = false;
  form.inert = true;
  form.setAttribute('aria-busy', 'true');

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
      canUseForm = true;
      return;
    }

    let shouldRestore = true;
    if (window.Swal && !form.hasAttribute('data-draft-auto-restore')) {
      const result = await window.Swal.fire({
        title: 'مسودة محفوظة',
        text: 'توجد مسودة محفوظة مسبقاً. هل ترغب في استعادتها؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، استعادة المسودة',
        cancelButtonText: 'تجاهل والبدء من جديد',
        customClass: {
          popup: 'custom-popup',
          title: 'custom-title text-center',
          htmlContainer: 'custom-text text-center',
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-secondary',
        },
        buttonsStyling: false,
      });
      shouldRestore = result.isConfirmed;
    }

    if (!shouldRestore) {
      await discardDraft(form);
      canUseForm = true;
      return;
    }

    addHiddenField(form, '_wizard_draft_id', draft.draft_id);
    addHiddenField(form, '_wizard_resource_key', draftResourceKey(form));
    restoreDraftValues(form, draft.data || {});
    renderArchivedDraftFiles(form, draft.files || {});
    form.dispatchEvent(new CustomEvent('wizardDraftRestored', {
      detail: { data: draft.data || {}, files: draft.files || {}, draftId: draft.draft_id }
    }));
    getFormState(form).stepper?.to(Math.max(1, Number(draft.step || 1)));
    canUseForm = true;
  } catch (_) {
    window.toastr?.error?.('تعذر التعامل مع المسودة. أعد تحميل الصفحة للمحاولة مرة أخرى قبل إدخال بيانات جديدة.');
  } finally {
    form.removeAttribute('aria-busy');
    if (canUseForm) {
      form.inert = false;
    }
  }
}

function initDrafts(form) {
  const draftUrl = form.dataset.draftUrl;
  if (!draftUrl) {
    return;
  }

  form.addEventListener('click', async (event) => {
    const removeFileButton = event.target.closest('[data-draft-file-key]');
    if (removeFileButton) {
      event.preventDefault();
      removeFileButton.disabled = true;
      try {
        const response = await fetch(form.dataset.draftFileUrl || `${draftUrl.replace(/\/$/, '')}/file`, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            resource_key: draftResourceKey(form),
            file_key: removeFileButton.dataset.draftFileKey,
          }),
        });
        if (!response.ok) throw new Error('Unable to remove archived file.');
        removeFileButton.closest('.d-flex')?.remove();
      } catch (_) {
        removeFileButton.disabled = false;
        window.toastr?.error?.('تعذر حذف الملف المؤرشف. يرجى المحاولة مرة أخرى.');
      }
      return;
    }

    const button = event.target.closest('[data-wizard-draft]');
    if (!button) {
      return;
    }

    event.preventDefault();
    button.disabled = true;
    try {
      const data = new FormData(form);
      data.delete('_method');
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
    if (control.type === 'email') {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        setFieldError(form, name, 'البريد الإلكتروني غير صحيح');
        return false;
      }
    } else if (name.toLowerCase() === 'email' && !trimmed.includes('@')) {
      if (!/^(?:05\d{8}|\+?\d{9,15}|[a-zA-Z0-9._-]{3,})$/.test(trimmed)) {
        setFieldError(form, name, 'البريد الإلكتروني أو رقم الجوال غير صحيح');
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
    const [passwordField] = passwordConfirmationDependencies(control);
    if (passwordField) {
      const pwd = form.querySelector(`[name="${CSS.escape(passwordField)}"]`)?.value;
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
      const [passwordField] = passwordConfirmationDependencies(control);
      if (passwordField) {
        const pwd = form.querySelector(`[name="${CSS.escape(passwordField)}"]`)?.value;
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

  nextButtons.forEach((btn) => {
    btn.disabled = false;
    btn.removeAttribute('disabled');
    btn.classList.remove('disabled');
  });
}

async function validateFields(form, fieldNames, { force = false } = {}) {
  const fields = [...new Set(fieldNames.filter(Boolean))];
  let validationUrl = form.dataset.validationUrl;

  if (!validationUrl || fields.length === 0) {
    return true;
  }

  try {
    const parsed = new URL(validationUrl, window.location.origin);
    if (parsed.origin !== window.location.origin && (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1')) {
      validationUrl = parsed.pathname + parsed.search;
    }
  } catch (_) {
    // Keep validationUrl as-is if parsing fails
  }

  const state = getFormState(form);
  const requestKey = fields.map(normalizeFieldName).sort().join('|');
  const requestSignature = validationSignature(form, fields);
  if (!force && state.successfulValidations.get(requestKey) === requestSignature) {
    return true;
  }
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

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content ||
    form.querySelector('input[name="_token"]')?.value ||
    '';

  const data = new FormData(form);
  form.querySelectorAll('input[type="file"][name]').forEach((fileInput) => {
    const isRequestedFileField = fields.some((field) => fieldMatches(fileInput.name, field));
    if (!isRequestedFileField) {
      data.delete(fileInput.name);
    }
  });
  if (csrfToken) {
    data.set('_token', csrfToken);
  }
  data.delete('_method');
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
        'X-CSRF-TOKEN': csrfToken,
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
      state.lastValidationErrors = null;
      fields.forEach((field) => setFieldValid(form, field));
      state.successfulValidations.set(requestKey, requestSignature);
      return true;
    }

    state.successfulValidations.delete(requestKey);

    const errors = payload.errors || {};
    state.lastValidationErrors = errors;
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

    state.successfulValidations.delete(requestKey);
    setFieldError(form, fields[0], 'تعذر التحقق من الحقل الآن. يرجى المحاولة مرة أخرى.');
    return false;
  } finally {
    if (state.controllers.get(requestKey) === controller) {
      state.controllers.delete(requestKey);
    }
  }
}

function initStepper(form) {
  const stepperElement = form.closest('[data-form-stepper]');
  if (!stepperElement || typeof window.Stepper !== 'function') {
    return;
  }

  const state = getFormState(form);
  state.stepper = new window.Stepper(stepperElement, { linear: false });

  const getSteps = () => Array.from(stepperElement.querySelectorAll('[data-form-step]'));

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

  const moveTo = (targetIndex) => {
    if (state.isNavigating) {
      return;
    }

    const currentIndex = getCurrentIndex();
    if (targetIndex === currentIndex) {
      return;
    }

    state.isNavigating = true;
    const steps = getSteps();
    try {
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
    async (event) => {
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
        if (state.isValidatingStep) {
          return;
        }

        const steps = getSteps();
        const currentStep = nextButton.closest('[data-form-step]');
        const currentIndex = steps.indexOf(currentStep);
        if (!currentStep || currentIndex < 0) {
          return;
        }
        const stepFields = validationFieldsFromStep(currentStep);

        state.isValidatingStep = true;
        nextButton.setAttribute('aria-busy', 'true');

        try {
          const isValid = await validateFields(form, stepFields, { force: true });
          if (isValid) {
            moveTo(currentIndex + 1);
          } else {
            const visibleError = currentStep.querySelector(
              '[data-form-error]:not([hidden]):not([style*="display: none"]), .invalid-feedback:not([hidden]):not([style*="display: none"]), .is-invalid [data-form-error]'
            );
            const serverErrors = state.lastValidationErrors ? Object.values(state.lastValidationErrors).flat().filter(Boolean) : [];
            const boundaryInput = currentStep.querySelector('input[name="boundary_geojson"]');
            const boundaryError = (boundaryInput && !boundaryInput.value) ? 'برجاء تحديد حدود العقار علي الصورة الجوية.' : null;
            const errorMessage = visibleError?.textContent?.trim() || boundaryError || serverErrors[0] || 'يرجى تصحيح الأخطاء قبل الانتقال للخطوة التالية.';
            if (window.toastr?.error) {
              window.toastr.error(errorMessage);
            }
            if (visibleError && typeof visibleError.scrollIntoView === 'function') {
              visibleError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }
        } finally {
          state.isValidatingStep = false;
          nextButton.removeAttribute('aria-busy');
          updateStepNextButton(form, currentStep);
        }
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

  form.addEventListener('stepChanged', () => {
    initSelect2Controls(form);
  });

  // Initial check on load
  setTimeout(updateCurrentStepButton, 150);
}

function initSelect2Controls(form, retryCount = 0) {
  if (!window.jQuery || typeof window.jQuery.fn.select2 !== 'function') {
    if (retryCount < 10) {
      setTimeout(() => initSelect2Controls(form, retryCount + 1), 100);
    }
    return;
  }

  const $ = window.jQuery;
  $(form)
    .find('select.select2, select[data-form-control]')
    .not('.select2-hidden-accessible, [data-select2-ignore]')
    .each(function () {
      const $select = $(this);
      const $modal = $select.closest('.modal');

      $select.select2({
        placeholder: $select.data('placeholder') || $select.attr('placeholder') || 'اختر',
        allowClear: !$select.prop('required'),
        width: '100%',
        dir: 'rtl',
        minimumResultsForSearch: $select.data('select2-search') === false || $select.data('select2-search') === 'false' ? Infinity : 0,
        dropdownParent: $modal.length ? $modal : undefined
      });
    });
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
  initSelect2Controls(form);

  const getStepForControl = (control) => control.closest('[data-form-step], .content');
  const validationFieldsForControl = (control) => [
    ...new Set([
      control.name,
      ...parseDependencies(control),
      ...passwordConfirmationDependencies(control),
    ]),
  ];
  const activeControlValidations = new WeakMap();
  const validationTimers = new WeakMap();
  const validateImmediately = (control, step, { force = false } = {}) => {
    if (!control.name || control.matches('[data-validation-skip], [data-datatable-filter]')) {
      return Promise.resolve(true);
    }

    const fields = validationFieldsForControl(control);
    const signature = validationSignature(form, fields);
    const activeValidation = activeControlValidations.get(control);

    if (activeValidation?.signature === signature && activeValidation.pending) {
      return activeValidation.promise;
    }

    const validation = validateFields(form, fields, { force })
      .then((isValid) => {
        if (step) updateStepNextButton(form, step);
        return isValid;
      })
      .finally(() => {
        const currentValidation = activeControlValidations.get(control);
        if (currentValidation?.promise === validation) {
          currentValidation.pending = false;
        }
      });

    activeControlValidations.set(control, { signature, pending: true, promise: validation });
    return validation;
  };
  const clearScheduledValidation = (control) => {
    const timer = validationTimers.get(control);
    if (timer) {
      clearTimeout(timer);
      validationTimers.delete(control);
    }
  };
  const scheduleValidation = (control, step) => {
    clearScheduledValidation(control);
    const timer = setTimeout(() => {
      validationTimers.delete(control);
      validateImmediately(control, step);
    }, INPUT_VALIDATION_DEBOUNCE_MS);
    validationTimers.set(control, timer);
  };

  const formControls = Array.from(form.querySelectorAll('[data-form-control][name], input[name], select[name], textarea[name]'));
  const hasControlValue = (control) => {
    if (control.type === 'checkbox' || control.type === 'radio') {
      return control.checked;
    }

    if (control.type === 'file') {
      return (control.files?.length || 0) > 0;
    }

    if (control.tagName === 'SELECT' && control.multiple) {
      return Array.from(control.selectedOptions).some((option) => option.value !== '');
    }

    return String(control.value ?? '').trim() !== '';
  };
  const isAuth = isAuthForm(form);
  let isPageReady = !isAuth;
  if (isAuth) {
    setTimeout(() => {
      isPageReady = true;
    }, 400);
  }

  const userInteractedControls = new WeakSet();
  const shouldValidateInteraction = (control, event) => event?.isTrusted || hasControlValue(control);

  formControls.forEach((control) => {
    if (['submit', 'button', 'reset', 'hidden'].includes(control.type)) {
      return;
    }

    control.addEventListener('focus', () => {
      userInteractedControls.add(control);
    });

    control.addEventListener('input', (event) => {
      if (isAuth && !isPageReady) return;
      if (isAuth && !event.isTrusted && !userInteractedControls.has(control)) return;

      if (!shouldValidateInteraction(control, event)) return;

      if (isAuth) {
        if (control.classList.contains('is-invalid') || control.getAttribute('aria-invalid') === 'true') {
          validateControlClientSide(form, control);
        }
        return;
      }

      // 1. Instant client-side feedback
      validateControlClientSide(form, control);

      // 2. Reactively update the Next button disabled state
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }
      scheduleValidation(control, step);
    });

    control.addEventListener('change', (event) => {
      if (isAuth && !isPageReady) return;
      if (isAuth && !event.isTrusted && !userInteractedControls.has(control)) return;

      userInteractedControls.add(control);
      if (!shouldValidateInteraction(control, event)) return;

      clearScheduledValidation(control);
      validateControlClientSide(form, control);
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }
      validateImmediately(control, step);
    });

    control.addEventListener('blur', () => {
      if (isAuth && !isPageReady) return;
      if (isAuth && !userInteractedControls.has(control)) return;

      clearScheduledValidation(control);
      validateControlClientSide(form, control);
      const step = getStepForControl(control);
      if (step) {
        updateStepNextButton(form, step);
      }
      validateImmediately(control, step, { force: true });
    });
  });

  if (window.jQuery) {
    const validatedSelectSelector = 'select[data-form-control][name]:not([data-validation-skip])';

    window.jQuery(form).on('change.sharedFormValidation', validatedSelectSelector, function (event) {
      if (isAuth && !isPageReady) return;
      if (!shouldValidateInteraction(this, event.originalEvent)) return;

      validateControlClientSide(form, this);
      const step = getStepForControl(this);
      if (step) updateStepNextButton(form, step);
      validateImmediately(this, step);
    });

    window.jQuery(form).on('select2:close.sharedFormValidation', validatedSelectSelector, function () {
      if (isAuth && !isPageReady) return;
      validateControlClientSide(form, this);
      const step = getStepForControl(this);
      if (step) updateStepNextButton(form, step);
      validateImmediately(this, step, { force: true });
    });
  }

  const hasInitialValue = (control) => {
    if (
      control.disabled
      || ['submit', 'button', 'reset', 'hidden'].includes(control.type)
      || control.matches('[data-validation-skip], [data-datatable-filter]')
    ) {
      return false;
    }

    return hasControlValue(control);
  };

  const initialFieldsByStep = new Map();
  if (!isAuth) {
    formControls.filter(hasInitialValue).forEach((control) => {
      const step = getStepForControl(control);
      if (!step) return;
      const fields = initialFieldsByStep.get(step) || new Set();
      fields.add(control.name);
      initialFieldsByStep.set(step, fields);
    });

    initialFieldsByStep.forEach((fields, step) => {
      validateFields(form, Array.from(fields)).then(() => {
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
        const steps = Array.from(stepperElement.querySelectorAll('[data-form-step]'));
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

let passwordTogglesInitialized = false;

function initPasswordToggles() {
  if (passwordTogglesInitialized) {
    return;
  }
  passwordTogglesInitialized = true;

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
  document.querySelectorAll('form').forEach(prepareCreateFormContract);
  document.querySelectorAll('form[data-form-validate]').forEach(initForm);
  initPasswordToggles();
  initImagePreviews();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}

window.addEventListener('load', () => {
  document.querySelectorAll('form[data-form-validate]').forEach((form) => initSelect2Controls(form));
});

window.SharedFormValidation = {
  validateFields,
  setFieldError,
  setFieldValid,
  clearFieldError,
  updateStepNextButton,
  isStepValid,
};
