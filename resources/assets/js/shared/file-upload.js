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

  const isMultiple = input.hasAttribute('multiple');
  const maxMb = Number(zone.dataset.maxSizeMb || input.dataset.maxSizeMb || 10);
  let currentFiles = [];

  // Handle clicking on existing file remove buttons
  if (previewContainer) {
    previewContainer.querySelectorAll('[data-remove-existing-file]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const existingCard = btn.closest('[data-existing-file-card]');
        const removeInputName = btn.dataset.removeExistingFile;
        if (removeInputName) {
          let hiddenInput = controlWrapper.querySelector(`input[name="${removeInputName}"]`);
          if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = removeInputName;
            controlWrapper.appendChild(hiddenInput);
          }
          hiddenInput.value = '1';
        }
        if (existingCard) existingCard.remove();
        zone.dispatchEvent(new CustomEvent('file:removed-existing', { bubbles: true, detail: { input } }));
      });
    });
  }

  let isSyncing = false;

  function syncAndDispatch() {
    if (!window.DataTransfer) return;
    const transfer = new DataTransfer();
    currentFiles.forEach((f) => transfer.items.add(f));
    isSyncing = true;
    input.files = transfer.files;

    if (currentFiles.length > 0) {
      zone.classList.add('has-files');
    } else {
      zone.classList.remove('has-files');
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
    existingCards.forEach((c) => previewContainer.appendChild(c));

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

      const viewUrl = file._draftUrl || (window.URL && typeof window.URL.createObjectURL === 'function' && file.size > 0 ? window.URL.createObjectURL(file) : null);
      if (viewUrl) {
        const viewLink = document.createElement('a');
        viewLink.href = viewUrl;
        viewLink.target = '_blank';
        viewLink.className = 'btn-view-file me-1';
        viewLink.title = 'معاينة الملف';
        viewLink.setAttribute('aria-label', `معاينة ${file.name}`);
        viewLink.innerHTML = '<i class="ti ti-eye"></i>';
        item.appendChild(viewLink);
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
          const draftFileUrl = form?.dataset.draftFileUrl;
          const resourceKey = form?.dataset.draftResourceKey || form?.dataset.wizardResourceKey;
          if (draftFileUrl && resourceKey) {
            const csrfToken = form.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(draftFileUrl, {
              method: 'DELETE',
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

      item.appendChild(removeBtn);
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
    renderPreviews,
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
  };
}
