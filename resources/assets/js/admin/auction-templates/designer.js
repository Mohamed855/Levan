const designers = document.querySelectorAll('[data-template-designer]');

for (const root of designers) {
  const input = root.querySelector('[data-schema-input]');
  const form = root.closest('form');
  const initial = root.dataset.schema ? JSON.parse(root.dataset.schema) : null;
  let type = root.dataset.type;
  let schema = initial || {
    canvas: { width: type === 'billboard' ? 1200 : 794, height: type === 'billboard' ? 628 : 1123 },
    pages: [{ id: crypto.randomUUID(), background: { color: '#ffffff', image: null }, elements: [] }],
  };
  let pageIndex = 0;
  let selectedId = null;

  const page = () => schema.pages[pageIndex];
  const selected = () => page().elements.find((element) => element.id === selectedId);
  const persist = () => { input.value = JSON.stringify(schema); };
  const element = (kind) => ({
    id: crypto.randomUUID(), type: kind, x: 60, y: 60, width: kind === 'image' ? 220 : 280,
    height: kind === 'image' ? 180 : 54, content: kind === 'text' ? 'نص جديد' : null,
    binding: kind === 'variable' ? 'auction.name' : kind === 'image' ? 'auction.cover_image' : null,
    required: false, style: { color: '#173f35', background_color: '#ffffff', font_size: 24, font_weight: 'normal', text_align: 'right', border_radius: 0 },
  });

  const escape = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));

  function render() {
    const current = page();
    root.querySelector('[data-page-tabs]').innerHTML = schema.pages.map((item, index) => `<button type="button" class="btn btn-sm ${index === pageIndex ? 'btn-primary' : 'btn-outline-secondary'}" data-page-index="${index}">صفحة ${index + 1}</button>`).join('');
    const canvas = root.querySelector('[data-canvas]');
    canvas.style.aspectRatio = `${schema.canvas.width}/${schema.canvas.height}`;
    canvas.style.background = current.background?.color || '#ffffff';
    canvas.innerHTML = current.elements.map((item) => {
      const value = item.type === 'text' ? item.content : item.type === 'image' ? `صورة: ${item.binding}` : `{{ ${item.binding} }}`;
      return `<button type="button" class="template-designer__element ${item.id === selectedId ? 'is-selected' : ''} is-${item.type}" data-element-id="${item.id}" style="left:${item.x}px;top:${item.y}px;width:${item.width}px;height:${item.height}px;color:${item.style?.color || '#173f35'};font-size:${item.style?.font_size || 24}px;font-weight:${item.style?.font_weight || 'normal'};text-align:${item.style?.text_align || 'right'};border-radius:${item.style?.border_radius || 0}px">${escape(value)}</button>`;
    }).join('');
    const currentSelected = selected();
    root.querySelector('[data-properties]').innerHTML = currentSelected ? `
      <label class="form-label">المحتوى أو المتغير</label>
      ${currentSelected.type === 'text' ? `<input class="form-control" data-property="content" value="${escape(currentSelected.content)}">` : `<select class="form-select" data-property="binding">${['auction.name','auction.description','auction.start_date','auction.end_date','auction.city','auction.platform_name','auction.platform_url','auction.cover_image'].map((binding) => `<option value="${binding}" ${currentSelected.binding === binding ? 'selected' : ''}>${binding}</option>`).join('')}</select>`}
      <div class="row g-2 mt-1"><div class="col-6"><label class="form-label">حجم الخط</label><input class="form-control" type="number" min="6" max="300" data-style="font_size" value="${currentSelected.style?.font_size || 24}"></div><div class="col-6"><label class="form-label">لون النص</label><input class="form-control form-control-color" type="color" data-style="color" value="${currentSelected.style?.color || '#173f35'}"></div></div>
      <div class="form-check mt-3"><input class="form-check-input" type="checkbox" data-property="required" ${currentSelected.required ? 'checked' : ''}><label class="form-check-label">مطلوب عند إنشاء المزاد</label></div>
      <button type="button" class="btn btn-outline-danger btn-sm mt-3" data-delete-element>حذف العنصر</button>` : '<p class="text-muted small mb-0">اختر عنصرًا من مساحة التصميم لتعديل خصائصه.</p>';
    root.querySelector('[data-background-color]').value = current.background?.color || '#ffffff';
    persist();
  }

  root.addEventListener('click', (event) => {
    const pageButton = event.target.closest('[data-page-index]');
    if (pageButton) { pageIndex = Number(pageButton.dataset.pageIndex); selectedId = null; render(); return; }
    const action = event.target.closest('[data-add-element]')?.dataset.addElement;
    if (action) { const item = element(action); page().elements.push(item); selectedId = item.id; render(); return; }
    if (event.target.closest('[data-add-page]')) { if (type === 'brochure') { schema.pages.push({ id: crypto.randomUUID(), background: { color: '#ffffff', image: null }, elements: [] }); pageIndex = schema.pages.length - 1; selectedId = null; render(); } return; }
    const selectedElement = event.target.closest('[data-element-id]');
    if (selectedElement) { selectedId = selectedElement.dataset.elementId; render(); return; }
    if (event.target.closest('[data-delete-element]')) { page().elements = page().elements.filter((item) => item.id !== selectedId); selectedId = null; render(); }
  });

  root.addEventListener('input', (event) => {
    const current = selected();
    if (event.target.matches('[data-background-color]')) { page().background.color = event.target.value; render(); return; }
    if (!current) return;
    if (event.target.dataset.property) current[event.target.dataset.property] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
    if (event.target.dataset.style) { current.style ||= {}; current.style[event.target.dataset.style] = event.target.type === 'number' ? Number(event.target.value) : event.target.value; }
    persist();
  });

  root.addEventListener('pointerdown', (event) => {
    const target = event.target.closest('[data-element-id]');
    if (!target) return;
    const current = page().elements.find((item) => item.id === target.dataset.elementId);
    const startX = event.clientX; const startY = event.clientY; const x = current.x; const y = current.y;
    target.setPointerCapture(event.pointerId);
    target.addEventListener('pointermove', (move) => { current.x = Math.max(0, x + move.clientX - startX); current.y = Math.max(0, y + move.clientY - startY); target.style.left = `${current.x}px`; target.style.top = `${current.y}px`; persist(); });
  });

  document.querySelector('[name="type"]')?.addEventListener('change', (event) => {
    type = event.target.value;
    if (type !== 'brochure' && schema.pages.length > 1) { schema.pages = schema.pages.slice(0, 1); pageIndex = 0; }
    render();
  });
  form?.addEventListener('wizard:draft-restored', () => {
    try { schema = JSON.parse(input.value); pageIndex = 0; selectedId = null; render(); } catch (_) {}
  });
  render();
}