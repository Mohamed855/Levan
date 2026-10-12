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

  function getBorderIndex() {
    return bordersList ? bordersList.querySelectorAll('.boundary-row').length : 0;
  }

  function createBorderRow(data = {}) {
    const index = getBorderIndex();
    const row = document.createElement('tr');
    row.className = 'boundary-row';
    row.innerHTML = `
      <td>
        <select name="boundaries[${index}][direction]" class="form-select form-select-sm" data-form-control required>
          <option value="north" ${data.direction === 'north' ? 'selected' : ''}>الشمال</option>
          <option value="south" ${data.direction === 'south' ? 'selected' : ''}>الجنوب</option>
          <option value="east" ${data.direction === 'east' ? 'selected' : ''}>الشرق</option>
          <option value="west" ${data.direction === 'west' ? 'selected' : ''}>الغرب</option>
        </select>
      </td>
      <td>
        <input type="text" name="boundaries[${index}][border_type]" class="form-control form-control-sm"
               value="${escapeHtml(data.border_type || '')}" placeholder="شارع / قطعة..." data-form-control required>
      </td>
      <td>
        <input type="text" name="boundaries[${index}][border_description]" class="form-control form-control-sm"
               value="${escapeHtml(data.border_description || '')}" placeholder="عرض 20 متر..." data-form-control required>
      </td>
      <td>
        <input type="number" step="0.01" name="boundaries[${index}][length]" class="form-control form-control-sm"
               value="${escapeHtml(data.length || '')}" placeholder="0.00" data-form-control required>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-border" aria-label="حذف الحد">
          <i class="ti ti-trash"></i>
        </button>
      </td>
    `;
    return row;
  }

  if (addBorderBtn && bordersList) {
    addBorderBtn.addEventListener('click', () => {
      bordersList.appendChild(createBorderRow());
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
    bordersList.querySelectorAll('.boundary-row').forEach((row, index) => {
      row.querySelectorAll('input, select').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/boundaries\[\d+\]/, `boundaries[${index}]`));
        }
      });
    });
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
        <div class="col-md-6">
          <label class="form-label">اسم المالك <span class="text-danger">*</span></label>
          <input type="text" name="owners[${index}][owner_name]" class="form-control"
                 value="${escapeHtml(data.owner_name || '')}" placeholder="أدخل اسم المالك" data-form-control required>
        </div>
        <div class="col-md-6">
          <label class="form-label">رقم الهوية / السجل التجاري <span class="text-danger">*</span></label>
          <input type="text" name="owners[${index}][national_id_or_cr]" class="form-control"
                 value="${escapeHtml(data.national_id_or_cr || '')}" placeholder="أدخل رقم الهوية أو السجل" data-form-control required>
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
      const numLabel = item.querySelector('.owner-number');
      if (numLabel) numLabel.textContent = `المالك #${index + 1}`;
      item.querySelectorAll('input').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/owners\[\d+\]/, `owners[${index}]`));
        }
      });
    });
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
    const row = document.createElement('tr');
    row.className = 'unit-row';
    row.innerHTML = `
      <td>
        <input type="text" name="units[${index}][unit_number]" class="form-control form-control-sm"
               value="${escapeHtml(data.unit_number || (index + 1))}" placeholder="رقم الوحدة" data-form-control>
      </td>
      <td>
        <input type="text" name="units[${index}][unit_type]" class="form-control form-control-sm"
               value="${escapeHtml(data.unit_type || '')}" placeholder="فيلا / ملحق / شقة..." data-form-control>
      </td>
      <td>
        <input type="number" step="0.01" name="units[${index}][area]" class="form-control form-control-sm"
               value="${escapeHtml(data.area || '')}" placeholder="المساحة م²" data-form-control>
      </td>
      <td>
        <input type="text" name="units[${index}][current_use]" class="form-control form-control-sm"
               value="${escapeHtml(data.current_use || '')}" placeholder="سكني / تجاري..." data-form-control>
      </td>
      <td>
        <select name="units[${index}][status]" class="form-select form-select-sm" data-form-control>
          <option value="available" ${data.status === 'available' ? 'selected' : ''}>متاح</option>
          <option value="rented" ${data.status === 'rented' ? 'selected' : ''}>مؤجر</option>
          <option value="sold" ${data.status === 'sold' ? 'selected' : ''}>مباع</option>
        </select>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-unit" aria-label="حذف الوحدة">
          <i class="ti ti-trash"></i>
        </button>
      </td>
    `;
    return row;
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
    unitsList.querySelectorAll('.unit-row').forEach((row, index) => {
      row.querySelectorAll('input, select').forEach((input) => {
        const name = input.getAttribute('name');
        if (name) {
          input.setAttribute('name', name.replace(/units\[\d+\]/, `units[${index}]`));
        }
      });
    });
  }

  // =========================================================================
  // 4. Leaflet Map & Google Maps URL Coordinates Parser
  // =========================================================================
  let mapInstance = null;
  let markerInstance = null;
  const mapsInput = form.elements.namedItem('google_maps_url');
  const latInput = form.elements.namedItem('latitude');
  const lngInput = form.elements.namedItem('longitude');
  const mapWarningBox = document.getElementById('map-warning-box');
  const mapContainer = document.getElementById('property-leaflet-map');

  function initMap(lat = 24.7136, lng = 46.6753, zoom = 14) {
    if (!mapContainer) return;
    const L = window.L || window.leaFlet;
    if (!L) return;

    if (!mapInstance) {
      mapInstance = L.map('property-leaflet-map').setView([lat, lng], zoom);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(mapInstance);

      markerInstance = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);

      markerInstance.on('dragend', function (e) {
        const position = markerInstance.getLatLng();
        if (latInput) latInput.value = position.lat.toFixed(7);
        if (lngInput) lngInput.value = position.lng.toFixed(7);
        form.dispatchEvent(new Event('change', { bubbles: true }));
      });
    } else {
      mapInstance.setView([lat, lng], zoom);
      if (markerInstance) {
        markerInstance.setLatLng([lat, lng]);
      }
      setTimeout(() => mapInstance.invalidateSize(), 200);
    }
  }

  function parseGoogleMapsUrl(url) {
    if (!url) return null;
    let match = url.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };

    match = url.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };

    match = url.match(/[?&]ll=(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };

    match = url.match(/place\/(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };

    return null;
  }

  function updateMapFromUrl() {
    const url = mapsInput?.value?.trim();
    if (!url) {
      if (mapWarningBox) mapWarningBox.style.display = 'block';
      return;
    }

    const coords = parseGoogleMapsUrl(url);
    if (coords) {
      if (latInput) latInput.value = coords.lat.toFixed(7);
      if (lngInput) lngInput.value = coords.lng.toFixed(7);
      if (mapWarningBox) mapWarningBox.style.display = 'none';
      initMap(coords.lat, coords.lng, 16);
    } else {
      // Still show warning if coordinates couldn't be parsed
      if (mapWarningBox) {
        mapWarningBox.style.display = 'block';
        mapWarningBox.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> تم إدخال الرابط، لكن تعذر قراءة الإحداثيات منه تلقائياً. يمكنك تحريك المؤشر على الخريطة لتحديد الموقع.';
      }
      initMap(24.7136, 46.6753, 12);
    }
  }

  if (mapsInput) {
    mapsInput.addEventListener('input', updateMapFromUrl);
    mapsInput.addEventListener('change', updateMapFromUrl);
  }

  // Invalidate map size on step change
  document.querySelectorAll('[data-bs-toggle="tab"], .step, [data-wizard-next], [data-wizard-prev]').forEach(btn => {
    btn.addEventListener('click', () => {
      setTimeout(() => {
        if (mapInstance) mapInstance.invalidateSize();
      }, 300);
    });
  });

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
      const locationFields = [
        ['المدينة', getSelectedText('city')],
        ['الحي', getVal('district')],
        ['المنطقة الإدارية', getVal('administrative_region')],
        ['رقم المخطط', getVal('plan_number')],
        ['رقم القطعة', getVal('parcel_number')],
        ['الإحداثيات', (getVal('latitude') !== '-' && getVal('longitude') !== '-') ? `${getVal('latitude')}, ${getVal('longitude')}` : 'غير محددة'],
        ['عدد الحدود المدخلة', `${bordersCount} حدود`],
      ];
      reviewLocation.innerHTML = `<dl class="row mb-0">${locationFields.map(([label, val]) => `
        <dt class="col-sm-4 text-muted mb-2">${label}:</dt>
        <dd class="col-sm-8 fw-medium mb-2">${escapeHtml(val)}</dd>
      `).join('')}</dl>`;
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
    const reviewDocs = document.getElementById('review-documents');
    if (reviewDocs) {
      const files = [];
      if (form.elements.namedItem('deed_document')?.files?.[0]) files.push('صك الملكية: ' + form.elements.namedItem('deed_document').files[0].name);
      if (form.elements.namedItem('cadastral_plan')?.files?.[0]) files.push('مخطط العقار: ' + form.elements.namedItem('cadastral_plan').files[0].name);
      if (form.elements.namedItem('coordinates_statement')?.files?.[0]) files.push('كشف الإحداثيات: ' + form.elements.namedItem('coordinates_statement').files[0].name);
      if (form.elements.namedItem('building_permit')?.files?.[0]) files.push('رخصة البناء: ' + form.elements.namedItem('building_permit').files[0].name);
      if (form.elements.namedItem('main_image')?.files?.[0]) files.push('الصورة الرئيسية: ' + form.elements.namedItem('main_image').files[0].name);

      const addDocs = form.elements.namedItem('additional_documents[]')?.files;
      if (addDocs && addDocs.length) files.push(`مستندات إضافية (${addDocs.length} ملفات)`);

      const propImages = form.elements.namedItem('property_images[]')?.files;
      if (propImages && propImages.length) files.push(`صور إضافية (${propImages.length} صور)`);

      if (files.length) {
        reviewDocs.innerHTML = `<ul class="list-unstyled mb-0">${files.map(f => `
          <li class="mb-1 text-success"><i class="ti ti-check me-1"></i> ${escapeHtml(f)}</li>
        `).join('')}</ul>`;
      } else {
        reviewDocs.innerHTML = '<p class="text-muted mb-0">لم يتم اختيار ملفات بعد.</p>';
      }
    }

    // Checklist status
    const isStep1Ok = getVal('name') !== '-' && getVal('area') !== '-';
    const isStep2Ok = getVal('city') !== '-' && getVal('plan_number') !== '-';
    const isStep3Ok = getVal('deed_number') !== '-' && getVal('issuing_authority') !== '-';
    const isStep4Ok = !!(form.elements.namedItem('deed_document')?.files?.[0] || form.elements.namedItem('main_image')?.files?.[0]);

    updateChecklistItem('check-step-1', isStep1Ok);
    updateChecklistItem('check-step-2', isStep2Ok);
    updateChecklistItem('check-step-3', isStep3Ok);
    updateChecklistItem('check-step-4', isStep4Ok);
  }

  function updateChecklistItem(id, isOk) {
    const el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = isOk
      ? '<span class="badge bg-label-success"><i class="ti ti-check me-1"></i> مكتمل</span>'
      : '<span class="badge bg-label-warning"><i class="ti ti-clock me-1"></i> قيد الإدخال</span>';
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
      const previewImg = document.getElementById('review-main-image-preview');
      if (file && previewImg) {
        previewImg.src = URL.createObjectURL(file);
        previewImg.style.display = 'block';
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
      data.boundaries.forEach(b => bordersList.appendChild(createBorderRow(b)));
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

    if (data.google_maps_url) {
      updateMapFromUrl();
    }

    buildReview();
  });

  // Initial check
  buildReview();
});
