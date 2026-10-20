/**
 * client-image-upload.js
 * Shared script for client create/edit forms
 * Handles: image preview, drag-and-drop, remove button
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Select2 init ---
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({ dir: 'rtl', language: 'ar' });
    }

    // --- Image Upload ---
    const dropZone   = document.getElementById('drop-zone');
    const logoInput  = document.getElementById('logo');
    const previewWrap = document.getElementById('preview-wrap');
    const previewImg = document.getElementById('preview-img');
    const fileName   = document.getElementById('file-name');
    const fileSize   = document.getElementById('file-size');
    const removeBtn  = document.getElementById('remove-logo');
    const MAX_MB     = 2;

    if (!dropZone || !logoInput) return;

    /** Show the preview area */
    function showPreview(file) {
        if (!file || !file.type.startsWith('image/')) {
            showError('يُرجى اختيار ملف صورة صحيح (jpg, png, gif).');
            return;
        }

        if (file.size > MAX_MB * 1024 * 1024) {
            showError(`حجم الصورة يتجاوز ${MAX_MB}MB.`);
            return;
        }

        clearError();

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            previewWrap.style.display = 'flex';
            dropZone.classList.add('has-file');
        };
        reader.readAsDataURL(file);
    }

    /** Reset everything back to empty state */
    function resetUpload() {
        logoInput.value = '';
        previewImg.src  = '';
        previewWrap.style.display = 'none';
        dropZone.classList.remove('has-file', 'drag-over');
        clearError();
    }

    function showError(msg) {
        let err = document.getElementById('logo-error');
        if (!err) {
            err = document.createElement('div');
            err.id = 'logo-error';
            err.className = 'invalid-feedback d-block';
            dropZone.insertAdjacentElement('afterend', err);
        }
        err.textContent = msg;
    }

    function clearError() {
        const err = document.getElementById('logo-error');
        if (err) err.remove();
    }

    // Click on drop zone → trigger file input
    dropZone.addEventListener('click', () => logoInput.click());

    // File chosen via dialog
    logoInput.addEventListener('change', () => {
        if (logoInput.files && logoInput.files[0]) {
            showPreview(logoInput.files[0]);
        }
    });

    // Remove button
    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            resetUpload();
        });
    }

    // Drag-and-drop events
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');

        const file = e.dataTransfer.files[0];
        if (file) {
            // Inject the dropped file into the input for form submission
            const dt = new DataTransfer();
            dt.items.add(file);
            logoInput.files = dt.files;
            showPreview(file);
        }
    });

});