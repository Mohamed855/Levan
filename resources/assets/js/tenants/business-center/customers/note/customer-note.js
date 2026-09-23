'use strict';

let noteIndex = 0;
let currentCustomerId;

// تصدير الدوال للنافذة العامة
window.addNoteField = addNoteField;
window.removeNoteField = removeNoteField;
window.saveNotes = saveNotes;
window.deleteNote = deleteNote;

$(document).ready(function () {
    currentCustomerId = window.customerId;

    // إضافة حقل افتراضي عند تحميل الصفحة
    if (document.getElementById('notes-container')) {
        addNoteField();
    }

    $(document).on('click', '.js-add-note-field-btn', function () {
        addNoteField();
    });

    $(document).on('click', '.js-save-notes-btn', function () {
        saveNotes();
    });

    $(document).on('click', '.js-remove-note-field-btn', function () {
        removeNoteField($(this).data('index'));
    });

    $(document).on('click', '.js-delete-note-btn', function () {
        deleteNote($(this).data('id'));
    });
});

function getRouteUrl(routeName, params = {}) {
    if (!window.routes || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }
    let url = window.routes[routeName];

    if (params.customer) url = url.replace(/CUSTOMER_ID/g, params.customer);
    if (params.customerNote) url = url.replace(/NOTE_ID/g, params.customerNote);

    if (!url.startsWith('http') && !url.startsWith('/')) url = '/' + url;
    return url;
}


// إضافة حقل ملاحظة جديد
function addNoteField() {
    noteIndex++;

    const fieldHtml = `
        <div class="note-field border rounded p-3 mb-3" data-index="${noteIndex}">
            <div class="row">
                <div class="col-11">
                    <label class="form-label">الملاحظة <span class="text-danger">*</span></label>
                    <textarea name="notes[]"
                              class="form-control note-text"
                              rows="4"
                              required
                              placeholder="اكتب ملاحظتك هنا..."></textarea>
                    <div class="form-text">بين 5-2000 حرف</div>
                </div>
                <div class="col-1 text-end">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger js-remove-note-field-btn"
                                data-index="${noteIndex}"
                                title="حذف هذه الملاحظة">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const container = document.getElementById('notes-container');
    if (container) {
        container.insertAdjacentHTML('beforeend', fieldHtml);
        updateSaveButtonVisibility();
    }
}

// حذف حقل ملاحظة
function removeNoteField(index) {
    const field = document.querySelector(`.note-field[data-index="${index}"]`);
    if (field) {
        field.remove();
        updateSaveButtonVisibility();

        // إضافة حقل جديد إذا لم يتبق أي حقل
        const remainingFields = document.querySelectorAll('.note-field[data-index]');
        if (remainingFields.length === 0) {
            addNoteField();
        }
    }
}

// تحديث ظهور زر الحفظ
function updateSaveButtonVisibility() {
    const hasFields = document.querySelectorAll('.note-field[data-index]').length > 0;
    const saveSection = document.getElementById('save-notes-section');
    if (saveSection) {
        saveSection.style.display = hasFields ? 'block' : 'none';
    }
}

// حفظ الملاحظات
function saveNotes() {
    const form = document.getElementById('customerNotesForm');
    const saveButton = document.querySelector('.js-save-notes-btn');
    const spinner = document.getElementById('save-notes-spinner');

    // جمع البيانات يدوياً
    const noteFields = document.querySelectorAll('.note-field[data-index] .note-text');
    let noteCount = 0;
    let hasNotes = false;

    noteFields.forEach(function (textarea, index) {
        const noteText = textarea.value.trim();
        if (noteText.length >= 5) {
            noteCount++;
            hasNotes = true;
        }
    });

    if (!hasNotes) {
        toastr.error('يجب إضافة ملاحظة واحدة على الأقل (5 أحرف كحد أدنى)');
        return;
    }

    // تعطيل الزر وإظهار اللودر
    if (saveButton) {
        saveButton.disabled = true;
    }
    if (spinner) {
        spinner.style.display = 'inline-block';
    }

    // استخدام FormData لدعم إرسال البيانات
    const formData = new FormData(form);

    $.ajax({
        url: getRouteUrl('customers.notes.store', { customer: currentCustomerId }),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);

                // مسح النموذج
                clearForm();

                // تحديث قائمة الملاحظات بدون إعادة تحميل الصفحة
                refreshNotesList();
            } else {
                toastr.error(response.message || 'حدث خطأ في الحفظ');
            }
        },
        error: function (xhr) {
            console.error('خطأ في حفظ الملاحظات:', xhr);
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                // عرض رسائل الخطأ
                let errorMessage = '';
                for (const [field, messages] of Object.entries(xhr.responseJSON.errors)) {
                    errorMessage += messages[0] + '\n';
                }
                toastr.error(errorMessage);
            } else {
                toastr.error('حدث خطأ أثناء حفظ الملاحظات');
            }
        },
        complete: function () {
            // إعادة تفعيل الزر وإخفاء اللودر
            if (saveButton) {
                saveButton.disabled = false;
            }
            if (spinner) {
                spinner.style.display = 'none';
            }
        }
    });
}

// مسح النموذج
function clearForm() {
    const container = document.getElementById('notes-container');
    if (container) {
        container.innerHTML = '';
        noteIndex = 0;
        addNoteField();
    }
}

// تحديث قائمة الملاحظات بعد الإضافة
function refreshNotesList() {
    $.ajax({
        url: getRouteUrl('customers.notes.list', { customer: currentCustomerId }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#saved-notes').html(response.html);
            } else {
                toastr.error('حدث خطأ في تحديث القائمة');
                // في حالة الخطأ، يمكن إعادة تحميل الصفحة كحل احتياطي
                location.reload();
            }
        },
        error: function () {
            toastr.error('حدث خطأ في تحديث القائمة');
            // في حالة الخطأ، يمكن إعادة تحميل الصفحة كحل احتياطي
            location.reload();
        }
    });
}

// حذف ملاحظة محفوظة
function deleteNote(noteId) {
    if (!noteId) {
        toastr.error('معرف الملاحظة غير صحيح');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text: "لا يمكن التراجع عن هذا الإجراء!",
        icon: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
    }).then((result) => {
        if (result.isConfirmed) {
            // تنفيذ الحذف عبر AJAX
            $.ajax({
                url: getRouteUrl('customers.notes.destroy', { customer: currentCustomerId, customerNote: noteId }),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        // إظهار رسالة النجاح
                        toastr.success(response.message || 'تم حذف الملاحظة بنجاح');

                        // إزالة الملاحظة من القائمة
                        const noteElement = document.getElementById(`note-${noteId}`);
                        if (noteElement) {
                            noteElement.style.transition = 'opacity 0.3s';
                            noteElement.style.opacity = '0';
                            setTimeout(() => {
                                noteElement.remove();
                                checkAndShowNoNotesMessage();
                            }, 300);
                        }
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    console.error('خطأ في حذف الملاحظة:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف الملاحظة';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

// التحقق من وجود ملاحظات وإظهار رسالة مناسبة
function checkAndShowNoNotesMessage() {
    const savedNotes = document.getElementById('saved-notes');
    const noteElements = savedNotes.querySelectorAll('.note-field');

    if (noteElements.length === 0) {
        const noNotesHtml = `
            <div class="text-center py-5" id="no-notes-message">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-secondary">
                        <i class="ti ti-notes" style="font-size: 2rem;"></i>
                    </span>
                </div>
                <h6 class="text-muted mb-1">لا توجد ملاحظات</h6>
                <p class="text-muted small mb-0">ابدأ بإضافة ملاحظات لهذا العميل</p>
            </div>
        `;
        savedNotes.innerHTML = noNotesHtml;
    }
}