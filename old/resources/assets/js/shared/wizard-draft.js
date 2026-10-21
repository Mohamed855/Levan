const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

for (const form of document.querySelectorAll('form[data-wizard-resource]')) {
  const resourceKey = form.dataset.wizardResource;
  const endpoint = form.dataset.wizardUrl;
  const modalElement = document.querySelector(`[data-wizard-modal="${resourceKey}"]`);
  let draftId = null;
  const request = async (url, options = {}) => fetch(url, { ...options, headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', ...(options.headers || {}) } });
  
  const fill = (data, prefix = '') => Object.entries(data || {}).forEach(([key, value]) => {
    const name = prefix ? `${prefix}[${key}]` : key;
    if (value && typeof value === 'object' && !Array.isArray(value)) return fill(value, name);
    const control = form.elements.namedItem(name);
    if (!control || control.type === 'file') return;
    if (control.length && control[0]?.type === 'radio') Array.from(control).forEach((item) => { item.checked = item.value === String(value); });
    else if (control.type === 'checkbox') control.checked = Boolean(value) && value !== '0';
    else control.value = value ?? '';
    control.dispatchEvent(new Event('change', { bubbles: true }));
  });

  const restoreFiles = (files) => {
    if (!files || typeof files !== 'object') return;
    const flatten = (obj, p = '') => Object.entries(obj || {}).flatMap(([k, v]) => {
      const full = p ? `${p}.${k}` : k;
      if (v && typeof v === 'object' && !Array.isArray(v) && !v.name) return flatten(v, full);
      if (Array.isArray(v)) return v.flatMap((i, idx) => i?.name ? [{ ...i, key: i.key || `${full}.${idx}` }] : []);
      return v?.name ? [{ ...v, key: v.key || full }] : [];
    });

    flatten(files).forEach((file) => {
      const rootName = String(file.key || '').split('.')[0];
      const input = form.querySelector(`input[type="file"][name="${CSS.escape(rootName)}"], input[type="file"][name="${CSS.escape(rootName)}[]"]`);
      if (!input) return;

      const previewId = input.dataset.imageInput || input.id;
      const previewImg = form.querySelector(`[data-image-preview="${CSS.escape(previewId)}"]`);
      if (previewImg && file.url) {
        previewImg.dataset.defaultSrc = previewImg.dataset.defaultSrc || previewImg.src;
        previewImg.src = file.url;
      }

      let list = input.parentElement?.querySelector('[data-archived-draft-files]');
      if (!list) {
        list = document.createElement('div');
        list.dataset.archivedDraftFiles = '';
        list.className = 'd-flex flex-column gap-2 mt-2';
        input.parentElement?.append(list);
      }

      const item = document.createElement('div');
      item.className = 'd-flex align-items-center justify-content-between gap-2 rounded border p-2 bg-light';
      const name = document.createElement('span');
      name.className = 'text-truncate small';
      name.innerHTML = `<i class="ti ti-file-check text-success me-1"></i>${file.name}`;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-sm btn-icon btn-outline-danger';
      remove.dataset.draftFileKey = file.key;
      remove.setAttribute('aria-label', `حذف الملف المؤرشف ${file.name}`);
      remove.innerHTML = '<i class="ti ti-trash" aria-hidden="true"></i>';
      item.append(name, remove);
      list.append(item);
    });
  };

  if (endpoint) {
    request(`${endpoint}?resource_key=${encodeURIComponent(resourceKey)}`).then(async (response) => {
      if (!response.ok) return;
      const draft = await response.json();
      if (!draft.draft_id) return;
      draftId = draft.draft_id;
      if (window.bootstrap && modalElement) {
        const modal = new window.bootstrap.Modal(modalElement);
        modalElement.querySelector('[data-restore-draft]').onclick = () => {
          fill(draft.data);
          restoreFiles(draft.files);
          const hiddenId = form.querySelector('[data-wizard-draft-id]');
          if (hiddenId) hiddenId.value = draftId;
          form.dispatchEvent(new CustomEvent('wizard:draft-restored', { detail: { data: draft.data, files: draft.files, draftId } }));
          modal.hide();
        };
        modalElement.querySelector('[data-discard-draft]').onclick = async () => {
          await request(endpoint, { method: 'DELETE', body: new URLSearchParams({ resource_key: resourceKey }) });
          draftId = null;
          modal.hide();
        };
        modal.show();
      }
    });

    form.querySelectorAll('[data-save-draft], [data-wizard-draft], .btn-save-draft').forEach((saveBtn) => {
      saveBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        saveBtn.disabled = true;
        try {
          const data = new FormData(form);
          data.set('resource_key', resourceKey);
          data.set('step', '1');
          const response = await request(endpoint, { method: 'POST', body: data });
          if (response.ok) {
            const draft = await response.json();
            draftId = draft.draft_id;
            const hiddenId = form.querySelector('[data-wizard-draft-id]');
            if (hiddenId) hiddenId.value = draftId;
            window.toastr?.success?.(draft.message || 'تم حفظ المسودة بنجاح.');
          } else {
            window.toastr?.error?.('تعذر حفظ المسودة.');
          }
        } catch (_) {
          window.toastr?.error?.('تعذر حفظ المسودة.');
        } finally {
          saveBtn.disabled = false;
        }
      });
    });
  }
}
