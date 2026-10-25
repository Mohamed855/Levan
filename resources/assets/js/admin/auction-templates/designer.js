import { backgroundStyle, setPageBackground } from './backgrounds.js';
import { clampRect, pointerDelta, resizeRect } from './geometry.js';
import { DESIGNER_FONTS, DEFAULT_FONT, normalizeFont } from './fonts.js';
import { activePage, createDesignState, replaceSchema } from './state.js';

const bindings = ['auction.name', 'auction.description', 'auction.start_date', 'auction.start_time', 'auction.end_date', 'auction.end_time', 'auction.type', 'auction.category', 'auction.city', 'auction.expected_properties_count', 'auction.supervising_entity', 'auction.platform_name', 'auction.platform_url', 'auction.cover_image'];
const canvasFor = (type) => ({ width: type === 'billboard' ? 1200 : type === 'social' ? 1080 : 794, height: type === 'billboard' ? 628 : type === 'social' ? 1080 : 1123 });
const id = () => globalThis.crypto?.randomUUID?.() || `el-${Math.random().toString(36).slice(2, 9)}`;
const esc = (v) => String(v ?? '').replace(/[&<>'"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

function initTemplateDesigners() {
  document.querySelectorAll('[data-template-designer]').forEach((root) => {
    if (root.__templateDesignerInitialized) return;
    root.__templateDesignerInitialized = true;
    const input = root.querySelector('[data-schema-input]');
    const form = root.closest('form');
    let type = root.dataset.type || 'brochure';
    let initial; try { initial = root.dataset.schema ? JSON.parse(root.dataset.schema) : null; } catch (_) { initial = null; }
    const state = createDesignState(initial, type, id, canvasFor);
    let schema = state.schema; let pageIndex = 0; let selectedId = null; let interaction = null;
    const page = () => { state.pageIndex = pageIndex; return activePage(state); };
    const selected = () => page().elements.find((e) => e.id === selectedId);
    const persist = () => { if (input) input.value = JSON.stringify(schema); };
    const display = (e) => e.type === 'text' ? esc(e.content || 'نص جديد') : e.type === 'image' ? `🖼️ ${esc(e.binding || 'auction.cover_image')}` : `{{ ${esc(e.binding || 'auction.name')} }}`;
    function renderTabs() {
      const tabs = root.querySelector('[data-page-tabs]');
      if (tabs) tabs.innerHTML = schema.pages.map((_, i) => `<button type="button" class="btn btn-sm ${i === pageIndex ? 'btn-primary' : 'btn-outline-secondary'}" data-page-index="${i}">صفحة ${i + 1}</button>`).join('');
      const add = root.querySelector('[data-add-page]'); if (add) add.classList.toggle('d-none', type !== 'brochure');
    }
    function renderCanvas() {
      const canvas = root.querySelector('[data-canvas]'); if (!canvas) return;
      canvas.style.aspectRatio = `${schema.canvas.width}/${schema.canvas.height}`; Object.assign(canvas.style, backgroundStyle(page().background));
      canvas.innerHTML = page().elements.map((e) => `<div role="button" tabindex="0" class="template-designer__element is-${e.type} ${e.id === selectedId ? 'is-selected' : ''}" data-element-id="${e.id}" style="left:${e.x}px;top:${e.y}px;width:${e.width}px;height:${e.height}px;color:${e.style?.color || '#173f35'};background-color:${e.style?.background_color || 'transparent'};font-size:${e.style?.font_size || 24}px;font-weight:${e.style?.font_weight || 'normal'};font-family:${normalizeFont(e.style?.font_family || DEFAULT_FONT)};text-align:${e.style?.text_align || 'right'};border-radius:${e.style?.border_radius || 0}px">${display(e)}<span class="template-designer__resize-handles">${['nw','n','ne','e','se','s','sw','w'].map((h) => `<i data-resize-handle="${h}"></i>`).join('')}</span></div>`).join('');
      const color = root.querySelector('[data-background-color]'); if (color) color.value = page().background.color || '#ffffff';
      const fit = root.querySelector('[data-background-fit]'); if (fit) fit.value = page().background.fit || 'cover';
    }
    function renderProperties() {
      const box = root.querySelector('[data-properties]'); if (!box) return; const e = selected();
      if (!e) { box.innerHTML = '<p class="text-muted small">اختر عنصراً من مساحة التصميم.</p>'; return; }
      const isText = e.type === 'text'; const style = e.style || {};
      box.innerHTML = `<div class="mb-3"><label class="form-label">${isText ? 'محتوى النص' : 'البيان المرتبط'}</label>${isText ? `<input class="form-control" data-property="content" value="${esc(e.content)}">` : `<select class="form-select" data-property="binding">${bindings.map((b) => `<option value="${b}" ${e.binding === b ? 'selected' : ''}>${b}</option>`).join('')}</select>`}</div><div class="row g-2 mb-3">${['x','y','width','height'].map((p) => `<div class="col-6"><label class="form-label">${p.toUpperCase()} (px)</label><input class="form-control" type="number" min="${p === 'width' || p === 'height' ? 20 : 0}" data-property="${p}" value="${Math.round(e[p])}"></div>`).join('')}</div>${!e.type.includes('image') ? `<div class="row g-2 mb-3"><div class="col-6"><label class="form-label">الخط</label><select class="form-select" data-style="font_family">${DESIGNER_FONTS.map(([v,l]) => `<option value="${v}" ${normalizeFont(style.font_family) === v ? 'selected' : ''}>${l}</option>`).join('')}</select></div><div class="col-6"><label class="form-label">حجم الخط</label><input class="form-control" type="number" min="6" data-style="font_size" value="${style.font_size || 24}"></div></div>` : ''}<button type="button" class="btn btn-outline-danger btn-sm w-100" data-delete-element>حذف العنصر</button>`;
    }
    const render = () => { renderTabs(); renderCanvas(); renderProperties(); persist(); };
    root.addEventListener('click', (ev) => {
      const p = ev.target.closest('[data-page-index]'); if (p) { pageIndex = Number(p.dataset.pageIndex); selectedId = null; render(); return; }
      const add = ev.target.closest('[data-add-element]')?.dataset.addElement; if (add) { const e = { id: id(), type: add, x: 60, y: 60, width: add === 'image' ? 220 : 280, height: add === 'image' ? 180 : 54, content: add === 'text' ? 'نص جديد' : null, binding: add === 'variable' ? 'auction.name' : add === 'image' ? 'auction.cover_image' : null, style: { color: '#173f35', font_size: 24, font_weight: 'normal', font_family: DEFAULT_FONT, text_align: 'right', border_radius: 0 } }; page().elements.push(e); selectedId = e.id; render(); return; }
      if (ev.target.closest('[data-add-page]') && type === 'brochure') { schema.pages.push({ id: id(), background: { color: '#ffffff', image: null, fit: 'cover' }, elements: [] }); pageIndex = schema.pages.length - 1; selectedId = null; render(); return; }
      if (ev.target.closest('[data-delete-element]') && selectedId) { page().elements = page().elements.filter((e) => e.id !== selectedId); selectedId = null; render(); return; }
      const el = ev.target.closest('[data-element-id]'); if (el) { selectedId = el.dataset.elementId; renderProperties(); root.querySelectorAll('[data-element-id]').forEach((n) => n.classList.toggle('is-selected', n.dataset.elementId === selectedId)); }
    });
    root.addEventListener('input', (ev) => {
      if (ev.target.matches('[data-background-color]')) { setPageBackground(page(), { color: ev.target.value }); renderCanvas(); persist(); return; }
      if (ev.target.matches('[data-background-fit]')) { setPageBackground(page(), { fit: ev.target.value }); renderCanvas(); persist(); return; }
      const e = selected(); if (!e) return; const prop = ev.target.dataset.property; const sty = ev.target.dataset.style;
      if (prop) e[prop] = ev.target.type === 'number' ? Number(ev.target.value) : ev.target.value;
      if (sty) { e.style ||= {}; e.style[sty] = ev.target.type === 'number' ? Number(ev.target.value) : ev.target.value; }
      renderCanvas(); renderProperties(); persist();
    });
    root.addEventListener('change', (ev) => { if (ev.target.matches('[data-background-image]') && ev.target.files?.[0]) { const f = ev.target.files[0]; if (/^image\/(png|jpe?g|webp)$/i.test(f.type)) { ev.target.name = `page_backgrounds[${page().id}]`; const r = new FileReader(); r.onload = () => { setPageBackground(page(), { image: null, image_preview: r.result }); render(); }; r.readAsDataURL(f); } } });
    const typeSelect = root.querySelector('[name="type"]');
    typeSelect?.addEventListener('change', (ev) => {
      type = ev.target.value;
      const dims = canvasFor(type); schema.canvas.width = dims.width; schema.canvas.height = dims.height;
      if (type !== 'brochure' && schema.pages.length > 1) { schema.pages = schema.pages.slice(0, 1); pageIndex = 0; }
      render();
    });
    root.addEventListener('pointerdown', (ev) => { const target = ev.target.closest('[data-element-id]'); if (!target) return; const e = page().elements.find((x) => x.id === target.dataset.elementId); if (!e) return; selectedId = e.id; const handle = ev.target.closest('[data-resize-handle]')?.dataset.resizeHandle; interaction = handle ? { kind: 'resize', target, e, handle, start: { x: ev.clientX, y: ev.clientY }, orig: { ...e } } : { kind: 'drag', target, e, start: { x: ev.clientX, y: ev.clientY }, orig: { ...e } }; target.setPointerCapture?.(ev.pointerId); ev.preventDefault(); });
    root.addEventListener('pointermove', (ev) => { if (!interaction) return; const canvas = root.querySelector('[data-canvas]'); const scale = canvas ? canvas.getBoundingClientRect().width / schema.canvas.width : 1; const d = pointerDelta(interaction.start, { x: ev.clientX, y: ev.clientY }, scale); const next = interaction.kind === 'resize' ? resizeRect(interaction.orig, interaction.handle, d, schema.canvas, { lockAspect: interaction.e.type === 'image' }) : clampRect({ ...interaction.orig, x: interaction.orig.x + d.x, y: interaction.orig.y + d.y }, schema.canvas); Object.assign(interaction.e, next); interaction.target.style.left = `${next.x}px`; interaction.target.style.top = `${next.y}px`; interaction.target.style.width = `${next.width}px`; interaction.target.style.height = `${next.height}px`; persist(); });
    root.addEventListener('pointerup', () => { interaction = null; }); root.addEventListener('pointercancel', () => { interaction = null; });
    form?.addEventListener('submit', persist); form?.addEventListener('wizard:draft-restored', () => { try { replaceSchema(state, JSON.parse(input.value), id, canvasFor, type); schema = state.schema; pageIndex = 0; selectedId = null; render(); } catch (_) {} });
    render();
  });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initTemplateDesigners); else initTemplateDesigners();
export { initTemplateDesigners };
