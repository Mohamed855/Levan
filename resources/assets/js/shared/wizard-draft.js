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

  request(`${endpoint}?resource_key=${encodeURIComponent(resourceKey)}`).then(async (response) => {
    if (!response.ok) return;
    const draft = await response.json();
    if (!draft.draft_id) return;
    draftId = draft.draft_id;
    if (window.bootstrap && modalElement) {
      const modal = new window.bootstrap.Modal(modalElement);
      modalElement.querySelector('[data-restore-draft]').onclick = () => { fill(draft.data); form.querySelector('[data-wizard-draft-id]').value = draftId; form.dispatchEvent(new Event('wizard:draft-restored')); modal.hide(); };
      modalElement.querySelector('[data-discard-draft]').onclick = async () => { await request(endpoint, { method: 'DELETE', body: new URLSearchParams({ resource_key: resourceKey }) }); draftId = null; modal.hide(); };
      modal.show();
    }
  });

  form.querySelector('[data-save-draft]')?.addEventListener('click', async () => {
    const data = new FormData(form); data.set('resource_key', resourceKey); data.set('step', '1');
    const response = await request(endpoint, { method: 'POST', body: data });
    if (response.ok) { const draft = await response.json(); draftId = draft.draft_id; form.querySelector('[data-wizard-draft-id]').value = draftId; }
  });
}