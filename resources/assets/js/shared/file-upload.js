/**
 * Shared File Upload Component Handler
 * Provides Drag & Drop, Multi-file management, Live Previews, and Event hooks.
 */

function formatFileSize(bytes) {
  if (!bytes || bytes <= 0) return '0 Bytes';
  const units = ['Bytes', 'KB', 'MB', 'GB'];
  const index = Math.floor(Math.log(bytes) / Math.log(1024));
  return Math.round((bytes / Math.pow(1024, index)) * 100) / 100 + ' ' + units[index];
}

function getFileIconClass(file) {
  const name = (file.name || '').toLowerCase();
  const type = (file.type || '').toLowerCase();

  if (type.includes('pdf') || name.endsWith('.pdf')) {
    return { icon: 'ti-file-type-pdf', classModifier: 'file-item-icon-pdf' };
  }
  if (type.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) {
    return { icon: 'ti-file-description', classModifier: 'file-item-icon-word' };
  }
  if (type.includes('sheet') || type.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx') || name.endsWith('.csv')) {
    return { icon: 'ti-file-spreadsheet', classModifier: 'file-item-icon-excel' };
  }
  if (type.startsWith('image/') || /\.(png|jpe?g|webp|gif|svg)$/i.test(name)) {
    return { icon: 'ti-photo', classModifier: 'file-item-icon-image' };
  }
  return { icon: 'ti-file', classModifier: '' };
}

function showNotification(message, type = 'error') {
  if (window.toastr && typeof window.toastr[type] === 'function') {
    window.toastr[type](message);
  } else if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
    toastr[type](message);
  } else {
    alert(message);
  }
}

export function initFileUploadZone(zone) {
  if (!zone) return null;
  if (zone.dataset.fileUploadInitialized === 'true') {
    return zone._fileUploadController || null;
  }
  zone.dataset.fileUploadInitialized = 'true';

  const controlWrapper = zone.closest('[data-file-upload-wrapper]') || zone.parentElement;
  const input = controlWrapper.querySelector('input[type="file"][data-file-input]') || controlWrapper.querySelector('input[type="file"]');
  const previewContainer = controlWrapper.querySelector('[data-file-preview-container]');

  if (!input) return null;

  const isMultiple = input.hasAttribute('multiple') || controlWrapper.dataset.isMultiple === 'true';
  const wasRequired = input.dataset.wasRequired === 'true' || controlWrapper.dataset.wasRequired === 'true' || input.hasAttribute('required');
  input.dataset.wasRequired = wasRequired ? 'true' : 'false';
  const maxMb = Number(zone.dataset.maxSizeMb || input.dataset.maxSizeMb || 10);
  let currentFiles = [];

  // Handle clicking on existing file remove buttons via delegation
  if (previewContainer) {
    previewContainer.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-remove-existing-file]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      const existingCard = btn.closest('[data-existing-file-card]');
      const removeInputName = btn.dataset.removeExistingFile;
      const removeMode = btn.dataset.removeMode || (isMultiple || String(removeInputName || '').endsWith('[]') ? 'multiple' : 'single');
      const removeValue = btn.dataset.removeValue || existingCard?.dataset.fileId || existingCard?.dataset.filePath || '1';

      if (removeInputName) {
        if (removeMode === 'multiple') {
          const existingHidden = Array.from(controlWrapper.querySelectorAll('input[type="hidden"]'))
            .find((el) => el.name === removeInputName && el.value === String(removeValue));
          if (!existingHidden) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = removeInputName;
            hiddenInput.value = String(removeValue);
            hiddenInput.dataset.generatedRemoveInput = 'multiple';
            controlWrapper.appendChild(hiddenInput);
          }
        } else {
          let hiddenInput = Array.from(controlWrapper.querySelectorAll('input[type="hidden"]'))
            .find((el) => el.name === removeInputName);
          if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = removeInputName;
            hiddenInput.dataset.generatedRemoveInput = 'single';
            controlWrapper.appendChild(hiddenInput);
          }
          hiddenInput.value = '1';
        }
      }
      if (existingCard) existingCard.remove();

      const remainingExisting = previewContainer.querySelectorAll('[data-existing-file-card]');
      if (remainingExisting.length === 0) {
        controlWrapper.dataset.hasExisting = 'false';
        input.dataset.hasExisting = 'false';
        if (input.dataset.wasRequired === 'true' && currentFiles.length === 0) {
          input.required = true;
          input.setAttribute('required', '');
        }
      }

      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      zone.dispatchEvent(new CustomEvent('file:removed-existing', {
        bubbles: true,
        detail: { input, removeInputName, removeMode, removeValue },
      }));
    });
  }

  let isSyncing = false;

  function syncInput() {
    if (!window.DataTransfer) return;
    const transfer = new DataTransfer();
    currentFiles.forEach((f) => transfer.items.add(f));
    isSyncing = true;
    input.files = transfer.files;
    isSyncing = false;
  }

  function syncAndDispatch() {
    if (!window.DataTransfer) return;
    const transfer = new DataTransfer();
    currentFiles.forEach((f) => transfer.items.add(f));
    isSyncing = true;
    input.files = transfer.files;

    if (currentFiles.length > 0) {
      zone.classList.add('has-files');
      if (!isMultiple) {
        controlWrapper.querySelectorAll('input[type="hidden"][data-generated-remove-input="single"]').forEach((el) => {
          el.value = '0';
        });
      }
      if (input.required) {
        input.required = false;
        input.removeAttribute('required');
      }
    } else {
      zone.classList.remove('has-files');
      const hasRemainingExisting = Boolean(previewContainer?.querySelector('[data-existing-file-card]'));
      if (hasRemainingExisting) {
        input.required = false;
        input.removeAttribute('required');
      } else if (input.dataset.wasRequired === 'true') {
        input.required = true;
        input.setAttribute('required', '');
      }
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    isSyncing = false;
    zone.dispatchEvent(new CustomEvent('file:changed', { bubbles: true, detail: { files: currentFiles, count: currentFiles.length, input } }));
  }

  function renderPreviews() {
    if (!previewContainer) return;

    // Retain any existing cards that are not dynamically uploaded
    const existingCards = previewContainer.querySelectorAll('[data-existing-file-card]');
    previewContainer.innerHTML = '';

    if (!isMultiple) {
      if (currentFiles.length > 0) {
        // Hide existing cards and disable their hidden inputs when a new replacement file is chosen
        existingCards.forEach((c) => {
          c.classList.add('d-none');
          const existingInput = c.querySelector('[data-existing-file-input]');
          if (existingInput) existingInput.disabled = true;
          previewContainer.appendChild(c);
        });
      } else {
        // Restore existing cards and re-enable their hidden inputs when no new file is active
        existingCards.forEach((c) => {
          c.classList.remove('d-none');
          const existingInput = c.querySelector('[data-existing-file-input]');
          if (existingInput) existingInput.disabled = false;
          previewContainer.appendChild(c);
        });
      }
    } else {
      // In multiple mode, retain all existing cards visible and enabled
      existingCards.forEach((c) => {
        c.classList.remove('d-none');
        const existingInput = c.querySelector('[data-existing-file-input]');
        if (existingInput) existingInput.disabled = false;
        previewContainer.appendChild(c);
      });
    }

    currentFiles.forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'file-item';

      const isImage = file.type.startsWith('image/');
      if (isImage) {
        const img = document.createElement('img');
        img.className = 'file-item-preview';
        img.alt = file.name;
        const reader = new FileReader();
        reader.onload = (e) => {
          img.src = e.target.result;
        };
        reader.readAsDataURL(file);
        item.appendChild(img);
      } else {
        const iconInfo = getFileIconClass(file);
        const iconDiv = document.createElement('div');
        iconDiv.className = `file-item-icon ${iconInfo.classModifier}`;
        iconDiv.innerHTML = `<i class="ti ${iconInfo.icon}"></i>`;
        item.appendChild(iconDiv);
      }

      const infoDiv = document.createElement('div');
      infoDiv.className = 'file-item-info';

      const nameDiv = document.createElement('div');
      nameDiv.className = 'file-item-name';
      nameDiv.title = file.name;
      nameDiv.textContent = file.name;

      const sizeDiv = document.createElement('div');
      sizeDiv.className = 'file-item-size';
      sizeDiv.textContent = formatFileSize(file.size);

      infoDiv.appendChild(nameDiv);
      infoDiv.appendChild(sizeDiv);
      item.appendChild(infoDiv);

      const actionsWrap = document.createElement('div');
      actionsWrap.className = 'file-item-actions';

      const objectUrl = !file._draftUrl && window.URL && typeof window.URL.createObjectURL === 'function' && file.size > 0
        ? window.URL.createObjectURL(file)
        : null;
      const viewUrl = file._draftUrl || objectUrl;
      if (viewUrl) {
        const viewLink = document.createElement('a');
        viewLink.href = viewUrl;
        viewLink.target = '_blank';
        viewLink.rel = 'noopener noreferrer';
        viewLink.className = 'btn-view-file text-decoration-none';
        viewLink.style.textDecoration = 'none';
        viewLink.title = 'معاينة الملف';
        viewLink.setAttribute('aria-label', `معاينة ${file.name}`);
        viewLink.innerHTML = '<i class="ti ti-eye"></i>';
        actionsWrap.appendChild(viewLink);

        const downloadLink = document.createElement('a');
        downloadLink.href = file._draftDownloadUrl || viewUrl;
        downloadLink.download = file.name || 'file';
        downloadLink.className = 'btn-download-file text-decoration-none';
        downloadLink.style.textDecoration = 'none';
        downloadLink.title = 'تنزيل الملف';
        downloadLink.setAttribute('aria-label', `تنزيل ${file.name}`);
        downloadLink.innerHTML = '<i class="ti ti-download"></i>';
        actionsWrap.appendChild(downloadLink);
      }

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn-remove-file';
      removeBtn.setAttribute('aria-label', `حذف ${file.name}`);
      removeBtn.title = 'حذف الملف';
      removeBtn.innerHTML = '<i class="ti ti-trash"></i>';

      removeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const removed = currentFiles.splice(index, 1)[0];
        syncAndDispatch();
        renderPreviews();

        if (removed && removed._draftKey) {
          const form = controlWrapper.closest('form');
          let draftFileUrl = form?.dataset.draftFileUrl
            || (form?.dataset.draftUrl ? `${form.dataset.draftUrl.replace(/\/$/, '')}/file` : '')
            || (document.body?.dataset.wizardDraftsUrl ? `${document.body.dataset.wizardDraftsUrl.replace(/\/$/, '')}/file` : '');
          if (draftFileUrl) {
            try {
              const parsed = new URL(draftFileUrl, window.location.origin);
              if (parsed.origin !== window.location.origin && (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1')) {
                draftFileUrl = parsed.pathname + parsed.search;
              }
            } catch (_) {}
          }
          const resourceKey = form?.dataset.draftResourceKey
            || form?.dataset.wizardResourceKey
            || form?.querySelector('input[name="_wizard_resource_key"]')?.value;
          if (draftFileUrl && resourceKey) {
            const csrfToken = form.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(draftFileUrl, {
              method: 'DELETE',
              credentials: 'same-origin',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                Accept: 'application/json',
              },
              body: JSON.stringify({
                resource_key: resourceKey,
                file_key: removed._draftKey,
              }),
            }).catch(() => {});
          }
        }

        zone.dispatchEvent(new CustomEvent('file:removed', {
          bubbles: true,
          detail: { removedFile: removed, removedIndex: index, remainingFiles: currentFiles, input }
        }));
      });

      actionsWrap.appendChild(removeBtn);
      item.appendChild(actionsWrap);
      previewContainer.appendChild(item);
    });
  }

  function handleFiles(filesList) {
    const validFiles = [];
    for (let i = 0; i < filesList.length; i++) {
      const file = filesList[i];
      if (file.size > maxMb * 1024 * 1024) {
        showNotification(`الملف "${file.name}" يتجاوز الحد الأقصى المسموح به (${maxMb}MB)`, 'error');
        continue;
      }
      validFiles.push(file);
    }

    if (validFiles.length === 0) return;

    if (isMultiple) {
      validFiles.forEach((file) => {
        // Avoid duplicate files with exact same name and size
        const exists = currentFiles.some((f) => f.name === file.name && f.size === file.size);
        if (!exists) {
          currentFiles.push(file);
        }
      });
    } else {
      currentFiles = [validFiles[0]];
    }

    syncAndDispatch();
    renderPreviews();
  }

  // Click on zone triggers file input
  zone.addEventListener('click', () => {
    input.click();
  });

  // Keyboard accessibility
  zone.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      input.click();
    }
  });

  // Change on native input
  input.addEventListener('change', () => {
    if (isSyncing) return;
    if (input.files && input.files.length > 0) {
      handleFiles(Array.from(input.files));
    }
  });

  // Drag and Drop
  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    zone.classList.add('drag-over');
  });

  zone.addEventListener('dragleave', () => {
    zone.classList.remove('drag-over');
  });

  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('drag-over');

    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      handleFiles(Array.from(e.dataTransfer.files));
    }
  });

  function createExistingCard(fileData) {
    if (!fileData) return null;
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
    const name = fileData.name || 'الملف المرفق';
    const safeName = escapeHtml(name);
    const rawPath = fileData.path || fileData.url || '';
    const resolvedUrl = fileData.url || (rawPath.startsWith('http') || rawPath.startsWith('/') ? rawPath : `/storage/${rawPath}`);
    const downloadUrl = fileData.downloadUrl || fileData.download_url || resolvedUrl;
    const ext = (fileData.extension || name.split('.').pop() || '').toLowerCase();
    const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext) || Boolean(fileData.isImage);

    const baseNameProp = input.name.replace(/\[\]$/, '');
    const cleanPropName = baseNameProp.replace(/[\[\].]/g, '_');
    const existingInputName = fileData.inputName || ('existing_' + (isMultiple ? baseNameProp + '[]' : baseNameProp));
    const removeInputName = fileData.removeName || fileData.remove_name || (isMultiple ? `remove_${cleanPropName}[]` : `remove_${cleanPropName}`);
    const removeMode = isMultiple || String(removeInputName).endsWith('[]') ? 'multiple' : 'single';
    const removeValue = fileData.removeValue ?? fileData.remove_value ?? fileData.id ?? rawPath;

    const card = document.createElement('div');
    card.className = 'file-item';
    card.dataset.existingFileCard = '';
    card.dataset.filePath = rawPath;
    if (fileData.id !== undefined && fileData.id !== null && fileData.id !== '') {
      card.dataset.fileId = String(fileData.id);
    }

    let mediaHtml = '';
    if (isImg && resolvedUrl && resolvedUrl !== '#') {
      mediaHtml = `<img src="${escapeHtml(resolvedUrl)}" alt="${safeName}" class="file-item-preview" onerror="this.classList.add('d-none');this.nextElementSibling?.classList.remove('d-none')"><div class="file-item-icon file-item-icon-image d-none"><i class="ti ti-photo"></i></div>`;
    } else {
      let iconClass = 'ti-file';
      let modClass = '';
      if (ext === 'pdf') {
        iconClass = 'ti-file-type-pdf';
        modClass = 'file-item-icon-pdf';
      } else if (['doc', 'docx'].includes(ext)) {
        iconClass = 'ti-file-description';
        modClass = 'file-item-icon-word';
      } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
        iconClass = 'ti-file-spreadsheet';
        modClass = 'file-item-icon-excel';
      }
      mediaHtml = `<div class="file-item-icon ${modClass}"><i class="ti ${iconClass}"></i></div>`;
    }

    const viewBtn = resolvedUrl && resolvedUrl !== '#'
      ? `<a href="${escapeHtml(resolvedUrl)}" target="_blank" rel="noopener noreferrer" class="btn-view-file text-decoration-none" style="text-decoration: none;" title="معاينة الملف" aria-label="معاينة الملف"><i class="ti ti-eye"></i></a>`
      : '';
    const downloadBtn = downloadUrl && downloadUrl !== '#'
      ? `<a href="${escapeHtml(downloadUrl)}" download="${safeName}" class="btn-download-file text-decoration-none" style="text-decoration: none;" title="تنزيل الملف" aria-label="تنزيل الملف"><i class="ti ti-download"></i></a>`
      : '';

    card.innerHTML = `
      <input type="hidden" name="${escapeHtml(existingInputName)}" value="${escapeHtml(rawPath)}" data-existing-file-input>
      ${mediaHtml}
      <div class="file-item-info">
        <div class="file-item-name" title="${safeName}">${safeName}</div>
        <div class="file-item-size text-success small">ملف محفوظ مسبقاً</div>
      </div>
      <div class="file-item-actions">
        ${viewBtn}
        ${downloadBtn}
        <button type="button" class="btn-remove-file" data-remove-existing-file="${escapeHtml(removeInputName)}" data-remove-mode="${escapeHtml(removeMode)}" data-remove-value="${escapeHtml(removeValue)}" title="إزالة الملف" aria-label="إزالة الملف">
          <i class="ti ti-trash"></i>
        </button>
      </div>
    `;

    return card;
  }

  const controller = {
    handleFiles,
    setFiles: (filesList) => {
      currentFiles = [];
      handleFiles(Array.from(filesList || []));
    },
    addFiles: (filesList) => {
      handleFiles(Array.from(filesList || []));
    },
    getFiles: () => currentFiles,
    clear: () => {
      currentFiles = [];
      syncAndDispatch();
      renderPreviews();
    },
    setExistingFiles: (filesList) => {
      if (!previewContainer) return;
      previewContainer.querySelectorAll('[data-existing-file-card]').forEach((c) => c.remove());
      const list = Array.isArray(filesList) ? filesList : (filesList ? [filesList] : []);
      list.forEach((f) => {
        const card = createExistingCard(f);
        if (card) previewContainer.appendChild(card);
      });
      const hasAny = previewContainer.querySelectorAll('[data-existing-file-card]').length > 0;
      controlWrapper.dataset.hasExisting = hasAny ? 'true' : 'false';
      input.dataset.hasExisting = hasAny ? 'true' : 'false';
      if (hasAny && input.required) {
        input.required = false;
        input.removeAttribute('required');
      }
      renderPreviews();
    },
    setExistingFile: (fileData) => {
      controller.setExistingFiles(fileData ? [fileData] : []);
    },
    clearExistingFiles: () => {
      if (!previewContainer) return;
      previewContainer.querySelectorAll('[data-existing-file-card]').forEach((c) => c.remove());
      controlWrapper.dataset.hasExisting = 'false';
      input.dataset.hasExisting = 'false';
      if (input.dataset.wasRequired === 'true' && currentFiles.length === 0) {
        input.required = true;
        input.setAttribute('required', '');
      }
      renderPreviews();
    },
    renderPreviews,
    syncInput,
    syncAndDispatch,
  };

  zone._fileUploadController = controller;
  input._fileUploadController = controller;
  controlWrapper._fileUploadController = controller;

  const onSetFiles = (e) => {
    if (e.detail?.files) {
      if (e.detail.replace !== false) {
        currentFiles = [];
      }
      handleFiles(Array.from(e.detail.files));
    }
  };
  zone.addEventListener('file:set-files', onSetFiles);
  input.addEventListener('file:set-files', onSetFiles);

  return controller;
}

export function getFileUploadController(target) {
  if (!target) return null;
  if (target._fileUploadController) return target._fileUploadController;
  const zone = target.matches?.('[data-file-upload-zone]')
    ? target
    : (target.closest?.('[data-file-upload-wrapper]') || target.parentElement)?.querySelector?.('[data-file-upload-zone]');
  if (zone) {
    if (zone._fileUploadController) return zone._fileUploadController;
    return initFileUploadZone(zone);
  }
  return null;
}

export function setFilesOnUploadZone(target, files) {
  const controller = getFileUploadController(target);
  if (controller) {
    controller.setFiles(files);
    return true;
  }
  return false;
}

export function initAllFileUploadZones(root = document) {
  root.querySelectorAll('[data-file-upload-zone]').forEach(initFileUploadZone);
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initAllFileUploadZones());
  } else {
    initAllFileUploadZones();
  }
}

if (typeof window !== 'undefined') {
  window.SharedFileUpload = {
    initZone: initFileUploadZone,
    initAll: initAllFileUploadZones,
    getController: getFileUploadController,
    setFiles: setFilesOnUploadZone,
    setExistingFile: (target, fileData) => {
      const c = getFileUploadController(target);
      if (c) {
        c.setExistingFile(fileData);
        return true;
      }
      return false;
    },
    setExistingFiles: (target, filesList) => {
      const c = getFileUploadController(target);
      if (c) {
        c.setExistingFiles(filesList);
        return true;
      }
      return false;
    },
    clearExistingFiles: (target) => {
      const c = getFileUploadController(target);
      if (c) {
        c.clearExistingFiles();
        return true;
      }
      return false;
    },
  };
}
