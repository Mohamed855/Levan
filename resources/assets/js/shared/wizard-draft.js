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

  const restoreFiles = async (files) => {
    if (!files || typeof files !== 'object') return;
    const flatten = (obj, p = '') => Object.entries(obj || {}).flatMap(([k, v]) => {
      const full = p ? `${p}.${k}` : k;
      if (v && typeof v === 'object' && !Array.isArray(v) && !v.name) return flatten(v, full);
      if (Array.isArray(v)) return v.flatMap((i, idx) => i?.name ? [{ ...i, key: i.key || `${full}.${idx}` }] : []);
      return v?.name ? [{ ...v, key: v.key || full }] : [];
    });

    const flatFiles = flatten(files);
    if (!flatFiles.length) return;

    const filesByInput = new Map();
    flatFiles.forEach((file) => {
      const rootName = String(file.key || '').split('.')[0];
      const input = form.querySelector(`input[type="file"][name="${CSS.escape(rootName)}"], input[type="file"][name="${CSS.escape(rootName)}[]"]`);
      if (!input) return;
      if (!filesByInput.has(input)) filesByInput.set(input, []);
      filesByInput.get(input).push(file);
    });

    for (const [input, items] of filesByInput.entries()) {
      const isMultiple = input.hasAttribute('multiple');
      const itemsToLoad = isMultiple ? items : [items[items.length - 1]];
      const reconstructedFiles = [];

      for (const item of itemsToLoad) {
        try {
          let blob = null;
          if (item.url) {
            const res = await fetch(item.url, { credentials: 'same-origin' });
            if (res.ok) blob = await res.blob();
          }
          if (!blob) blob = new Blob([''], { type: item.mime || 'application/octet-stream' });
          const file = new File([blob], item.name, {
            type: item.mime || blob.type || 'application/octet-stream',
            lastModified: Date.now(),
          });
          file._draftKey = item.key;
          file._draftUrl = item.url;
          reconstructedFiles.push(file);
        } catch (_) {}
      }

      if (!reconstructedFiles.length) continue;

      if (window.DataTransfer) {
        const transfer = new DataTransfer();
        reconstructedFiles.forEach((f) => transfer.items.add(f));
        input.files = transfer.files;
      }

      if (window.SharedFileUpload?.setFiles) {
        window.SharedFileUpload.setFiles(input, reconstructedFiles);
      } else {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  };

  if (endpoint) {
    request(`${endpoint}?resource_key=${encodeURIComponent(resourceKey)}`).then(async (response) => {
      if (!response.ok) return;
      const draft = await response.json();
      if (!draft.draft_id) return;
      draftId = draft.draft_id;
      if (window.bootstrap && modalElement) {
        const modal = new window.bootstrap.Modal(modalElement);
        modalElement.querySelector('[data-restore-draft]').onclick = async () => {
          fill(draft.data);
          await restoreFiles(draft.files);
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
