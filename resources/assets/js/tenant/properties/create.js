'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('property-create-form');
  if (!form) return;

  function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
  }

  // =========================================================================
  // 1. Dynamic Borders (حدود العقار)
  // =========================================================================
  const bordersList = document.getElementById('boundaries-list');
  const addBorderBtn = document.getElementById('btn-add-border');
  const borderRowTemplate = document.getElementById('boundary-row-template');

  function getBorderIndex() {
    return bordersList ? bordersList.querySelectorAll('.boundary-row').length : 0;
  }

  function createBorderRow(data = {}) {
    if (!borderRowTemplate) return null;

    const index = getBorderIndex();
    const row = borderRowTemplate.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('*').forEach((element) => {
      Array.from(element.attributes).forEach((attribute) => {
        if (attribute.value.includes('__INDEX__')) {
          element.setAttribute(attribute.name, attribute.value.replaceAll('__INDEX__', String(index)));
        }
      });
    });

    row.querySelector('select[name*="[direction]"]').value = data.direction ?? '';
    row.querySelector('select[name*="[border_type]"]').value = data.border_type ?? '';
    row.querySelector('input[name*="[border_description]"]').value = data.border_description ?? '';
    row.querySelector('input[name*="[length]"]').value = (data.length !== undefined && data.length !== '' && data.length !== null) ? data.length : 0;

    return row;
  }

  if (addBorderBtn && bordersList) {
    addBorderBtn.addEventListener('click', () => {
      const row = createBorderRow();
      if (!row) return;
      bordersList.appendChild(row);
      reindexBorders();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });

    bordersList.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.btn-remove-border');
      if (!removeBtn) return;
      const rows = bordersList.querySelectorAll('.boundary-row');
      if (rows.length <= 1) {
        if (window.toastr) window.toastr.warning('يجب إبقاء حد واحد على الأقل للعقار');
        return;
      }
      removeBtn.closest('.boundary-row').remove();
      reindexBorders();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function reindexBorders() {
    if (!bordersList) return;
    if (window.jQuery?.fn?.select2) {
      window.jQuery(bordersList).find('select.select2-hidden-accessible').select2('destroy');
    }

    bordersList.querySelectorAll('.boundary-row').forEach((row, index) => {
      row.querySelectorAll('input, select').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/boundaries\[\d+\]/, `boundaries[${index}]`));
        }
      });

      row.querySelectorAll('*').forEach((element) => {
        ['id', 'for', 'data-field-name', 'aria-describedby'].forEach((attributeName) => {
          const value = element.getAttribute(attributeName);
          if (!value) return;
          element.setAttribute(attributeName, value
            .replace(/boundaries\[\d+\]/, `boundaries[${index}]`)
            .replace(/boundaries-\d+-/, `boundaries-${index}-`));
        });
      });
    });

    form.dispatchEvent(new CustomEvent('stepChanged'));
  }

  // =========================================================================
  // 2. Dynamic Owners (ملاك العقار)
  // =========================================================================
  const ownersList = document.getElementById('owners-list');
  const addOwnerBtn = document.getElementById('btn-add-owner');

  function getOwnerIndex() {
    return ownersList ? ownersList.querySelectorAll('.owner-item').length : 0;
  }

  function createOwnerItem(data = {}) {
    const index = getOwnerIndex();
    const item = document.createElement('div');
    item.className = 'owner-item card border p-3 mb-3';
    item.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 text-muted owner-number">المالك #${index + 1}</h6>
        ${index > 0 ? `
          <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-owner" aria-label="حذف المالك">
            <i class="ti ti-trash"></i>
          </button>` : ''}
      </div>
      <div class="row g-3">
        <div class="col-md-6" data-form-field data-field-name="owners[${index}][owner_name]">
          <label class="form-label">اسم المالك <span class="text-danger">*</span></label>
          <input type="text" name="owners[${index}][owner_name]" class="form-control"
                 value="${escapeHtml(data.owner_name || '')}" placeholder="أدخل اسم المالك" data-form-control required>
          <div class="invalid-feedback" data-form-error hidden></div>
        </div>
        <div class="col-md-6" data-form-field data-field-name="owners[${index}][national_id_or_cr]">
          <label class="form-label">رقم الهوية / السجل التجاري <span class="text-danger">*</span></label>
          <input type="number" step="1" min="0" name="owners[${index}][national_id_or_cr]" class="form-control"
                 value="${escapeHtml(data.national_id_or_cr || '')}" placeholder="أدخل رقم الهوية أو السجل" data-form-control required>
          <div class="invalid-feedback" data-form-error hidden></div>
        </div>
      </div>
    `;
    return item;
  }

  if (addOwnerBtn && ownersList) {
    addOwnerBtn.addEventListener('click', () => {
      ownersList.appendChild(createOwnerItem());
      reindexOwners();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });

    ownersList.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.btn-remove-owner');
      if (!removeBtn) return;
      removeBtn.closest('.owner-item').remove();
      reindexOwners();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function reindexOwners() {
    if (!ownersList) return;
    ownersList.querySelectorAll('.owner-item').forEach((item, index) => {
      const numberEl = item.querySelector('.owner-number');
      if (numberEl) numberEl.textContent = `المالك #${index + 1}`;

      item.querySelectorAll('input').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/owners\[\d+\]/, `owners[${index}]`));
        }
      });
      item.querySelectorAll('[data-field-name]').forEach((field) => {
        field.dataset.fieldName = String(field.dataset.fieldName || '').replace(/owners\[\d+\]/, `owners[${index}]`);
      });
    });
    form.dispatchEvent(new CustomEvent('stepChanged'));
  }

  // =========================================================================
  // 3. Dynamic Units (وحدات العقار)
  // =========================================================================
  const unitsList = document.getElementById('units-list');
  const addUnitBtn = document.getElementById('btn-add-unit');

  function getUnitIndex() {
    return unitsList ? unitsList.querySelectorAll('.unit-row').length : 0;
  }

  function createUnitRow(data = {}) {
    const index = getUnitIndex();
    const tr = document.createElement('tr');
    tr.className = 'unit-row';

    const statuses = window.propertyUnitStatuses || [
      { value: 'available', label: 'متاح' },
      { value: 'rented', label: 'مؤجر' },
      { value: 'sold', label: 'مباع' }
    ];

    const selectOptions = (options, value) => [
      '<option value=""></option>',
      ...options.map(option => `<option value="${option.value}" ${value === option.value ? 'selected' : ''}>${option.label}</option>`),
    ].join('');
    const statusOptions = selectOptions(statuses, data.status);
    const unitTypeOptions = selectOptions(window.propertyUnitTypes || [], data.unit_type);
    const unitUsageOptions = selectOptions(window.propertyUnitUsages || [], data.current_use);

    tr.innerHTML = `
      <td><input type="text" name="units[${index}][unit_number]" class="form-control form-control-sm"
                 value="${escapeHtml(data.unit_number || '')}" placeholder="وحدة ..." data-form-control></td>
      <td><select name="units[${index}][unit_type]" class="form-select form-select-sm select2" data-select2-search="false" data-form-control>${unitTypeOptions}</select></td>
      <td><input type="number" step="0.01" name="units[${index}][area]" class="form-control form-control-sm"
                 value="${escapeHtml(data.area || '')}" placeholder="المساحة" data-form-control></td>
      <td><select name="units[${index}][current_use]" class="form-select form-select-sm select2" data-select2-search="false" data-form-control>${unitUsageOptions}</select></td>
      <td>
        <select name="units[${index}][status]" class="form-select form-select-sm select2" data-select2-search="false" data-form-control>
          ${statusOptions}
        </select>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-unit" aria-label="حذف الوحدة">
          <i class="ti ti-trash"></i>
        </button>
      </td>
    `;
    return tr;
  }

  if (addUnitBtn && unitsList) {
    addUnitBtn.addEventListener('click', () => {
      unitsList.appendChild(createUnitRow());
      reindexUnits();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });

    unitsList.addEventListener('click', (e) => {
      const removeBtn = e.target.closest('.btn-remove-unit');
      if (!removeBtn) return;
      removeBtn.closest('.unit-row').remove();
      reindexUnits();
      form.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function reindexUnits() {
    if (!unitsList) return;
    if (window.jQuery?.fn?.select2) {
      window.jQuery(unitsList).find('select.select2-hidden-accessible').select2('destroy');
    }
    unitsList.querySelectorAll('.unit-row').forEach((row, index) => {
      row.querySelectorAll('input, select').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/units\[\d+\]/, `units[${index}]`));
        }
      });
    });
    form.dispatchEvent(new CustomEvent('stepChanged'));
  }

  // =========================================================================
  // 4. Interactive Property Image Boundary Selection & Annotation
  // =========================================================================
  const imageUploadInput = document.getElementById('property-image-upload');
  const emptyPlaceholder = document.getElementById('property-image-empty-placeholder');
  const canvasWrapper = document.getElementById('property-image-canvas-wrapper');
  const canvas = document.getElementById('property-drawing-canvas');
  const statusBar = document.getElementById('property-image-status-bar');
  const pointsBadge = document.getElementById('points-count-badge');
  const statusText = document.getElementById('polygon-status-text');
  const btnDownload = document.getElementById('btn-download-annotated');
  const btnClearBoundary = document.getElementById('btn-clear-boundary');
  const aerialImageInput = document.getElementById('property-aerial-image');
  const boundaryGeoJsonInput = document.getElementById('property-boundary-geojson');

  let loadedImage = null;
  let points = []; // Array of normalized { x: 0..1, y: 0..1 }
  let draggedPointIndex = null;
  let hoveredPointIndex = null;
  let annotatedDataUrl = '';

  function setImageActionVisibility(visible) {
    [btnDownload, btnClearBoundary].forEach((button) => {
      if (!button) return;
      button.classList.toggle('d-none', !visible);
      button.setAttribute('aria-hidden', visible ? 'false' : 'true');
    });
  }

  setImageActionVisibility(false);

  function notifyUser(type, message) {
    if (window.toastr && typeof window.toastr[type] === 'function') {
      window.toastr[type](message);
    }
  }

  function getCanvasCoords(event) {
    if (!canvas) return { x: 0, y: 0, pixelX: 0, pixelY: 0 };
    const rect = canvas.getBoundingClientRect();
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;
    const px = Math.max(0, Math.min(canvas.width, (clientX - rect.left) * (canvas.width / rect.width)));
    const py = Math.max(0, Math.min(canvas.height, (clientY - rect.top) * (canvas.height / rect.height)));
    return {
      x: px / canvas.width,
      y: py / canvas.height,
      pixelX: px,
      pixelY: py
    };
  }

  function findPointNear(pixelX, pixelY, threshold = 14) {
    if (!canvas || !points.length) return -1;
    for (let i = 0; i < points.length; i++) {
      const ptX = points[i].x * canvas.width;
      const ptY = points[i].y * canvas.height;
      const dist = Math.hypot(ptX - pixelX, ptY - pixelY);
      if (dist <= threshold) return i;
    }
    return -1;
  }

  function appendPolygonPath(context, width, height) {
    context.moveTo(points[0].x * width, points[0].y * height);
    for (let i = 1; i < points.length; i++) {
      context.lineTo(points[i].x * width, points[i].y * height);
    }
  }

  function drawOutsideSelectionOverlay(context, width, height) {
    if (points.length < 3) return;

    context.save();
    context.beginPath();
    context.rect(0, 0, width, height);
    appendPolygonPath(context, width, height);
    context.closePath();
    context.fillStyle = 'rgba(0, 0, 0, 0.5)';
    context.fill('evenodd');
    context.restore();
  }

  function drawCanvas() {
    if (!canvas || !loadedImage) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw background image
    ctx.drawImage(loadedImage, 0, 0, canvas.width, canvas.height);

    if (!points.length) {
      updateStatusUI();
      return;
    }

    // Dim the area outside the selected property boundary.
    drawOutsideSelectionOverlay(ctx, canvas.width, canvas.height);

    // Draw Polygon path
    ctx.beginPath();
    appendPolygonPath(ctx, canvas.width, canvas.height);

    if (points.length >= 3) {
      ctx.closePath();
      ctx.fillStyle = 'rgba(29, 79, 68, 0.28)';
      ctx.fill();
    }

    ctx.strokeStyle = '#1D4F44';
    ctx.lineWidth = 3;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.stroke();

    // Draw vertex handles
    points.forEach((pt, index) => {
      const px = pt.x * canvas.width;
      const py = pt.y * canvas.height;
      const isHovered = (hoveredPointIndex === index);
      const isDragged = (draggedPointIndex === index);
      const isFirst = (index === 0);

      ctx.beginPath();
      ctx.arc(px, py, isDragged ? 8 : (isHovered ? 7 : 5.5), 0, Math.PI * 2);
      ctx.fillStyle = isFirst ? '#F7F3EC' : (isDragged || isHovered ? '#ffc107' : '#ffffff');
      ctx.fill();
      ctx.strokeStyle = '#1D4F44';
      ctx.lineWidth = 2.5;
      ctx.stroke();

      ctx.fillStyle = '#1D4F44';
      ctx.font = 'bold 11px sans-serif';
      ctx.fillText(String(index + 1), px + 8, py - 6);
    });

    updateStatusUI();
  }

  function updateStatusUI() {
    if (!statusBar || !pointsBadge || !statusText) return;
    pointsBadge.textContent = `${points.length} نقاط محددة`;
    if (points.length === 0) {
      statusText.textContent = 'انقر على زوايا العقار لوضع نقاط الحدود';
      pointsBadge.className = 'badge bg-label-info';
    } else if (points.length < 3) {
      statusText.textContent = `حدد ${3 - points.length} نقاط إضافية على الأقل لإغلاق المضلع`;
      pointsBadge.className = 'badge bg-label-warning';
    } else {
      statusText.textContent = 'تم إغلاق وتحديد مضلع العقار بنجاح (يمكنك سحب أي نقطة لتعديلها)';
      pointsBadge.className = 'badge bg-label-success';
    }
  }

  function syncAnnotatedImage() {
    if (!loadedImage) return;

    if (points.length >= 3) {
      const naturalW = loadedImage.naturalWidth || loadedImage.width || 1200;
      const naturalH = loadedImage.naturalHeight || loadedImage.height || 800;
      const offscreen = document.createElement('canvas');
      offscreen.width = naturalW;
      offscreen.height = naturalH;
      const oCtx = offscreen.getContext('2d');

      oCtx.drawImage(loadedImage, 0, 0, naturalW, naturalH);

      // Keep the exported/annotated image visually consistent with the canvas.
      drawOutsideSelectionOverlay(oCtx, naturalW, naturalH);

      oCtx.beginPath();
      appendPolygonPath(oCtx, naturalW, naturalH);
      oCtx.closePath();

      oCtx.fillStyle = 'rgba(29, 79, 68, 0.32)';
      oCtx.fill();

      const strokeW = Math.max(4, Math.round(naturalW / 250));
      oCtx.strokeStyle = '#1D4F44';
      oCtx.lineWidth = strokeW;
      oCtx.lineJoin = 'round';
      oCtx.lineCap = 'round';
      oCtx.stroke();

      const vRadius = Math.max(6, Math.round(naturalW / 180));
      points.forEach((pt) => {
        const vx = pt.x * naturalW;
        const vy = pt.y * naturalH;
        oCtx.beginPath();
        oCtx.arc(vx, vy, vRadius, 0, Math.PI * 2);
        oCtx.fillStyle = '#ffffff';
        oCtx.fill();
        oCtx.strokeStyle = '#1D4F44';
        oCtx.lineWidth = Math.max(2.5, Math.round(strokeW / 2));
        oCtx.stroke();
      });

      annotatedDataUrl = offscreen.toDataURL('image/png');
      if (aerialImageInput) {
        aerialImageInput.value = annotatedDataUrl;
        aerialImageInput.dispatchEvent(new Event('input', { bubbles: true }));
        aerialImageInput.dispatchEvent(new Event('change', { bubbles: true }));
      }

      if (btnDownload) {
        btnDownload.disabled = false;
      }

      const ring = points.map((p) => [
        Number((p.x * 100).toFixed(4)),
        Number((p.y * 100).toFixed(4))
      ]);
      ring.push([ring[0][0], ring[0][1]]);
      const geoJson = {
        type: 'Polygon',
        coordinates: [ring]
      };
      if (boundaryGeoJsonInput) {
        boundaryGeoJsonInput.value = JSON.stringify(geoJson);
        boundaryGeoJsonInput.dispatchEvent(new Event('input', { bubbles: true }));
        boundaryGeoJsonInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
    } else {
      if (btnDownload) btnDownload.disabled = true;
      if (boundaryGeoJsonInput) {
        boundaryGeoJsonInput.value = '';
        boundaryGeoJsonInput.dispatchEvent(new Event('input', { bubbles: true }));
        boundaryGeoJsonInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    form.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function loadImageSrc(src, existingGeoJson = null) {
    if (!src) return;
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      loadedImage = img;
      setImageActionVisibility(true);
      if (emptyPlaceholder) emptyPlaceholder.classList.add('d-none');
      if (canvasWrapper) canvasWrapper.classList.remove('d-none');
      if (statusBar) statusBar.classList.remove('d-none');
      if (statusBar) statusBar.classList.add('d-flex');

      const maxW = 720;
      const scale = Math.min(1, maxW / img.naturalWidth);
      canvas.width = Math.round(img.naturalWidth * scale);
      canvas.height = Math.round(img.naturalHeight * scale);

      if (existingGeoJson) {
        let parsed = existingGeoJson;
        if (typeof existingGeoJson === 'string') {
          try { parsed = JSON.parse(existingGeoJson); } catch (e) { parsed = null; }
        }
        if (parsed?.type === 'Polygon' && Array.isArray(parsed?.coordinates?.[0])) {
          const ring = parsed.coordinates[0];
          const slice = ring.length > 3 ? ring.slice(0, ring.length - 1) : ring;
          points = slice.map((c) => ({
            x: Math.max(0, Math.min(1, Number(c[0]) / 100)),
            y: Math.max(0, Math.min(1, Number(c[1]) / 100))
          }));
        }
      }

      drawCanvas();
      syncAnnotatedImage();
    };
    img.src = src;
  }

  if (imageUploadInput) {
    imageUploadInput.addEventListener('change', (e) => {
      const file = e.target.files?.[0];
      if (!file) return;

      if (!file.type.startsWith('image/')) {
        notifyUser('error', 'يرجى اختيار ملف صورة صالح.');
        return;
      }

      const reader = new FileReader();
      reader.onload = (event) => {
        points = [];
        loadImageSrc(event.target.result);
        notifyUser('info', 'تم تحميل الصورة. انقر الآن على زوايا العقار لتحديد حدوده.');
      };
      reader.readAsDataURL(file);
    });
  }

  if (canvas) {
    canvas.addEventListener('mousedown', (e) => {
      if (!loadedImage) return;
      const coords = getCanvasCoords(e);
      const nearIndex = findPointNear(coords.pixelX, coords.pixelY);

      if (nearIndex >= 0) {
        draggedPointIndex = nearIndex;
        canvas.style.cursor = 'grabbing';
      } else {
        points.push({ x: coords.x, y: coords.y });
        drawCanvas();
        syncAnnotatedImage();
      }
    });

    window.addEventListener('mousemove', (e) => {
      if (!canvas || !loadedImage) return;

      if (draggedPointIndex !== null) {
        const coords = getCanvasCoords(e);
        points[draggedPointIndex] = {
          x: Math.max(0, Math.min(1, coords.x)),
          y: Math.max(0, Math.min(1, coords.y))
        };
        drawCanvas();
      } else {
        const coords = getCanvasCoords(e);
        const nearIndex = findPointNear(coords.pixelX, coords.pixelY);
        hoveredPointIndex = nearIndex;
        canvas.style.cursor = nearIndex >= 0 ? 'grab' : 'crosshair';
        drawCanvas();
      }
    });

    window.addEventListener('mouseup', () => {
      if (draggedPointIndex !== null) {
        draggedPointIndex = null;
        if (canvas) canvas.style.cursor = 'crosshair';
        syncAnnotatedImage();
      }
    });

    canvas.addEventListener('touchstart', (e) => {
      if (!loadedImage) return;
      e.preventDefault();
      const coords = getCanvasCoords(e);
      const nearIndex = findPointNear(coords.pixelX, coords.pixelY, 20);

      if (nearIndex >= 0) {
        draggedPointIndex = nearIndex;
      } else {
        points.push({ x: coords.x, y: coords.y });
        drawCanvas();
        syncAnnotatedImage();
      }
    }, { passive: false });

    canvas.addEventListener('touchmove', (e) => {
      if (draggedPointIndex !== null) {
        e.preventDefault();
        const coords = getCanvasCoords(e);
        points[draggedPointIndex] = {
          x: Math.max(0, Math.min(1, coords.x)),
          y: Math.max(0, Math.min(1, coords.y))
        };
        drawCanvas();
      }
    }, { passive: false });

    canvas.addEventListener('touchend', () => {
      if (draggedPointIndex !== null) {
        draggedPointIndex = null;
        syncAnnotatedImage();
      }
    });
  }

  if (btnClearBoundary) {
    btnClearBoundary.addEventListener('click', () => {
      points = [];
      annotatedDataUrl = '';
      drawCanvas();
      syncAnnotatedImage();
      if (aerialImageInput && loadedImage?.src && !loadedImage.src.startsWith('data:image/')) {
        aerialImageInput.value = loadedImage.src;
      } else if (aerialImageInput) {
        aerialImageInput.value = '';
      }
      notifyUser('info', 'تم مسح تحديد المضلع. يمكنك النقر للبدء من جديد.');
    });
  }

  if (btnDownload) {
    btnDownload.addEventListener('click', () => {
      if (!annotatedDataUrl) {
        notifyUser('warning', 'يرجى تحديد مضلع العقار أولاً قبل التحميل.');
        return;
      }
      const a = document.createElement('a');
      a.href = annotatedDataUrl;
      const propName = form.elements.namedItem('name')?.value?.trim() || 'property';
      a.download = `${propName}-marked-boundary.png`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      notifyUser('success', 'تم تحميل الصورة المحددة بنجاح إلى جهازك.');
    });
  }

  if (aerialImageInput && aerialImageInput.value) {
    loadImageSrc(aerialImageInput.value, boundaryGeoJsonInput?.value || null);
  }

  // =========================================================================
  // 5. Review Step (الخطوة 5: المراجعة والحفظ)
  // =========================================================================
  function getVal(name) {
    return form.elements.namedItem(name)?.value?.trim() || '-';
  }

  function getSelectedText(name) {
    const select = form.elements.namedItem(name);
    return select?.selectedOptions?.[0]?.textContent?.trim() || '-';
  }

  function filesFromInput(name) {
    return Array.from(form.elements.namedItem(name)?.files || []);
  }

  function buildAttachmentBox(title, files, icon) {
    const countLabel = `${files.length} ${files.length === 1 ? 'ملف' : 'ملفات'}`;
    const content = files.length
      ? files.map((file) => `<article class="assignment-file-card"><i class="ti ${icon} text-primary fs-4"></i><span class="assignment-file-card__name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span></article>`).join('')
      : '<p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>';

    return `<section class="assignment-properties-table-card mb-3"><header class="assignment-properties-table-card__header"><h6 class="mb-0">${title}</h6><span class="badge bg-label-primary">${countLabel}</span></header><div class="assignment-file-list p-3">${content}</div></section>`;
  }

  function safeAerialReviewMarkup(value) {
    const source = String(value || '');
    if (!/^(data:image\/(png|jpeg|webp);base64,|https?:\/\/|\/)/i.test(source)) return '';

    return `<figure class="property-aerial-review mt-3 mb-0"><img src="${escapeHtml(source)}" alt="الصورة الجوية للعقار بعد تحديد الحدود"><a class="property-aerial-review__download" href="${escapeHtml(source)}" download="property-boundary.png" aria-label="تحميل الصورة الجوية المحددة"><i class="ti ti-download" aria-hidden="true"></i></a></figure>`;
  }

  const additionalFiles = new Map();

  function renderAdditionalAttachmentBox(inputName, listId, countId, icon) {
    const list = document.getElementById(listId);
    const count = document.getElementById(countId);
    if (!list || !count) return;

    const files = additionalFiles.get(inputName) || filesFromInput(inputName);
    count.textContent = `${files.length} ${files.length === 1 ? 'ملف' : 'ملفات'}`;
    list.innerHTML = files.length
      ? files.map((file, index) => `<article class="assignment-file-card"><i class="ti ${icon} text-primary fs-4"></i><span class="assignment-file-card__name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span><button type="button" class="btn btn-icon btn-sm btn-label-danger" data-remove-additional-file="${inputName}" data-file-index="${index}" aria-label="حذف ${escapeHtml(file.name)}"><i class="ti ti-trash" aria-hidden="true"></i></button></article>`).join('')
      : '<p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>';
  }

  function refreshAdditionalAttachmentBoxes() {
    renderAdditionalAttachmentBox('property_images[]', 'property-images-cards', 'property-images-count', 'ti-photo');
    renderAdditionalAttachmentBox('additional_documents[]', 'additional-documents-cards', 'additional-documents-count', 'ti-paperclip');
  }

  ['property_images[]', 'additional_documents[]'].forEach((name) => {
    form.elements.namedItem(name)?.addEventListener('change', (event) => {
      additionalFiles.set(name, Array.from(event.currentTarget.files || []));
      refreshAdditionalAttachmentBoxes();
    });
  });

  form.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-additional-file]');
    if (!button || !window.DataTransfer) return;

    const inputName = button.dataset.removeAdditionalFile;
    const fileIndex = Number(button.dataset.fileIndex);
    const input = form.elements.namedItem(inputName);
    const files = [...(additionalFiles.get(inputName) || filesFromInput(inputName))];
    if (!input || !Number.isInteger(fileIndex) || !files[fileIndex]) return;

    files.splice(fileIndex, 1);
    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
    additionalFiles.set(inputName, files);
    refreshAdditionalAttachmentBoxes();
    form.dispatchEvent(new Event('change', { bubbles: true }));
  });

  function buildReview() {
    // Review Basic Info
    const reviewBasic = document.getElementById('review-basic');
    if (reviewBasic) {
      const basicFields = [
        ['اسم العقار', getVal('name')],
        ['نوع العقار', getSelectedText('type')],
        ['تصنيف الاستخدام', getSelectedText('usage_classification')],
        ['المساحة (م²)', getVal('area') + ' م²'],
        ['حالة العقار', getSelectedText('is_active')],
        ['الوصف المختصر', getVal('short_description')],
      ];
      reviewBasic.innerHTML = `<dl class="row mb-0">${basicFields.map(([label, val]) => `
        <dt class="col-sm-4 text-muted mb-2">${label}:</dt>
        <dd class="col-sm-8 fw-medium mb-2">${escapeHtml(val)}</dd>
      `).join('')}</dl>`;
    }

    // Review Location & Borders
    const reviewLocation = document.getElementById('review-location');
    if (reviewLocation) {
      const bordersCount = bordersList ? bordersList.querySelectorAll('.boundary-row').length : 0;
      const hasPolygon = Boolean(boundaryGeoJsonInput?.value && points.length >= 3);
      const hasImage = Boolean(aerialImageInput?.value || loadedImage);
      const locationFields = [
        ['المدينة', getSelectedText('city')],
        ['الحي', getVal('district')],
        ['المنطقة الإدارية', getVal('administrative_region')],
        ['رقم المخطط', getVal('plan_number')],
        ['رقم القطعة', getVal('parcel_number')],
        ['الصورة الجوية للعقار', hasImage ? 'تم رفع صورة العقار' : 'لم يتم الرفع'],
        ['تحديد حدود العقار', hasPolygon ? `تم تحديد مضلع العقار بنجاح (${points.length} نقاط)` : 'لم يتم التحديد بعد'],
        ['عدد الحدود المدخلة', `${bordersCount} حدود`],
      ];
      reviewLocation.innerHTML = `<dl class="row mb-0">${locationFields.map(([label, val]) => `
        <dt class="col-sm-4 text-muted mb-2">${label}:</dt>
        <dd class="col-sm-8 fw-medium mb-2">${escapeHtml(val)}</dd>
      `).join('')}</dl>${safeAerialReviewMarkup(aerialImageInput?.value)}`;
    }

    // Review Ownership
    const reviewOwnership = document.getElementById('review-ownership');
    if (reviewOwnership) {
      const ownersCount = ownersList ? ownersList.querySelectorAll('.owner-item').length : 0;
      const unitsCount = unitsList ? unitsList.querySelectorAll('.unit-row').length : 0;
      const ownershipFields = [
        ['رقم الصك', getVal('deed_number')],
        ['تاريخ الصك', getVal('deed_date')],
        ['جهة الإصدار', getVal('issuing_authority')],
        ['نوع الملكية', getSelectedText('ownership_type')],
        ['عدد الملاك', `${ownersCount} مالك`],
        ['اسم الوكيل', getVal('agent_name') || 'لا يوجد وكيل'],
        ['عدد الوحدات', unitsCount > 0 ? `${unitsCount} وحدات` : 'لا توجد وحدات'],
      ];
      reviewOwnership.innerHTML = `<dl class="row mb-0">${ownershipFields.map(([label, val]) => `
        <dt class="col-sm-4 text-muted mb-2">${label}:</dt>
        <dd class="col-sm-8 fw-medium mb-2">${escapeHtml(val)}</dd>
      `).join('')}</dl>`;
    }

    // Review Documents
    const reviewDocs = document.getElementById('review-files');
    if (reviewDocs) {
      const files = [];
      if (form.elements.namedItem('main_image')?.files?.[0]) files.push(form.elements.namedItem('main_image').files[0].name);
      if (form.elements.namedItem('deed_document')?.files?.[0]) files.push(form.elements.namedItem('deed_document').files[0].name);
      if (form.elements.namedItem('cadastral_plan')?.files?.[0]) files.push(form.elements.namedItem('cadastral_plan').files[0].name);
      if (form.elements.namedItem('coordinates_statement')?.files?.[0]) files.push(form.elements.namedItem('coordinates_statement').files[0].name);
      if (form.elements.namedItem('building_permit')?.files?.[0]) files.push(form.elements.namedItem('building_permit').files[0].name);
      if (form.elements.namedItem('assignment_letter_image')?.files?.[0]) files.push(form.elements.namedItem('assignment_letter_image').files[0].name);

      const addDocs = form.elements.namedItem('additional_documents[]')?.files;
      if (addDocs) {
        for (let i = 0; i < addDocs.length; i++) files.push(addDocs[i].name);
      }

      const propImages = form.elements.namedItem('property_images[]')?.files;
      if (propImages) {
        for (let i = 0; i < propImages.length; i++) files.push(propImages[i].name);
      }

      if (files.length) {
        reviewDocs.innerHTML = files.map(name => `
          <article class="assignment-file-card">
            <i class="ti ti-file text-primary fs-4"></i>
            <span class="assignment-file-card__name" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
          </article>
        `).join('');
      } else {
        reviewDocs.innerHTML = '<p class="text-muted mb-0">لم يتم اختيار ملفات بعد.</p>';
      }
    }

    if (reviewDocs) {
      const files = [];
      if (form.elements.namedItem('main_image')?.files?.[0]) files.push(form.elements.namedItem('main_image').files[0].name);
      if (form.elements.namedItem('deed_document')?.files?.[0]) files.push(form.elements.namedItem('deed_document').files[0].name);
      if (form.elements.namedItem('cadastral_plan')?.files?.[0]) files.push(form.elements.namedItem('cadastral_plan').files[0].name);
      if (form.elements.namedItem('coordinates_statement')?.files?.[0]) files.push(form.elements.namedItem('coordinates_statement').files[0].name);
      if (form.elements.namedItem('building_permit')?.files?.[0]) files.push(form.elements.namedItem('building_permit').files[0].name);
      if (form.elements.namedItem('assignment_letter_image')?.files?.[0]) files.push(form.elements.namedItem('assignment_letter_image').files[0].name);
      const standardFiles = files.length ? `<div class="assignment-file-list mt-3">${files.map(name => `
        <article class="assignment-file-card">
          <i class="ti ti-file text-primary fs-4"></i>
          <span class="assignment-file-card__name" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
        </article>
      `).join('')}</div>` : '';
      reviewDocs.innerHTML = [
        buildAttachmentBox('صور إضافية للمعرض', filesFromInput('property_images[]'), 'ti-photo'),
        buildAttachmentBox('مستندات إضافية', filesFromInput('additional_documents[]'), 'ti-paperclip'),
        standardFiles,
      ].join('');
    }

    const reviewName = document.getElementById('review-name');
    if (reviewName) reviewName.textContent = getVal('name') || 'اسم العقار';

    const reviewDesc = document.getElementById('review-description');
    if (reviewDesc) reviewDesc.textContent = getVal('short_description') || 'وصف العقار';
  }

  form.addEventListener('change', buildReview);
  form.addEventListener('input', buildReview);

  // Jump to step via edit buttons
  document.querySelectorAll('[data-edit-step]').forEach(btn => {
    btn.addEventListener('click', () => {
      const wizardEl = document.getElementById('wizard-validation');
      if (wizardEl && wizardEl.bsStepper) {
        wizardEl.bsStepper.to(Number(btn.dataset.editStep));
      }
    });
  });

  // Main Image preview in review card
  const mainImageInput = form.elements.namedItem('main_image');
  if (mainImageInput) {
    mainImageInput.addEventListener('change', () => {
      const file = mainImageInput.files?.[0];
      const previewImg = document.getElementById('review-image');
      if (file && previewImg) {
        previewImg.src = URL.createObjectURL(file);
      }
    });
  }

  // =========================================================================
  // 6. Wizard Draft Restoration
  // =========================================================================
  form.addEventListener('wizardDraftRestored', (event) => {
    const data = event.detail?.data || {};

    // Restore boundaries
    if (Array.isArray(data.boundaries) && data.boundaries.length && bordersList) {
      bordersList.innerHTML = '';
      data.boundaries.forEach((boundary) => {
        const row = createBorderRow(boundary);
        if (row) bordersList.appendChild(row);
      });
      reindexBorders();
    }

    // Restore owners
    if (Array.isArray(data.owners) && data.owners.length && ownersList) {
      ownersList.innerHTML = '';
      data.owners.forEach(o => ownersList.appendChild(createOwnerItem(o)));
      reindexOwners();
    }

    // Restore units
    if (Array.isArray(data.units) && data.units.length && unitsList) {
      unitsList.innerHTML = '';
      data.units.forEach(u => unitsList.appendChild(createUnitRow(u)));
      reindexUnits();
    }

    // Restore aerial image and boundary points
    if (data.aerial_image) {
      aerialImageInput.value = data.aerial_image;
      boundaryGeoJsonInput.value = data.boundary_geojson || '';
      loadImageSrc(data.aerial_image, data.boundary_geojson || null);
    } else if (data.boundary_geojson && aerialImageInput?.value) {
      loadImageSrc(aerialImageInput.value, data.boundary_geojson);
    }

    buildReview();
  });

  // Initial check
  refreshAdditionalAttachmentBoxes();
  buildReview();
});
