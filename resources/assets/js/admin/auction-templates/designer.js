'use strict';

function initTemplateDesigners() {
  const designers = document.querySelectorAll('[data-template-designer]');

  for (const root of designers) {
    if (root.__templateDesignerInitialized) continue;
    root.__templateDesignerInitialized = true;

    const input = root.querySelector('[data-schema-input]');
    const form = root.closest('form') || root.querySelector('form') || document.querySelector('form');
    let type = root.dataset.type || 'brochure';

    const getInitialCanvas = (t) => ({
      width: t === 'billboard' ? 1200 : (t === 'social' ? 1080 : 794),
      height: t === 'billboard' ? 628 : (t === 'social' ? 1080 : 1123),
    });

    function generateId() {
      return (typeof crypto !== 'undefined' && crypto.randomUUID)
        ? crypto.randomUUID()
        : 'el-' + Math.random().toString(36).substring(2, 9);
    }

    let initial = null;
    if (root.dataset.schema) {
      try {
        initial = JSON.parse(root.dataset.schema);
      } catch (e) {
        console.warn('Failed to parse template schema', e);
      }
    }

    let schema = initial || {
      canvas: getInitialCanvas(type),
      pages: [{ id: generateId(), background: { color: '#ffffff', image: null }, elements: [] }],
    };

    if (!schema.canvas) {
      schema.canvas = getInitialCanvas(type);
    }
    if (!Array.isArray(schema.pages) || schema.pages.length === 0) {
      schema.pages = [{ id: generateId(), background: { color: '#ffffff', image: null }, elements: [] }];
    }

    let pageIndex = 0;
    let selectedId = null;
    let activeDrag = null;

    const page = () => {
      if (pageIndex >= schema.pages.length) pageIndex = Math.max(0, schema.pages.length - 1);
      return schema.pages[pageIndex];
    };

    const selected = () => page().elements.find((element) => element.id === selectedId);

    const persist = () => {
      if (input) {
        input.value = JSON.stringify(schema);
      }
    };

    const createElement = (kind) => ({
      id: generateId(),
      type: kind,
      x: 60,
      y: 60,
      width: kind === 'image' ? 220 : 280,
      height: kind === 'image' ? 180 : 54,
      content: kind === 'text' ? 'نص جديد' : null,
      binding: kind === 'variable' ? 'auction.name' : kind === 'image' ? 'auction.cover_image' : null,
      required: false,
      style: {
        color: '#173f35',
        background_color: '#ffffff',
        font_size: 24,
        font_weight: 'normal',
        text_align: 'right',
        border_radius: 0,
      },
    });

    const escapeHtml = (value) =>
      String(value || '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;',
      }[char]));

    const bindings = [
      { value: 'auction.name', label: 'اسم المزاد (auction.name)' },
      { value: 'auction.description', label: 'وصف المزاد (auction.description)' },
      { value: 'auction.start_date', label: 'تاريخ بداية المزاد (auction.start_date)' },
      { value: 'auction.start_time', label: 'وقت بداية المزاد (auction.start_time)' },
      { value: 'auction.end_date', label: 'تاريخ نهاية المزاد (auction.end_date)' },
      { value: 'auction.end_time', label: 'وقت نهاية المزاد (auction.end_time)' },
      { value: 'auction.type', label: 'نوع المزاد (auction.type)' },
      { value: 'auction.category', label: 'تصنيف المزاد (auction.category)' },
      { value: 'auction.city', label: 'المدينة (auction.city)' },
      { value: 'auction.expected_properties_count', label: 'عدد العقارات (auction.expected_properties_count)' },
      { value: 'auction.supervising_entity', label: 'الجهة المشرفة (auction.supervising_entity)' },
      { value: 'auction.platform_name', label: 'اسم المنصة (auction.platform_name)' },
      { value: 'auction.platform_url', label: 'رابط المنصة (auction.platform_url)' },
      { value: 'auction.cover_image', label: 'صورة الغلاف (auction.cover_image)' },
    ];

    function getElementDisplayHtml(item) {
      if (item.type === 'text') {
        return escapeHtml(item.content || 'نص جديد');
      }
      if (item.type === 'image') {
        return `🖼️ صورة: ${escapeHtml(item.binding || 'auction.cover_image')}`;
      }
      return `{{ ${escapeHtml(item.binding || 'auction.name')} }}`;
    }

    function renderTabs() {
      const pageTabs = root.querySelector('[data-page-tabs]');
      if (pageTabs) {
        pageTabs.innerHTML = schema.pages.map((item, index) =>
          `<button type="button" class="btn btn-sm ${index === pageIndex ? 'btn-primary' : 'btn-outline-secondary'}" data-page-index="${index}">صفحة ${index + 1}</button>`
        ).join('');
      }

      const addPageBtn = root.querySelector('[data-add-page]');
      if (addPageBtn) {
        if (type === 'brochure') {
          addPageBtn.classList.remove('d-none');
        } else {
          addPageBtn.classList.add('d-none');
        }
      }
    }

    function renderCanvas() {
      const current = page();
      const canvas = root.querySelector('[data-canvas]');
      if (!canvas) return;

      canvas.style.aspectRatio = `${schema.canvas.width}/${schema.canvas.height}`;
      canvas.style.backgroundColor = current.background?.color || '#ffffff';

      canvas.innerHTML = current.elements.map((item) => {
        const val = getElementDisplayHtml(item);
        return `<div role="button" tabindex="0" class="template-designer__element ${item.id === selectedId ? 'is-selected' : ''} is-${item.type}" data-element-id="${item.id}" style="left:${item.x}px;top:${item.y}px;width:${item.width}px;height:${item.height}px;color:${item.style?.color || '#173f35'};background-color:${item.style?.background_color || 'transparent'};font-size:${item.style?.font_size || 24}px;font-weight:${item.style?.font_weight || 'normal'};text-align:${item.style?.text_align || 'right'};border-radius:${item.style?.border_radius || 0}px;">${val}</div>`;
      }).join('');

      const bgColor = root.querySelector('[data-background-color]');
      if (bgColor) {
        bgColor.value = current.background?.color || '#ffffff';
      }
    }

    function selectElement(id) {
      selectedId = id;

      // Update selection class on elements without wiping out the canvas DOM
      const elements = root.querySelectorAll('[data-element-id]');
      elements.forEach((el) => {
        el.classList.toggle('is-selected', el.dataset.elementId === selectedId);
      });

      renderProperties();
      persist();
    }

    function renderProperties() {
      const properties = root.querySelector('[data-properties]');
      if (!properties) return;

      const currentSelected = selected();
      if (!currentSelected) {
        properties.innerHTML = '<p class="text-muted small mb-0">اختر عنصرًا من مساحة التصميم لتعديل خصائصه وموضعه.</p>';
        return;
      }

      const isText = currentSelected.type === 'text';
      const isImage = currentSelected.type === 'image';
      const isVariable = currentSelected.type === 'variable';

      properties.innerHTML = `
        <div class="mb-3">
          <label class="form-label">${isText ? 'محتوى النص' : 'البيان المرتبط (Binding)'}</label>
          ${isText
            ? `<input class="form-control" data-property="content" value="${escapeHtml(currentSelected.content || '')}">`
            : `<select class="form-select" data-property="binding">
                ${bindings
                  .filter((b) => isImage ? b.value === 'auction.cover_image' : b.value !== 'auction.cover_image')
                  .map((b) => `<option value="${b.value}" ${currentSelected.binding === b.value ? 'selected' : ''}>${b.label}</option>`).join('')}
              </select>`
          }
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">الموضع X (px)</label>
            <input class="form-control" type="number" min="0" data-property="x" value="${Math.round(currentSelected.x)}">
          </div>
          <div class="col-6">
            <label class="form-label">الموضع Y (px)</label>
            <input class="form-control" type="number" min="0" data-property="y" value="${Math.round(currentSelected.y)}">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">العرض (px)</label>
            <input class="form-control" type="number" min="20" data-property="width" value="${Math.round(currentSelected.width)}">
          </div>
          <div class="col-6">
            <label class="form-label">الارتفاع (px)</label>
            <input class="form-control" type="number" min="20" data-property="height" value="${Math.round(currentSelected.height)}">
          </div>
        </div>
        ${!isImage ? `
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">حجم الخط</label>
              <input class="form-control" type="number" min="6" max="300" data-style="font_size" value="${currentSelected.style?.font_size || 24}">
            </div>
            <div class="col-6">
              <label class="form-label">محاذاة النص</label>
              <select class="form-select" data-style="text_align">
                <option value="right" ${(currentSelected.style?.text_align || 'right') === 'right' ? 'selected' : ''}>يمين</option>
                <option value="center" ${currentSelected.style?.text_align === 'center' ? 'selected' : ''}>وسط</option>
                <option value="left" ${currentSelected.style?.text_align === 'left' ? 'selected' : ''}>يسار</option>
              </select>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">لون النص</label>
              <input class="form-control form-control-color w-100" type="color" data-style="color" value="${currentSelected.style?.color || '#173f35'}">
            </div>
            <div class="col-6">
              <label class="form-label">سُمك الخط</label>
              <select class="form-select" data-style="font_weight">
                <option value="normal" ${(currentSelected.style?.font_weight || 'normal') === 'normal' ? 'selected' : ''}>عادي</option>
                <option value="bold" ${currentSelected.style?.font_weight === 'bold' ? 'selected' : ''}>عريض (Bold)</option>
              </select>
            </div>
          </div>
        ` : ''}
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">استدارة الحواف (px)</label>
            <input class="form-control" type="number" min="0" max="500" data-style="border_radius" value="${currentSelected.style?.border_radius || 0}">
          </div>
        </div>
        ${isVariable || isImage ? `
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="prop-required" data-property="required" ${currentSelected.required ? 'checked' : ''}>
            <label class="form-check-label" for="prop-required">مطلوب عند إنشاء المزاد</label>
          </div>
        ` : ''}
        <button type="button" class="btn btn-outline-danger btn-sm w-100" data-delete-element>
          <i class="ti ti-trash me-1"></i>حذف العنصر
        </button>
      `;
    }

    function renderAll() {
      renderTabs();
      renderCanvas();
      renderProperties();
      persist();
    }

    // Handle clicks
    root.addEventListener('click', (event) => {
      const pageButton = event.target.closest('[data-page-index]');
      if (pageButton) {
        pageIndex = Number(pageButton.dataset.pageIndex);
        selectElement(null);
        renderTabs();
        renderCanvas();
        return;
      }

      const addAction = event.target.closest('[data-add-element]')?.dataset.addElement;
      if (addAction) {
        const item = createElement(addAction);
        page().elements.push(item);
        renderCanvas();
        selectElement(item.id);
        return;
      }

      if (event.target.closest('[data-add-page]')) {
        if (type === 'brochure') {
          schema.pages.push({
            id: generateId(),
            background: { color: '#ffffff', image: null },
            elements: [],
          });
          pageIndex = schema.pages.length - 1;
          renderTabs();
          renderCanvas();
          selectElement(null);
        }
        return;
      }

      if (event.target.closest('[data-delete-element]')) {
        if (selectedId) {
          page().elements = page().elements.filter((item) => item.id !== selectedId);
          selectElement(null);
          renderCanvas();
        }
        return;
      }

      const elem = event.target.closest('[data-element-id]');
      if (elem) {
        event.stopPropagation();
        selectElement(elem.dataset.elementId);
        return;
      }

      if (event.target.matches('[data-canvas]')) {
        selectElement(null);
      }
    });

    // Handle properties input/change live
    root.addEventListener('input', (event) => {
      if (event.target.matches('[data-background-color]')) {
        page().background.color = event.target.value;
        const canvas = root.querySelector('[data-canvas]');
        if (canvas) canvas.style.backgroundColor = event.target.value;
        persist();
        return;
      }

      const currentSelected = selected();
      if (!currentSelected) return;

      const domElem = root.querySelector(`[data-element-id="${currentSelected.id}"]`);

      if (event.target.dataset.property) {
        const prop = event.target.dataset.property;
        if (event.target.type === 'checkbox') {
          currentSelected[prop] = event.target.checked;
        } else if (event.target.type === 'number') {
          currentSelected[prop] = Number(event.target.value);
        } else {
          currentSelected[prop] = event.target.value;
        }

        if (domElem) {
          if (prop === 'content' || prop === 'binding') {
            domElem.innerHTML = getElementDisplayHtml(currentSelected);
          } else if (prop === 'x') {
            domElem.style.left = `${currentSelected.x}px`;
          } else if (prop === 'y') {
            domElem.style.top = `${currentSelected.y}px`;
          } else if (prop === 'width') {
            domElem.style.width = `${currentSelected.width}px`;
          } else if (prop === 'height') {
            domElem.style.height = `${currentSelected.height}px`;
          }
        }
        persist();
      } else if (event.target.dataset.style) {
        const styleProp = event.target.dataset.style;
        currentSelected.style = currentSelected.style || {};
        const val = event.target.type === 'number' ? Number(event.target.value) : event.target.value;
        currentSelected.style[styleProp] = val;

        if (domElem) {
          if (styleProp === 'font_size') domElem.style.fontSize = `${val}px`;
          if (styleProp === 'color') domElem.style.color = val;
          if (styleProp === 'font_weight') domElem.style.fontWeight = val;
          if (styleProp === 'text_align') domElem.style.textAlign = val;
          if (styleProp === 'border_radius') domElem.style.borderRadius = `${val}px`;
        }
        persist();
      }
    });

    root.addEventListener('change', (event) => {
      if (event.target.dataset.property || event.target.dataset.style) {
        event.target.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });

    // Drag & Drop via Pointer Events
    root.addEventListener('pointerdown', (event) => {
      const target = event.target.closest('[data-element-id]');
      if (!target) return;
      const currentElem = page().elements.find((item) => item.id === target.dataset.elementId);
      if (!currentElem) return;

      // Select element immediately on pointerdown without destroying canvas elements!
      selectElement(currentElem.id);

      activeDrag = {
        target,
        element: currentElem,
        startX: event.clientX,
        startY: event.clientY,
        origX: currentElem.x,
        origY: currentElem.y,
        hasMoved: false,
      };

      try {
        target.setPointerCapture(event.pointerId);
      } catch (_) {}
    });

    root.addEventListener('pointermove', (event) => {
      if (!activeDrag) return;
      const dx = event.clientX - activeDrag.startX;
      const dy = event.clientY - activeDrag.startY;

      if (!activeDrag.hasMoved && Math.hypot(dx, dy) < 3) {
        return;
      }

      activeDrag.hasMoved = true;
      activeDrag.element.x = Math.max(0, Math.round(activeDrag.origX + dx));
      activeDrag.element.y = Math.max(0, Math.round(activeDrag.origY + dy));
      activeDrag.target.style.left = `${activeDrag.element.x}px`;
      activeDrag.target.style.top = `${activeDrag.element.y}px`;

      const xInput = root.querySelector('[data-property="x"]');
      const yInput = root.querySelector('[data-property="y"]');
      if (xInput) xInput.value = activeDrag.element.x;
      if (yInput) yInput.value = activeDrag.element.y;
    });

    const endDrag = (event) => {
      if (!activeDrag) return;
      try {
        if (activeDrag.target && activeDrag.target.hasPointerCapture(event.pointerId)) {
          activeDrag.target.releasePointerCapture(event.pointerId);
        }
      } catch (_) {}
      activeDrag = null;
      persist();
    };

    root.addEventListener('pointerup', endDrag);
    root.addEventListener('pointercancel', endDrag);

    // Template Type Change Handler (supports native and Select2)
    const handleTypeChange = (newType) => {
      if (!newType || newType === type) return;
      type = newType;
      root.dataset.type = type;
      const dims = getInitialCanvas(type);
      schema.canvas.width = dims.width;
      schema.canvas.height = dims.height;

      if (type !== 'brochure' && schema.pages.length > 1) {
        schema.pages = schema.pages.slice(0, 1);
        pageIndex = 0;
      }
      renderTabs();
      renderCanvas();
      persist();
    };

    // Initialize Select2 if jQuery + Select2 are available
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
      const $typeSelect = window.jQuery(root).find('select[name="type"]');
      if ($typeSelect.length && !$typeSelect.hasClass('select2-hidden-accessible')) {
        $typeSelect.select2({
          width: '100%',
        });
      }
      $typeSelect.on('change.select2 change', function () {
        handleTypeChange(this.value);
      });
    }

    const typeSelect = root.querySelector('[name="type"]');
    if (typeSelect) {
      typeSelect.addEventListener('change', (event) => {
        handleTypeChange(event.target.value);
      });
    }

    form?.addEventListener('wizard:draft-restored', () => {
      try {
        if (input && input.value) {
          schema = JSON.parse(input.value);
          pageIndex = 0;
          selectedId = null;
          renderAll();
        }
      } catch (e) {
        console.warn('Failed to parse restored draft schema', e);
      }
    });

    form?.addEventListener('submit', () => {
      persist();
    });

    renderAll();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTemplateDesigners);
} else {
  initTemplateDesigners();
}
