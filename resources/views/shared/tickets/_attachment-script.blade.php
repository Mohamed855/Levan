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

        function deleteDraftFileOnServer(file) {
            if (!form || !file || !file._draftKey) return;
            var baseDraftUrl = document.body && document.body.dataset ? document.body.dataset.wizardDraftsUrl : '';
            var resourceKey = form.dataset.draftResourceKey || (form.querySelector('input[name="_wizard_resource_key"]') && form.querySelector('input[name="_wizard_resource_key"]').value) || '';
            if (!baseDraftUrl || !resourceKey) return;
            var draftFileUrl = baseDraftUrl.replace(/\/$/, '') + '/file';
            var csrfToken = (form.querySelector('input[name="_token"]') && form.querySelector('input[name="_token"]').value)
                || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '';
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
                    file_key: file._draftKey,
                }),
            }).catch(function() {});
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
            if (removed && removed.file) {
                deleteDraftFileOnServer(removed.file);
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
                    if (entry.file) {
                        deleteDraftFileOnServer(entry.file);
                    }
                });
                pendingFiles = [];
                closeDropdown();
                syncFileInput();
                renderAttachments();
                syncSharedValidation();
            });
        }

        // Recover failed-validation reply attachments when form-validation.js is not active (e.g. Admin ticket reply)
        if (form && fileInput && !form.hasAttribute('data-form-validate')) {
            var bodyDataset = document.body && document.body.dataset ? document.body.dataset : {};
            var resourceKey = form.dataset.draftResourceKey || (form.querySelector('input[name="_wizard_resource_key"]') && form.querySelector('input[name="_wizard_resource_key"]').value) || '';
            var recoveryId = bodyDataset.failedDraftResource === resourceKey ? bodyDataset.failedDraftId : '';
            var draftsUrl = bodyDataset.wizardDraftsUrl || '';
            if (recoveryId && draftsUrl) {
                fetch(draftsUrl + '?resource_key=' + encodeURIComponent(resourceKey), {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                }).then(function(res) {
                    return res.ok ? res.json() : null;
                }).then(async function(draft) {
                    if (!draft || draft.draft_id !== recoveryId || !draft.files || !draft.files.attachments) return;
                    var hiddenDraftId = form.querySelector('input[name="_wizard_draft_id"]');
                    if (!hiddenDraftId) {
                        hiddenDraftId = document.createElement('input');
                        hiddenDraftId.type = 'hidden';
                        hiddenDraftId.name = '_wizard_draft_id';
                        form.appendChild(hiddenDraftId);
                    }
                    hiddenDraftId.value = draft.draft_id;
                    var items = Array.isArray(draft.files.attachments) ? draft.files.attachments : Object.values(draft.files.attachments);
                    var restored = [];
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        if (!item || !item.url || !item.name) continue;
                        try {
                            var fetchUrl = item.url;
                            var parsed = new URL(fetchUrl, window.location.origin);
                            if (parsed.origin !== window.location.origin && (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1')) {
                                fetchUrl = parsed.pathname + parsed.search;
                            }
                            var fileRes = await fetch(fetchUrl, { credentials: 'same-origin' });
                            if (!fileRes.ok) continue;
                            var blob = await fileRes.blob();
                            var f = new File([blob], item.name, { type: item.mime || blob.type || 'application/octet-stream', lastModified: Date.now() });
                            f._draftKey = item.key;
                            f._draftUrl = item.url;
                            restored.push(f);
                        } catch (_) {}
                    }
                    if (restored.length > 0) {
                        appendFiles(restored);
                        syncFileInput();
                        renderAttachments();
                    }
                }).catch(function() {});
            }
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
