import { backgroundStyle, setPageBackground } from './backgrounds.js';
import { clampRect, pointerDelta, resizeRect } from './geometry.js';
import { DESIGNER_FONTS, DEFAULT_FONT, normalizeFont } from './fonts.js';
import { activePage, createDesignState, replaceSchema } from './state.js';

const BINDINGS_BY_TYPE = {
  brochure: [
    { key: 'auction.name', label: 'اسم المزاد', required: true },
    { key: 'auction.start_date', label: 'تاريخ بداية المزاد', required: true },
    { key: 'auction.start_time', label: 'وقت بداية المزاد', required: false },
    { key: 'auction.end_date', label: 'تاريخ نهاية المزاد', required: false },
    { key: 'auction.end_time', label: 'وقت نهاية المزاد', required: false },
    { key: 'auction.city', label: 'المدينة', required: true },
    { key: 'auction.type', label: 'نوع المزاد', required: false },
    { key: 'auction.category', label: 'تصنيف المزاد', required: false },
    { key: 'auction.expected_properties_count', label: 'عدد العقارات', required: false },
    { key: 'auction.supervising_entity', label: 'الجهة المشرفة', required: false },
    { key: 'auction.platform_name', label: 'منصة المزاد', required: false },
    { key: 'auction.platform_url', label: 'رابط المنصة', required: false },
    { key: 'auction.description', label: 'وصف المزاد', required: false },
    { key: 'auction.cover_image', label: 'صورة الغلاف', required: false },
  ],
  billboard: [
    { key: 'auction.name', label: 'اسم المزاد', required: true },
    { key: 'auction.start_date', label: 'تاريخ بداية المزاد', required: true },
    { key: 'auction.start_time', label: 'وقت بداية المزاد', required: false },
    { key: 'auction.city', label: 'المدينة', required: true },
    { key: 'auction.expected_properties_count', label: 'عدد العقارات', required: false },
    { key: 'auction.supervising_entity', label: 'الجهة المشرفة', required: false },
    { key: 'auction.platform_name', label: 'منصة المزاد', required: false },
    { key: 'auction.platform_url', label: 'رابط المنصة', required: false },
    { key: 'auction.cover_image', label: 'صورة الغلاف', required: false },
  ],
  social: [
    { key: 'auction.name', label: 'اسم المزاد', required: true },
    { key: 'auction.start_date', label: 'تاريخ بداية المزاد', required: true },
    { key: 'auction.city', label: 'المدينة', required: true },
    { key: 'auction.expected_properties_count', label: 'عدد العقارات', required: false },
    { key: 'auction.platform_name', label: 'منصة المزاد', required: false },
    { key: 'auction.cover_image', label: 'صورة الغلاف', required: false },
  ],
};

function getBindingLabel(bindingKey) {
  for (const list of Object.values(BINDINGS_BY_TYPE)) {
    const found = list.find((b) => b.key === bindingKey);
    if (found) return found.label;
  }
  return bindingKey;
}

const canvasFor = (type) => ({
  width: type === 'billboard' ? 1200 : (type === 'social' ? 1080 : 794),
  height: type === 'billboard' ? 628 : (type === 'social' ? 1080 : 1123),
  label: type === 'billboard' ? '1200 × 628 px (16:9 عريض)' : (type === 'social' ? '1080 × 1080 px (1:1 مربع)' : '794 × 1123 px (A4 رأسي)'),
});

const id = () => globalThis.crypto?.randomUUID?.() || `el-${Math.random().toString(36).slice(2, 9)}`;
const esc = (v) => String(v ?? '').replace(/[&<>'"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

function initTemplateDesigners() {
  document.querySelectorAll('[data-template-designer]').forEach((root) => {
    if (root.__templateDesignerInitialized) return;
    root.__templateDesignerInitialized = true;

    const input = root.querySelector('[data-schema-input]');
    const form = root.closest('form');
    let type = root.dataset.type || 'brochure';

    let initial;
    try {
      initial = root.dataset.schema ? JSON.parse(root.dataset.schema) : null;
    } catch (_) {
      initial = null;
    }

    const state = createDesignState(initial, type, id, canvasFor);
    let schema = state.schema;
    let pageIndex = 0;
    let selectedId = null;
    let interaction = null;

    const page = () => {
      state.pageIndex = pageIndex;
      return activePage(state);
    };

    const selected = () => page().elements.find((e) => e.id === selectedId);

    const persist = () => {
      if (input) input.value = JSON.stringify(schema);
    };

    const display = (e) => {
      if (e.type === 'text') return esc(e.content || 'نص جديد');
      if (e.type === 'image') return `🖼️ ${esc(getBindingLabel(e.binding || 'auction.cover_image'))}`;
      return `{{ ${esc(getBindingLabel(e.binding || 'auction.name'))} }}`;
    };

    function updateGuides(currentType) {
      // 1. Specifications Guide card
      const specsWrapper = document.getElementById('specifications-guide-wrapper');
      if (specsWrapper) {
        specsWrapper.querySelectorAll('.spec-guide-item').forEach((item) => {
          item.classList.add('d-none');
        });
        const currentGuide = document.getElementById(`spec-guide-${currentType}`);
        if (currentGuide) currentGuide.classList.remove('d-none');
      }

      // 2. Approved fields list
      const badgesContainer = document.getElementById('approved-fields-badges');
      const countBadge = document.getElementById('approved-fields-count');
      const fields = BINDINGS_BY_TYPE[currentType] || BINDINGS_BY_TYPE.brochure;

      if (countBadge) {
        countBadge.textContent = `${fields.length} بيان`;
      }
      if (badgesContainer) {
        badgesContainer.innerHTML = fields
          .map((f) => {
            const badgeColor = f.required ? 'bg-label-primary' : 'bg-label-secondary';
            return `<span class="badge ${badgeColor}" title="${esc(f.key)}">${esc(f.label)}${f.required ? ' *' : ''}</span>`;
          })
          .join('');
      }

      // 3. Canvas Dimensions indicator
      const dimsBadge = root.querySelector('[data-canvas-dims]');
      if (dimsBadge) {
        dimsBadge.textContent = canvasFor(currentType).label;
      }
    }

    function renderTabs() {
      const tabs = root.querySelector('[data-page-tabs]');
      if (tabs) {
        tabs.innerHTML = schema.pages
          .map((_, i) => `<button type="button" class="btn btn-sm ${i === pageIndex ? 'btn-primary' : 'btn-outline-secondary'}" data-page-index="${i}">صفحة ${i + 1}</button>`)
          .join('');
      }
      const add = root.querySelector('[data-add-page]');
      if (add) add.classList.toggle('d-none', type !== 'brochure');
    }

    function renderCanvas() {
      const canvas = root.querySelector('[data-canvas]');
      if (!canvas) return;

      canvas.className = `template-designer__canvas template-canvas is-${type}`;
      canvas.style.aspectRatio = `${schema.canvas.width}/${schema.canvas.height}`;
      Object.assign(canvas.style, backgroundStyle(page().background));

      canvas.innerHTML = page().elements
        .map((e) => {
          return `<div role="button" tabindex="0" class="template-designer__element is-${e.type} ${e.id === selectedId ? 'is-selected' : ''}" data-element-id="${e.id}" style="left:${e.x}px;top:${e.y}px;width:${e.width}px;height:${e.height}px;color:${e.style?.color || '#173f35'};background-color:${e.style?.background_color || 'transparent'};font-size:${e.style?.font_size || 24}px;font-weight:${e.style?.font_weight || 'normal'};font-family:${normalizeFont(e.style?.font_family || DEFAULT_FONT)};text-align:${e.style?.text_align || 'right'};border-radius:${e.style?.border_radius || 0}px">${display(e)}<span class="template-designer__resize-handles">${['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'].map((h) => `<i data-resize-handle="${h}"></i>`).join('')}</span></div>`;
        })
        .join('');

      const color = root.querySelector('[data-background-color]');
      if (color) color.value = page().background.color || '#ffffff';

      const positionControls = root.querySelector('[data-background-position-controls]');
      positionControls?.classList.toggle('d-none', !(page().background.image_preview || page().background.image));

      root.querySelectorAll('[data-background-position]').forEach((control) => {
        const axis = control.dataset.backgroundPosition;
        const value = page().background[`position_${axis}`] ?? 50;
        control.value = value;
        const output = root.querySelector(`[data-background-position-value="${axis}"]`);
        if (output) output.textContent = `${value}%`;
      });
    }

    function renderProperties() {
      const box = root.querySelector('[data-properties]');
      if (!box) return;
      const e = selected();
      if (!e) {
        box.innerHTML = '<p class="text-muted small mb-0">اختر عنصراً من مساحة التصميم لتعديل خصائصه.</p>';
        return;
      }
      const isText = e.type === 'text';
      const isImage = e.type === 'image';
      const style = e.style || {};
      const currentBindings = BINDINGS_BY_TYPE[type] || BINDINGS_BY_TYPE.brochure;

      box.innerHTML = `
        <div class="mb-3">
          <label class="form-label">${isText ? 'محتوى النص' : 'البيان المرتبط'}</label>
          ${isText
            ? `<input class="form-control" data-property="content" value="${esc(e.content)}">`
            : `<select class="form-select" data-property="binding">
                ${currentBindings.map((b) => `<option value="${b.key}" ${e.binding === b.key ? 'selected' : ''}>${esc(b.label)} (${b.key})${b.required ? ' *' : ''}</option>`).join('')}
              </select>`
          }
        </div>
        <div class="row g-2 mb-3">
          ${['x', 'y', 'width', 'height'].map((p) => `
            <div class="col-6">
              <label class="form-label small">${p.toUpperCase()} (px)</label>
              <input class="form-control form-control-sm" type="number" min="${p === 'width' || p === 'height' ? 20 : 0}" data-property="${p}" value="${Math.round(e[p])}">
            </div>
          `).join('')}
        </div>
        ${!isImage ? `
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small">نوع الخط</label>
              <select class="form-select form-select-sm" data-style="font_family">
                ${DESIGNER_FONTS.map(([v, l]) => `<option value="${v}" ${normalizeFont(style.font_family) === v ? 'selected' : ''}>${l}</option>`).join('')}
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">حجم الخط</label>
              <input class="form-control form-control-sm" type="number" min="6" data-style="font_size" value="${style.font_size || 24}">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small">محاذاة النص</label>
              <select class="form-select form-select-sm" data-style="text_align">
                <option value="right" ${style.text_align === 'right' ? 'selected' : ''}>يمين</option>
                <option value="center" ${style.text_align === 'center' ? 'selected' : ''}>وسط</option>
                <option value="left" ${style.text_align === 'left' ? 'selected' : ''}>يسار</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small">لون النص</label>
              <input class="form-control form-control-color w-100" type="color" data-style="color" value="${style.color || '#173f35'}">
            </div>
          </div>
        ` : ''}
        <button type="button" class="btn btn-outline-danger btn-sm w-100" data-delete-element>
          <i class="ti ti-trash me-1"></i>حذف العنصر
        </button>
      `;
    }

    const render = () => {
      renderTabs();
      renderCanvas();
      renderProperties();
      persist();
    };

    function applyType(newType) {
      if (!newType) return;
      type = newType;
      root.dataset.type = type;

      const dims = canvasFor(type);
      schema.canvas.width = dims.width;
      schema.canvas.height = dims.height;

      if (type !== 'brochure' && schema.pages.length > 1) {
        schema.pages = schema.pages.slice(0, 1);
        pageIndex = 0;
      }

      // Clamp existing elements so none remain outside the new canvas dimensions
      schema.pages.forEach((p) => {
        p.elements.forEach((e) => {
          if (e.x + e.width > dims.width) e.x = Math.max(0, dims.width - e.width);
          if (e.y + e.height > dims.height) e.y = Math.max(0, dims.height - e.height);
        });
      });

      updateGuides(type);
      render();
    }

    // Handle Clicks
    root.addEventListener('click', (ev) => {
      const p = ev.target.closest('[data-page-index]');
      if (p) {
        pageIndex = Number(p.dataset.pageIndex);
        selectedId = null;
        render();
        return;
      }

      const add = ev.target.closest('[data-add-element]')?.dataset.addElement;
      if (add) {
        const defaultBinding = add === 'variable'
          ? (BINDINGS_BY_TYPE[type]?.[0]?.key || 'auction.name')
          : (add === 'image' ? 'auction.cover_image' : null);

        const e = {
          id: id(),
          type: add,
          x: 60,
          y: 60,
          width: add === 'image' ? 220 : 280,
          height: add === 'image' ? 180 : 54,
          content: add === 'text' ? 'نص جديد' : null,
          binding: defaultBinding,
          style: {
            color: '#173f35',
            font_size: 24,
            font_weight: 'normal',
            font_family: DEFAULT_FONT,
            text_align: 'right',
            border_radius: 0,
          },
        };
        page().elements.push(e);
        selectedId = e.id;
        render();
        return;
      }

      if (ev.target.closest('[data-add-page]') && type === 'brochure') {
        schema.pages.push({
          id: id(),
          background: { color: '#ffffff', image: null, fit: 'cover', position_x: 50, position_y: 50 },
          elements: [],
        });
        pageIndex = schema.pages.length - 1;
        selectedId = null;
        render();
        return;
      }

      if (ev.target.closest('[data-delete-element]') && selectedId) {
        page().elements = page().elements.filter((e) => e.id !== selectedId);
        selectedId = null;
        render();
        return;
      }

      const el = ev.target.closest('[data-element-id]');
      if (el) {
        selectedId = el.dataset.elementId;
        renderProperties();
        root.querySelectorAll('[data-element-id]').forEach((n) => n.classList.toggle('is-selected', n.dataset.elementId === selectedId));
        return;
      }

      // Preview modal button
      if (ev.target.closest('[data-preview-design]')) {
        const previewModalEl = document.getElementById('designer-preview-modal');
        if (previewModalEl) {
          const body = previewModalEl.querySelector('[data-preview-body]');
          if (body) {
            body.innerHTML = `
              <div class="d-flex flex-column align-items-center gap-4 p-3 bg-success mb-3 rounded">
                ${schema.pages.map((p, i) => `
                  <div class="w-100 text-center">
                    <span class="badge bg-secondary mb-2">صفحة ${i + 1}</span>
                    <div style="position:relative;margin:0 auto;width:min(100%, ${schema.canvas.width}px);aspect-ratio:${schema.canvas.width}/${schema.canvas.height};background-color:${p.background.color || '#fff'};${p.background.image_preview || p.background.image ? `background-image:url('${p.background.image_preview || p.background.image}');background-size:cover;background-position:${p.background.position_x ?? 50}% ${p.background.position_y ?? 50}%;` : ''}border:1px solid #ddd;border-radius:4px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                      ${p.elements.map((el) => `
                        <div style="position:absolute;left:${el.x}px;top:${el.y}px;width:${el.width}px;height:${el.height}px;color:${el.style?.color || '#173f35'};font-size:${el.style?.font_size || 24}px;font-family:${normalizeFont(el.style?.font_family || DEFAULT_FONT)};text-align:${el.style?.text_align || 'right'};border-radius:${el.style?.border_radius || 0}px;display:flex;align-items:center;">
                          ${display(el)}
                        </div>
                      `).join('')}
                    </div>
                  </div>
                `).join('')}
              </div>
            `;
          }
          if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(previewModalEl).show();
          }
        }
      }
    });

    // Handle Input Events
    root.addEventListener('input', (ev) => {
      if (ev.target.matches('[data-background-color]')) {
        setPageBackground(page(), { color: ev.target.value });
        renderCanvas();
        persist();
        return;
      }

      const backgroundPosition = ev.target.dataset.backgroundPosition;
      if (backgroundPosition) {
        setPageBackground(page(), { [`position_${backgroundPosition}`]: Number(ev.target.value) });
        renderCanvas();
        persist();
        return;
      }

      const e = selected();
      if (!e) return;
      const prop = ev.target.dataset.property;
      const sty = ev.target.dataset.style;
      if (prop) e[prop] = ev.target.type === 'number' ? Number(ev.target.value) : ev.target.value;
      if (sty) {
        e.style ||= {};
        e.style[sty] = ev.target.type === 'number' ? Number(ev.target.value) : ev.target.value;
      }
      renderCanvas();
      renderProperties();
      persist();
    });

    // Handle Change Events
    root.addEventListener('change', (ev) => {
      if (ev.target.matches('[data-background-image]') && ev.target.files?.[0]) {
        const f = ev.target.files[0];
        if (/^image\/(png|jpe?g|webp)$/i.test(f.type)) {
          ev.target.name = `page_backgrounds[${page().id}]`;
          const r = new FileReader();
          r.onload = () => {
            setPageBackground(page(), { image: null, image_preview: r.result });
            render();
          };
          r.readAsDataURL(f);
        }
      }
    });

    // Wire Type Change with Select2 & Native Select
    if (window.jQuery) {
      window.jQuery(root).find('select[name="type"], #type').on('change.select2 change', function () {
        applyType(this.value);
      });
    }

    const typeSelect = root.querySelector('select[name="type"]');
    typeSelect?.addEventListener('change', (ev) => {
      applyType(ev.target.value);
    });

    // Dragging & Resizing
    root.addEventListener('pointerdown', (ev) => {
      const target = ev.target.closest('[data-element-id]');
      if (!target) return;
      const e = page().elements.find((x) => x.id === target.dataset.elementId);
      if (!e) return;

      selectedId = e.id;
      const handle = ev.target.closest('[data-resize-handle]')?.dataset.resizeHandle;
      interaction = handle
        ? { kind: 'resize', target, e, handle, start: { x: ev.clientX, y: ev.clientY }, orig: { ...e } }
        : { kind: 'drag', target, e, start: { x: ev.clientX, y: ev.clientY }, orig: { ...e } };

      target.setPointerCapture?.(ev.pointerId);
      ev.preventDefault();
    });

    root.addEventListener('pointermove', (ev) => {
      if (!interaction) return;
      const canvas = root.querySelector('[data-canvas]');
      const scale = canvas ? canvas.getBoundingClientRect().width / schema.canvas.width : 1;
      const d = pointerDelta(interaction.start, { x: ev.clientX, y: ev.clientY }, scale);

      const next = interaction.kind === 'resize'
        ? resizeRect(interaction.orig, interaction.handle, d, schema.canvas, { lockAspect: interaction.e.type === 'image' })
        : clampRect({ ...interaction.orig, x: interaction.orig.x + d.x, y: interaction.orig.y + d.y }, schema.canvas);

      Object.assign(interaction.e, next);
      interaction.target.style.left = `${next.x}px`;
      interaction.target.style.top = `${next.y}px`;
      interaction.target.style.width = `${next.width}px`;
      interaction.target.style.height = `${next.height}px`;
      persist();
    });

    root.addEventListener('pointerup', () => {
      interaction = null;
    });

    root.addEventListener('pointercancel', () => {
      interaction = null;
    });

    form?.addEventListener('submit', persist);

    const handleDraftRestored = (event) => {
      try {
        const restoredData = event?.detail?.data || {};
        const restoredSchema = restoredData.schema || input?.value;
        if (restoredSchema) {
          const parsed = typeof restoredSchema === 'string' ? JSON.parse(restoredSchema) : restoredSchema;
          replaceSchema(state, parsed, id, canvasFor, type);
          schema = state.schema;
          pageIndex = 0;
          selectedId = null;
        }
        const currentType = typeSelect ? typeSelect.value : (restoredData.type || type);
        if (currentType && currentType !== type) {
          applyType(currentType);
        } else {
          updateGuides(type);
          render();
        }
      } catch (_) {}
    };

    form?.addEventListener('wizardDraftRestored', handleDraftRestored);
    form?.addEventListener('wizard:draft-restored', handleDraftRestored);
    if (window.jQuery) {
      window.jQuery(form).on('wizardDraftRestored', handleDraftRestored);
    }

    // Initial guides & canvas setup
    updateGuides(type);
    render();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTemplateDesigners);
} else {
  initTemplateDesigners();
}

export { initTemplateDesigners };
