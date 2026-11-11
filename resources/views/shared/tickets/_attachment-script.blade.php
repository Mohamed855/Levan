<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('ticket-reply-form');
        var messageInput = document.getElementById('replyMessage');
        var messageError = document.getElementById('replyMessage-error');
        var composerBox = document.querySelector('.ticket-reply-composer');
        var fileInput = document.getElementById('attachments');
        var triggerBtn = document.getElementById('btn-trigger-attachments');
        var statusBtn = document.getElementById('attachment-status-text');
        var countLabel = document.getElementById('attachment-count-label');
        var clearBtn = document.getElementById('btn-clear-attachments');
        var attachmentsList = document.getElementById('ticket-attachments-list');

        var pendingFiles = [];
        var nextFileId = 1;
        var isSyncingFiles = false;

        function formatFileSize(bytes) {
            if (!bytes || bytes <= 0) return '0 KB';
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(1) + ' MB';
            }
            return Math.max(0.1, bytes / 1024).toFixed(1) + ' KB';
        }

        function resolveFileIconClass(fileName) {
            var ext = (fileName.split('.').pop() || '').toLowerCase();
            if (ext === 'pdf') return 'ti ti-file-type-pdf text-danger';
            if (ext === 'doc' || ext === 'docx') return 'ti ti-file-description text-primary';
            if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'ti ti-file-spreadsheet text-success';
            if (ext === 'zip' || ext === 'rar' || ext === '7z') return 'ti ti-file-zip text-warning';
            return 'ti ti-file text-secondary';
        }

        function createThumbnailElement(entry) {
            var thumb = document.createElement('span');
            thumb.className = 'ticket-attachment-thumb';

            if (entry.objectUrl) {
                var img = document.createElement('img');
                img.src = entry.objectUrl;
                img.alt = entry.file.name;
                thumb.appendChild(img);
            } else {
                var icon = document.createElement('i');
                icon.className = resolveFileIconClass(entry.file.name);
                thumb.appendChild(icon);
            }

            return thumb;
        }

        function closeDropdown() {
            if (statusBtn && window.bootstrap && window.bootstrap.Dropdown) {
                var instance = window.bootstrap.Dropdown.getInstance(statusBtn);
                if (instance) {
                    instance.hide();
                }
            }
        }

        function syncFileInput() {
            if (!fileInput) return;

            isSyncingFiles = true;
            try {
                if (typeof window.DataTransfer === 'function') {
                    var dt = new window.DataTransfer();
                    pendingFiles.forEach(function(entry) {
                        dt.items.add(entry.file);
                    });
                    fileInput.files = dt.files;
                }
                if (pendingFiles.length === 0) {
                    fileInput.value = '';
                }
            } finally {
                isSyncingFiles = false;
            }
        }

        function clearManualMessageError() {
            if (composerBox) {
                composerBox.classList.remove('border-danger');
            }
            if (messageError && !form?.hasAttribute('data-form-validate')) {
                messageError.textContent = '';
                messageError.hidden = true;
                messageError.style.display = 'none';
            }
        }

        function syncSharedValidation() {
            if (!form) return;

            if (!form.hasAttribute('data-form-validate')) {
                clearManualMessageError();
                return;
            }

            if (window.SharedFormValidation && typeof window.SharedFormValidation.validateFields === 'function') {
                var hasMessageText = Boolean(messageInput && messageInput.value.trim() !== '');
                if (hasMessageText || pendingFiles.length > 0 || (messageError && !messageError.hidden)) {
                    window.SharedFormValidation.validateFields(form, ['message', 'attachments'], { force: true });
                }
            }
        }

        function removePendingFile(entryId) {
            var index = pendingFiles.findIndex(function(item) {
                return item.id === entryId;
            });
            if (index === -1) return;

            var removed = pendingFiles.splice(index, 1)[0];
            if (removed && removed.objectUrl) {
                URL.revokeObjectURL(removed.objectUrl);
            }

            syncFileInput();
            renderAttachments();
            if (pendingFiles.length === 0) {
                closeDropdown();
            }
            syncSharedValidation();
        }

        function renderAttachments() {
            if (!statusBtn || !countLabel || !attachmentsList) return;

            attachmentsList.replaceChildren();

            var count = pendingFiles.length;
            if (count === 0) {
                countLabel.textContent = '';
                statusBtn.classList.add('d-none');
                statusBtn.classList.remove('d-inline-flex');
                return;
            }

            countLabel.textContent = 'تم إرفاق ' + count + ' ملف';
            statusBtn.classList.remove('d-none');
            statusBtn.classList.add('d-inline-flex');

            pendingFiles.forEach(function(entry) {
                var li = document.createElement('li');
                li.className = 'ticket-attachment-item';
                li.dataset.attachmentId = String(entry.id);

                var thumb = createThumbnailElement(entry);

                var nameEl = document.createElement('span');
                nameEl.className = 'ticket-attachment-name';
                nameEl.textContent = entry.file.name;
                nameEl.title = entry.file.name;

                var sizeEl = document.createElement('span');
                sizeEl.className = 'ticket-attachment-size';
                sizeEl.textContent = formatFileSize(entry.file.size);

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'ticket-attachment-remove';
                removeBtn.title = 'حذف الملف';
                removeBtn.setAttribute('aria-label', 'حذف ' + entry.file.name);

                var trashIcon = document.createElement('i');
                trashIcon.className = 'ti ti-trash';
                removeBtn.appendChild(trashIcon);

                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    removePendingFile(entry.id);
                });

                li.appendChild(thumb);
                li.appendChild(nameEl);
                li.appendChild(sizeEl);
                li.appendChild(removeBtn);
                attachmentsList.appendChild(li);
            });
        }

        function appendFiles(fileList) {
            if (!fileList || fileList.length === 0) return;

            Array.from(fileList).forEach(function(file) {
                var alreadyQueued = pendingFiles.some(function(entry) {
                    return entry.file.name === file.name
                        && entry.file.size === file.size
                        && entry.file.lastModified === file.lastModified;
                });

                if (alreadyQueued) return;

                var isImage = Boolean(file.type && file.type.startsWith('image/'));
                pendingFiles.push({
                    id: nextFileId++,
                    file: file,
                    objectUrl: isImage ? URL.createObjectURL(file) : null,
                });
            });
        }

        if (triggerBtn && fileInput) {
            triggerBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (isSyncingFiles) return;

                appendFiles(this.files);
                syncFileInput();
                renderAttachments();
                syncSharedValidation();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                pendingFiles.forEach(function(entry) {
                    if (entry.objectUrl) {
                        URL.revokeObjectURL(entry.objectUrl);
                    }
                });
                pendingFiles = [];
                closeDropdown();
                syncFileInput();
                renderAttachments();
                syncSharedValidation();
            });
        }

        if (messageInput) {
            messageInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    clearManualMessageError();
                }
            });
        }

        if (form && !form.hasAttribute('data-form-validate')) {
            form.addEventListener('submit', function(e) {
                var text = messageInput ? messageInput.value.trim() : '';
                if (text === '' && pendingFiles.length === 0) {
                    e.preventDefault();
                    if (composerBox) {
                        composerBox.classList.add('border-danger');
                    }
                    if (messageError) {
                        messageError.textContent = 'يجب كتابة رد أو إرفاق ملف واحد على الأقل';
                        messageError.hidden = false;
                        messageError.style.display = 'block';
                    }
                    if (messageInput) {
                        messageInput.focus();
                    }
                }
            });
        }

        window.addEventListener('beforeunload', function() {
            pendingFiles.forEach(function(entry) {
                if (entry.objectUrl) {
                    URL.revokeObjectURL(entry.objectUrl);
                }
            });
        });
    });
</script>
