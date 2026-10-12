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
    row.querySelector('input[name*="[border_type]"]').value = data.border_type ?? '';
    row.querySelector('input[name*="[border_description]"]').value = data.border_description ?? '';
    row.querySelector('input[name*="[length]"]').value = data.length ?? '';

    return row;
  }

  if (addBorderBtn && bordersList) {
    addBorderBtn.addEventListener('click', () => {
      const row = createBorderRow();
      if (!row) return;
      bordersList.appendChild(row);
      reindexBorders();
      const position = markerInstance?.getLatLng();
      if (position) renderBoundaries(position.lat, position.lng);
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
      const position = markerInstance?.getLatLng();
      if (position) renderBoundaries(position.lat, position.lng);
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
  // 4. Leaflet Map, Google Places Search & Interactive Polygon Boundary
  // =========================================================================
  let mapInstance = null;
  let centerMarker = null;
  let currentPolygon = null;
  let vertexMarkers = [];
  let boundaryMaskLayer = null;

  const mapsInput = form.elements.namedItem('google_maps_url');
  const latInput = form.elements.namedItem('latitude');
  const lngInput = form.elements.namedItem('longitude');
  const mapWarningBox = document.getElementById('map-warning-box');
  const mapContainer = document.getElementById('property-leaflet-map');
  const boundaryGeoJsonInput = document.getElementById('property-boundary-geojson');

  const btnDrawBoundary = document.getElementById('btn-draw-boundary');
  const btnResetBoundary = document.getElementById('btn-reset-boundary');
  const btnClearBoundary = document.getElementById('btn-clear-boundary');

  const searchInput = document.getElementById('property-map-search-input');
  const searchBtn = document.getElementById('property-map-search-btn');
  const searchSpinner = document.getElementById('property-map-search-spinner');
  const searchResults = document.getElementById('property-map-search-results');

  const L = window.L || window.leaFlet;

  function notifyUser(type, message) {
    if (window.toastr && typeof window.toastr[type] === 'function') {
      window.toastr[type](message);
    }
  }

  function getBoundaryLengths() {
    const lengths = { north: 0, south: 0, east: 0, west: 0 };
    if (!bordersList) return lengths;

    bordersList.querySelectorAll('.boundary-row').forEach((row) => {
      const direction = row.querySelector('select[name*="[direction]"]')?.value;
      const length = Number.parseFloat(row.querySelector('input[name*="[length]"]')?.value);
      if (direction in lengths && Number.isFinite(length) && length > 0) {
        lengths[direction] = Math.max(lengths[direction], length);
      }
    });

    return lengths;
  }

  function latLngsToGeoJson(latLngs) {
    if (!Array.isArray(latLngs) || latLngs.length < 3) return null;
    const ring = latLngs.map((p) => [
      Number(Number(p.lng).toFixed(7)),
      Number(Number(p.lat).toFixed(7))
    ]);

    // Close linear ring if needed
    if (ring[0][0] !== ring[ring.length - 1][0] || ring[0][1] !== ring[ring.length - 1][1]) {
      ring.push([ring[0][0], ring[0][1]]);
    }

    return {
      type: 'Polygon',
      coordinates: [ring]
    };
  }

  function geoJsonToLatLngs(geoJson) {
    if (!geoJson || !L) return null;
    let parsed = geoJson;
    if (typeof geoJson === 'string') {
      try {
        parsed = JSON.parse(geoJson);
      } catch (e) {
        return null;
      }
    }

    if (parsed?.type !== 'Polygon' || !Array.isArray(parsed?.coordinates?.[0])) {
      return null;
    }

    const ring = parsed.coordinates[0];
    if (ring.length < 4) return null;

    // Discard the repeated closing vertex
    const points = ring.slice(0, ring.length - 1).map((c) => L.latLng(Number(c[1]), Number(c[0])));
    return points.length >= 3 ? points : null;
  }

  function syncGeoJsonInput(latLngs) {
    if (!boundaryGeoJsonInput) return;
    const geoJson = latLngsToGeoJson(latLngs);
    boundaryGeoJsonInput.value = geoJson ? JSON.stringify(geoJson) : '';
    boundaryGeoJsonInput.dispatchEvent(new Event('input', { bubbles: true }));
    boundaryGeoJsonInput.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function calculateCentroid(latLngs) {
    if (!Array.isArray(latLngs) || !latLngs.length) return null;
    let totalLat = 0;
    let totalLng = 0;
    latLngs.forEach((p) => {
      totalLat += p.lat;
      totalLng += p.lng;
    });
    return {
      lat: totalLat / latLngs.length,
      lng: totalLng / latLngs.length
    };
  }

  function updateDarkMask(latLngs) {
    if (!mapInstance || !L) return;
    const worldBounds = [
      [85.05112878, -180],
      [85.05112878, 180],
      [-85.05112878, 180],
      [-85.05112878, -180]
    ];

    if (!Array.isArray(latLngs) || latLngs.length < 3) {
      if (boundaryMaskLayer) {
        boundaryMaskLayer.remove();
        boundaryMaskLayer = null;
      }
      return;
    }

    if (!boundaryMaskLayer) {
      boundaryMaskLayer = L.polygon([worldBounds, latLngs], {
        stroke: false,
        fillColor: '#0a0d14',
        fillOpacity: 0.65,
        fillRule: 'evenodd',
        interactive: false
      }).addTo(mapInstance);
    } else {
      boundaryMaskLayer.setLatLngs([worldBounds, latLngs]);
    }
  }

  function updateCenterMarker(lat, lng) {
    if (!mapInstance || !L) return;
    const centerPinIcon = L.divIcon({
      className: 'property-center-pin',
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      html: '<div class="pin-badge"><i class="ti ti-map-pin"></i></div>'
    });

    if (!centerMarker) {
      centerMarker = L.marker([lat, lng], {
        icon: centerPinIcon,
        draggable: true,
        zIndexOffset: 1000
      }).addTo(mapInstance);

      centerMarker.on('dragend', () => {
        const pos = centerMarker.getLatLng();
        if (latInput) latInput.value = pos.lat.toFixed(7);
        if (lngInput) lngInput.value = pos.lng.toFixed(7);
        if (mapsInput && (!mapsInput.value || mapsInput.value.includes('google.com'))) {
          mapsInput.value = `https://maps.google.com/?q=${pos.lat.toFixed(7)},${pos.lng.toFixed(7)}`;
        }
        form.dispatchEvent(new Event('change', { bubbles: true }));
      });
    } else {
      centerMarker.setLatLng([lat, lng]);
    }

    if (latInput) latInput.value = lat.toFixed(7);
    if (lngInput) lngInput.value = lng.toFixed(7);
  }

  function clearVertexMarkers() {
    vertexMarkers.forEach((m) => m.remove());
    vertexMarkers = [];
  }

  function renderPolygonWithVertices(latLngs, shouldFit = false) {
    if (!mapInstance || !L) return;
    if (!Array.isArray(latLngs) || latLngs.length < 3) return;

    clearVertexMarkers();

    if (!currentPolygon) {
      currentPolygon = L.polygon(latLngs, {
        color: '#6366f1',
        weight: 3.5,
        opacity: 0.95,
        fillColor: '#6366f1',
        fillOpacity: 0.28
      }).addTo(mapInstance);
    } else {
      currentPolygon.setLatLngs(latLngs);
    }

    const vertexIcon = L.divIcon({
      className: 'polygon-vertex-handle',
      iconSize: [16, 16],
      iconAnchor: [8, 8],
      html: '<div class="vertex-dot"></div>'
    });

    latLngs.forEach((latLng, index) => {
      const handle = L.marker(latLng, {
        icon: vertexIcon,
        draggable: true,
        zIndexOffset: 500
      }).addTo(mapInstance);

      // Realtime dragging of vertex handles
      handle.on('drag', () => {
        const updatedLatLngs = currentPolygon.getLatLngs()[0] || currentPolygon.getLatLngs();
        updatedLatLngs[index] = handle.getLatLng();
        currentPolygon.setLatLngs(updatedLatLngs);
        updateDarkMask(updatedLatLngs);

        const centroid = calculateCentroid(updatedLatLngs);
        if (centroid && centerMarker) {
          centerMarker.setLatLng([centroid.lat, centroid.lng]);
          if (latInput) latInput.value = centroid.lat.toFixed(7);
          if (lngInput) lngInput.value = centroid.lng.toFixed(7);
        }

        syncGeoJsonInput(updatedLatLngs);
      });

      handle.on('dragend', () => {
        const updatedLatLngs = currentPolygon.getLatLngs()[0] || currentPolygon.getLatLngs();
        syncGeoJsonInput(updatedLatLngs);
        const centroid = calculateCentroid(updatedLatLngs);
        if (centroid) {
          updateCenterMarker(centroid.lat, centroid.lng);
        }
        form.dispatchEvent(new Event('change', { bubbles: true }));
      });

      vertexMarkers.push(handle);
    });

    updateDarkMask(latLngs);

    const center = calculateCentroid(latLngs);
    if (center) {
      updateCenterMarker(center.lat, center.lng);
    }

    syncGeoJsonInput(latLngs);

    if (shouldFit) {
      window.requestAnimationFrame(() => {
        mapInstance.invalidateSize();
        mapInstance.fitBounds(currentPolygon.getBounds(), {
          padding: [30, 30],
          maxZoom: 18
        });
      });
    }
  }

  function buildDefaultPolygonFromCenter(lat, lng) {
    const lengths = getBoundaryLengths();
    const width = Math.max(lengths.north, lengths.south, 30);
    const height = Math.max(lengths.east, lengths.west, 30);
    const latitudeMeters = 111320;
    const longitudeMeters = Math.max(latitudeMeters * Math.cos((lat * Math.PI) / 180), 1);

    const northLat = lat + (height / 2) / latitudeMeters;
    const southLat = lat - (height / 2) / latitudeMeters;
    const eastLng = lng + (width / 2) / longitudeMeters;
    const westLng = lng - (width / 2) / longitudeMeters;

    return [
      L.latLng(northLat, westLng),
      L.latLng(northLat, eastLng),
      L.latLng(southLat, eastLng),
      L.latLng(southLat, westLng)
    ];
  }

  function clearBoundarySelection() {
    if (currentPolygon) {
      currentPolygon.remove();
      currentPolygon = null;
    }
    clearVertexMarkers();
    if (boundaryMaskLayer) {
      boundaryMaskLayer.remove();
      boundaryMaskLayer = null;
    }
    if (boundaryGeoJsonInput) {
      boundaryGeoJsonInput.value = '';
      boundaryGeoJsonInput.dispatchEvent(new Event('input', { bubbles: true }));
      boundaryGeoJsonInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    notifyUser('warning', 'تم مسح تحديد الحدود.');
  }

  function initMap(lat = 24.7136, lng = 46.6753, zoom = 15) {
    if (!mapContainer || !L) return;

    if (!mapInstance) {
      mapInstance = L.map('property-leaflet-map', {
        attributionControl: true
      }).setView([lat, lng], zoom);

      // Remove Leaflet branding badge while keeping attribution legally required
      if (mapInstance.attributionControl) {
        mapInstance.attributionControl.setPrefix(false);
      }

      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles &copy; Esri, Maxar, Earthstar Geographics'
      }).addTo(mapInstance);

      // Custom GPS Locate Control
      const LocateControl = L.Control.extend({
        onAdd: function () {
          const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
          const link = L.DomUtil.create('a', 'leaflet-control-locate-btn', container);
          link.href = '#';
          link.title = 'تحديد موقعي الحالي';
          link.setAttribute('role', 'button');
          link.innerHTML = '<i class="ti ti-current-location"></i>';

          L.DomEvent.on(link, 'click', function (e) {
            L.DomEvent.stopPropagation(e);
            L.DomEvent.preventDefault(e);
            if (navigator.geolocation) {
              notifyUser('info', 'جاري تحديد موقعك الحالي...');
              navigator.geolocation.getCurrentPosition(
                (pos) => {
                  const uLat = pos.coords.latitude;
                  const uLng = pos.coords.longitude;
                  mapInstance.flyTo([uLat, uLng], 17);
                  updateCenterMarker(uLat, uLng);
                  if (mapsInput) {
                    mapsInput.value = `https://maps.google.com/?q=${uLat.toFixed(7)},${uLng.toFixed(7)}`;
                  }
                  if (currentPolygon) {
                    const shifted = buildDefaultPolygonFromCenter(uLat, uLng);
                    renderPolygonWithVertices(shifted, true);
                  }
                  notifyUser('success', 'تم تحديد موقعك الحالي بنجاح.');
                },
                () => {
                  notifyUser('error', 'تعذر الوصول إلى موقعك الحالي.');
                }
              );
            } else {
              notifyUser('warning', 'المتصفح لا يدعم تحديد الموقع الجغرافي.');
            }
          });
          return container;
        }
      });
      new LocateControl({ position: 'topleft' }).addTo(mapInstance);

      updateCenterMarker(lat, lng);
    } else {
      mapInstance.setView([lat, lng], zoom);
      updateCenterMarker(lat, lng);
      setTimeout(() => mapInstance.invalidateSize(), 200);
    }

    // Restore existing GeoJSON if available
    const existingGeoJson = boundaryGeoJsonInput?.value?.trim();
    if (existingGeoJson) {
      const points = geoJsonToLatLngs(existingGeoJson);
      if (points) {
        renderPolygonWithVertices(points, true);
        return;
      }
    }

    // Default polygon if coordinates are available
    if (latInput?.value && lngInput?.value) {
      const parsedLat = Number(latInput.value);
      const parsedLng = Number(lngInput.value);
      if (Number.isFinite(parsedLat) && Number.isFinite(parsedLng)) {
        const poly = buildDefaultPolygonFromCenter(parsedLat, parsedLng);
        renderPolygonWithVertices(poly, true);
      }
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
      initMap(coords.lat, coords.lng, 17);
      const poly = buildDefaultPolygonFromCenter(coords.lat, coords.lng);
      renderPolygonWithVertices(poly, true);
    } else {
      if (mapWarningBox) {
        mapWarningBox.style.display = 'block';
        mapWarningBox.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> تم إدخال الرابط، لكن تعذر قراءة الإحداثيات منه تلقائياً. يمكنك تحريك المؤشر على الخريطة لتحديد الموقع.';
      }
      initMap(24.7136, 46.6753, 12);
    }
  }

  // Toolbar Button 1: رسم الحدود على الخريطة
  if (btnDrawBoundary) {
    btnDrawBoundary.addEventListener('click', () => {
      const currentLat = Number(latInput?.value) || centerMarker?.getLatLng()?.lat || 24.7136;
      const currentLng = Number(lngInput?.value) || centerMarker?.getLatLng()?.lng || 46.6753;

      if (!currentPolygon) {
        const poly = buildDefaultPolygonFromCenter(currentLat, currentLng);
        renderPolygonWithVertices(poly, true);
        notifyUser('success', 'تم رسم حدود العقار. اسحب زوايا الشكل بالماوس لتعديل الأبعاد بدقة.');
      } else {
        mapInstance.fitBounds(currentPolygon.getBounds(), { padding: [30, 30], maxZoom: 18 });
        notifyUser('info', 'يمكنك سحب النقاط الزرقاء على زوايا الحدود لتعديل الشكل لحظياً.');
      }
    });
  }

  // Toolbar Button 2: تحديد عبر الإحداثيات
  if (btnResetBoundary) {
    btnResetBoundary.addEventListener('click', () => {
      const currentLat = Number(latInput?.value) || centerMarker?.getLatLng()?.lat || 24.7136;
      const currentLng = Number(lngInput?.value) || centerMarker?.getLatLng()?.lng || 46.6753;

      const poly = buildDefaultPolygonFromCenter(currentLat, currentLng);
      renderPolygonWithVertices(poly, true);
      notifyUser('info', 'تم تحديد وضبط حدود العقار بناءً على الإحداثيات وأطوال الحدود المدخلة.');
    });
  }

  // Toolbar Button 3: مسح التحديد
  if (btnClearBoundary) {
    btnClearBoundary.addEventListener('click', clearBoundarySelection);
  }

  // Location Search via Server-Side Google Places Endpoint
  let searchTimeout = null;
  const searchEndpoint = window.routes?.propertyLocationSearch || mapContainer?.dataset?.searchUrl || '';

  async function executeLocationSearch() {
    const query = searchInput?.value?.trim() || '';
    if (query.length < 3) {
      if (searchResults) searchResults.classList.add('d-none');
      return;
    }

    if (searchSpinner) searchSpinner.classList.remove('d-none');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('input[name="_token"]')?.value;

    try {
      const response = await fetch(searchEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken || ''
        },
        body: JSON.stringify({
          query: query,
          latitude: Number(latInput?.value) || 24.7136,
          longitude: Number(lngInput?.value) || 46.6753
        })
      });

      const result = await response.json();
      if (searchSpinner) searchSpinner.classList.add('d-none');

      if (searchResults) {
        searchResults.innerHTML = '';
        if (result.success && Array.isArray(result.places) && result.places.length > 0) {
          result.places.forEach((place) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action text-start';
            item.innerHTML = `
              <div class="fw-semibold text-truncate"><i class="ti ti-map-pin me-1 text-primary"></i>${escapeHtml(place.name)}</div>
              <small class="text-muted text-truncate d-block">${escapeHtml(place.address || '')}</small>
            `;
            item.addEventListener('click', () => {
              const placeLat = Number(place.latitude);
              const placeLng = Number(place.longitude);
              mapInstance.flyTo([placeLat, placeLng], 17);
              updateCenterMarker(placeLat, placeLng);

              if (mapsInput) {
                mapsInput.value = `https://maps.google.com/?q=${placeLat.toFixed(7)},${placeLng.toFixed(7)}`;
              }

              const poly = buildDefaultPolygonFromCenter(placeLat, placeLng);
              renderPolygonWithVertices(poly, true);

              searchResults.classList.add('d-none');
              if (searchInput) searchInput.value = place.name;
              notifyUser('success', `تم الانتقال إلى الموقع: ${place.name}`);
            });
            searchResults.appendChild(item);
          });
          searchResults.classList.remove('d-none');
        } else {
          searchResults.innerHTML = '<div class="list-group-item text-muted small py-2">لم يتم العثور على نتائج مطابقة.</div>';
          searchResults.classList.remove('d-none');
        }
      }
    } catch (err) {
      if (searchSpinner) searchSpinner.classList.add('d-none');
      if (searchResults) {
        searchResults.innerHTML = '<div class="list-group-item text-danger small py-2">حدث خطأ أثناء البحث. يرجى المحاولة لاحقاً.</div>';
        searchResults.classList.remove('d-none');
      }
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(executeLocationSearch, 350);
    });

    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        executeLocationSearch();
      }
    });

    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchResults?.contains(e.target)) {
        if (searchResults) searchResults.classList.add('d-none');
      }
    });
  }

  if (searchBtn) {
    searchBtn.addEventListener('click', executeLocationSearch);
  }

  if (mapsInput) {
    mapsInput.addEventListener('input', updateMapFromUrl);
    mapsInput.addEventListener('change', updateMapFromUrl);
  }

  if (bordersList) {
    bordersList.addEventListener('input', () => {
      if (currentPolygon && centerMarker) {
        const center = centerMarker.getLatLng();
        const poly = buildDefaultPolygonFromCenter(center.lat, center.lng);
        renderPolygonWithVertices(poly, false);
      }
    });
  }

  // Initial Map Bootstrapping
  const initialLat = Number(latInput?.value) || 24.7136;
  const initialLng = Number(lngInput?.value) || 46.6753;
  initMap(initialLat, initialLng, initialLat !== 24.7136 ? 17 : 14);

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
      const hasPolygon = Boolean(currentPolygon || boundaryGeoJsonInput?.value);
      const locationFields = [
        ['المدينة', getSelectedText('city')],
        ['الحي', getVal('district')],
        ['المنطقة الإدارية', getVal('administrative_region')],
        ['رقم المخطط', getVal('plan_number')],
        ['رقم القطعة', getVal('parcel_number')],
        ['الإحداثيات', (getVal('latitude') !== '-' && getVal('longitude') !== '-') ? `${getVal('latitude')}, ${getVal('longitude')}` : 'غير محددة'],
        ['حدود العقار (الخريطة)', hasPolygon ? 'تم تحديد ورسم الحدود بنجاح' : 'لم يتم التحديد بعد'],
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

    if (data.boundary_geojson) {
      const points = geoJsonToLatLngs(data.boundary_geojson);
      if (points) {
        renderPolygonWithVertices(points, true);
      }
    } else if (data.google_maps_url) {
      updateMapFromUrl();
    }

    buildReview();
  });

  // Initial check
  buildReview();
});
