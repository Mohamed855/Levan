'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('assignment-create-form');
  if (!form) return;

  const selectedProperties = new Map();
  const propertyInputs = document.getElementById('selected-property-inputs');
  const propertyCards = document.getElementById('selected-properties-cards');
  const propertyCount = document.getElementById('selected-properties-count');
  const fileInputs = [
    { input: document.getElementById('general_documents'), list: document.getElementById('general-documents-cards'), label: 'مستند عام' },
    { input: document.getElementById('additional_attachments'), list: document.getElementById('additional-attachments-cards'), label: 'مرفق إضافي' },
  ];
  const accumulatedFiles = new Map();
  const restoredDraftFiles = new Map();
  const restoredSingleFiles = new Map();

  function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
  }

  function renderPropertyCards(target = propertyCards, removable = true) {
    if (!selectedProperties.size) {
      target.innerHTML = '<p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>';
      return;
    }

    target.innerHTML = [...selectedProperties.values()].map(property => `
      <article class="assignment-file-card assignment-property-card">
        <img class="assignment-property-card__image" src="${escapeHtml(property.image)}" alt="">
        <span class="assignment-file-card__name" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</span>
        ${removable ? `<button type="button" class="btn btn-sm btn-icon btn-label-danger" data-remove-property="${property.id}" aria-label="إزالة ${escapeHtml(property.name)}"><i class="ti ti-x"></i></button>` : ''}
      </article>`).join('');
  }

  function syncProperties() {
    propertyInputs.innerHTML = [...selectedProperties.keys()].map(id => `<input type="hidden" name="property_ids[]" value="${id}" data-form-control>`).join('');
    propertyCount.textContent = String(selectedProperties.size);
    renderPropertyCards();
    document.querySelectorAll('.property-selector').forEach(checkbox => { checkbox.checked = selectedProperties.has(String(checkbox.value)); });
    form.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function propertyFromCheckbox(checkbox) {
    return { id: String(checkbox.value), name: checkbox.dataset.name || '', number: checkbox.dataset.number || '', image: checkbox.dataset.image || '' };
  }

  document.addEventListener('change', event => {
    const checkbox = event.target.closest('.property-selector');
    if (!checkbox) return;
    const property = propertyFromCheckbox(checkbox);
    if (checkbox.checked) selectedProperties.set(property.id, property); else selectedProperties.delete(property.id);
    syncProperties();
  });

  document.addEventListener('change', event => {
    if (event.target.id !== 'select-all-properties') return;
    document.querySelectorAll('#assignment-properties-table .property-selector').forEach(checkbox => {
      checkbox.checked = event.target.checked;
      const property = propertyFromCheckbox(checkbox);
      if (checkbox.checked) selectedProperties.set(property.id, property); else selectedProperties.delete(property.id);
    });
    syncProperties();
  });

  propertyCards.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-property]');
    if (!button) return;
    selectedProperties.delete(String(button.dataset.removeProperty));
    syncProperties();
  });

  if (window.jQuery) {
    const tableWrapper = document.getElementById('assignment-properties-table_wrapper');
    tableWrapper?.querySelectorAll('input, select, textarea').forEach(control => {
      control.removeAttribute('name');
      control.dataset.validationSkip = 'true';
      control.classList.remove('is-valid', 'is-invalid');
      control.removeAttribute('aria-invalid');
    });

    window.jQuery('#assignment-properties-table').on('draw.dt', () => {
      const selectAll = document.getElementById('select-all-properties');
      if (selectAll) selectAll.checked = false;
      document.querySelectorAll('.property-selector').forEach(checkbox => { checkbox.checked = selectedProperties.has(String(checkbox.value)); });
    });
  }

  function fileKey(file) { return `${file.name}:${file.size}:${file.lastModified}`; }

  function syncFileInput(config) {
    const files = accumulatedFiles.get(config.input.id) || new Map();
    const restored = restoredDraftFiles.get(config.input.id) || new Map();
    const transfer = new DataTransfer();
    files.forEach(file => transfer.items.add(file));
    config.input.files = transfer.files;
    const localCards = [...files.entries()].map(([key, file]) => `
      <article class="assignment-file-card"><i class="ti ti-file text-primary fs-4"></i><span class="assignment-file-card__name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span><button type="button" class="btn btn-sm btn-icon btn-label-danger" data-remove-file="${escapeHtml(key)}" data-input-id="${config.input.id}" aria-label="إزالة ${escapeHtml(file.name)}"><i class="ti ti-x"></i></button></article>`).join('');
    const restoredCards = [...restored.values()].map(file => `
      <article class="assignment-file-card"><i class="ti ti-file-check text-success fs-4"></i><span class="assignment-file-card__name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span><button type="button" class="btn btn-sm btn-icon btn-label-danger" data-remove-draft-file="${escapeHtml(file.key)}" data-input-id="${config.input.id}" aria-label="إزالة ${escapeHtml(file.name)}"><i class="ti ti-x"></i></button></article>`).join('');
    config.list.innerHTML = localCards + restoredCards;
    config.input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  fileInputs.forEach(config => {
    if (!config.input) return;
    accumulatedFiles.set(config.input.id, new Map());
    config.input.addEventListener('change', () => {
      const files = accumulatedFiles.get(config.input.id);
      [...config.input.files].forEach(file => files.set(fileKey(file), file));
      syncFileInput(config);
    });
  });

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-file]');
    if (!button) return;
    const config = fileInputs.find(item => item.input?.id === button.dataset.inputId);
    if (!config) return;
    accumulatedFiles.get(config.input.id)?.delete(button.dataset.removeFile);
    syncFileInput(config);
  });

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-remove-draft-file]');
    if (!button) return;
    const config = fileInputs.find(item => item.input?.id === button.dataset.inputId);
    if (!config || !form.dataset.draftFileUrl) return;
    button.disabled = true;
    const response = await fetch(form.dataset.draftFileUrl, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify({ resource_key: form.dataset.draftResourceKey, file_key: button.dataset.removeDraftFile }),
    });
    if (!response.ok) {
      button.disabled = false;
      window.toastr?.error?.('تعذر حذف الملف من المسودة.');
      return;
    }
    restoredDraftFiles.get(config.input.id)?.delete(button.dataset.removeDraftFile);
    syncFileInput(config);
  });

  function selectedText(name) {
    const select = form.elements.namedItem(name);
    return select?.selectedOptions?.[0]?.textContent?.trim() || '-';
  }

  function buildReview() {
    const value = name => form.elements.namedItem(name)?.value || '-';
    const fields = [
      ['رقم الإسناد', value('assignment_number')], ['اسم الإسناد', value('name')], ['نوع الإسناد', selectedText('category')],
      ['حالة الإسناد', selectedText('status')], ['الجهة المسندة', value('issuing_entity')], ['المدينة', selectedText('city')],
      ['النطاق/الحي', value('district')], ['تاريخ البداية', value('start_date')], ['تاريخ النهاية', value('end_date')], ['وصف الإسناد', value('notes')],
    ];
    document.getElementById('review-basic').innerHTML = `<dl class="row mb-0">${fields.map(([label, content]) => `<dt class="col-md-3 mb-2">${label}</dt><dd class="col-md-9 mb-2">${escapeHtml(content)}</dd>`).join('')}</dl>`;
    renderPropertyCards(document.getElementById('review-properties'), false);

    const reviewFiles = [];
    const singleFiles = [form.elements.namedItem('assignment_image')?.files?.[0], form.elements.namedItem('assignment_letter')?.files?.[0]].filter(Boolean);
    singleFiles.forEach(file => reviewFiles.push(file.name));
    accumulatedFiles.forEach(files => files.forEach(file => reviewFiles.push(file.name)));
    restoredDraftFiles.forEach(files => files.forEach(file => reviewFiles.push(file.name)));
    restoredSingleFiles.forEach(file => reviewFiles.push(file.name));
    document.getElementById('review-files').innerHTML = reviewFiles.map(name => `<article class="assignment-file-card"><i class="ti ti-file text-primary fs-4"></i><span class="assignment-file-card__name">${escapeHtml(name)}</span></article>`).join('');
    document.getElementById('review-name').textContent = value('name');
    document.getElementById('review-description').textContent = value('notes');
  }

  form.addEventListener('change', buildReview);
  document.querySelectorAll('[data-edit-step]').forEach(button => button.addEventListener('click', () => {
    const wizard = document.getElementById('wizard-validation');
    wizard?.bsStepper?.to(Number(button.dataset.editStep));
  }));

  const imageInput = form.elements.namedItem('assignment_image');
  imageInput?.addEventListener('change', () => {
    const file = imageInput.files?.[0];
    if (file) document.getElementById('review-image').src = URL.createObjectURL(file);
  });

  form.addEventListener('wizardDraftRestored', async event => {
    const ids = event.detail?.data?.property_ids || [];
    if (ids.length && form.dataset.selectedPropertiesUrl) {
      const url = new URL(form.dataset.selectedPropertiesUrl, window.location.origin);
      ids.forEach(id => url.searchParams.append('ids[]', id));
      const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (response.ok) {
        const payload = await response.json();
        (payload.data || []).forEach(property => selectedProperties.set(String(property.id), property));
      }
    }
    const draftFiles = event.detail?.files || {};
    ['general_documents', 'additional_attachments'].forEach(inputId => {
      const entries = Object.values(draftFiles[inputId] || {}).filter(file => file?.key && file?.name);
      restoredDraftFiles.set(inputId, new Map(entries.map(file => [file.key, file])));
      const config = fileInputs.find(item => item.input?.id === inputId);
      if (config) syncFileInput(config);
    });
    const singleDraftFiles = [draftFiles.assignment_image, draftFiles.assignment_letter].filter(file => file?.name);
    singleDraftFiles.forEach(file => {
      restoredSingleFiles.set(file.key, file);
      const field = file.key === 'assignment_image' ? form.elements.namedItem('assignment_image') : form.elements.namedItem('assignment_letter');
      const wrapper = field?.closest('[data-form-field]');
      if (wrapper && !wrapper.querySelector('[data-restored-file]')) {
        const note = document.createElement('div');
        note.dataset.restoredFile = 'true';
        note.className = 'small text-success mt-2';
        note.textContent = `الملف المحفوظ في المسودة: ${file.name}`;
        wrapper.append(note);
      }
    });
    syncProperties();
    buildReview();
  });

  buildReview();
});
