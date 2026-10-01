'use strict';

let settlementIndex = 0;
let currentCustomerId;

// تصدير الدوال للنافذة العامة
window.showCreateSettlementModal = showCreateSettlementModal;
window.showEditSettlementModal = showEditSettlementModal;
window.deleteSettlement = deleteSettlement;
window.addSettlementField = addSettlementField;
window.removeSettlementField = removeSettlementField;

$(document).ready(function () {
    currentCustomerId = window.customerId;

    $(document).on('click', '.js-create-settlement-btn', function () {
        showCreateSettlementModal();
    });

    $(document).on('click', '.js-add-settlement-field-btn', function () {
        addSettlementField();
    });

    $(document).on('click', '.js-edit-settlement-btn', function () {
        showEditSettlementModal($(this).data('id'));
    });

    $(document).on('click', '.js-delete-settlement-btn', function () {
        deleteSettlement($(this).data('id'));
    });

    $(document).on('click', '.js-remove-settlement-field-btn', function () {
        removeSettlementField($(this).data('index'));
    });
});

// فتح مودال الإضافة
async function showCreateSettlementModal() {
    if (!currentCustomerId) {
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    try {
        const existingModal = document.getElementById('createSettlementModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('createSettlementModal', 'إضافة تسوية جديدة');

        const response = await $.ajax({
            url: getRouteUrl('customers.settlements.create', { customer: currentCustomerId }),
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#createSettlementModal .modal-content').html(response.html);

            // تطبيق الخصائص لمنع الإغلاق
            $('#createSettlementModal').attr('data-bs-backdrop', 'static');
            $('#createSettlementModal').attr('data-bs-keyboard', 'false');

            // إضافة حقل افتراضي
            settlementIndex = 0;
            addSettlementField();

            // تهيئة select2 بعد إضافة الحقول
            setTimeout(() => {
                initializeModalComponents('#createSettlementModal');
            }, 100);

            bindCreateModalEvents();
        } else {
            toastr.error('حدث خطأ في تحميل النموذج');
            hideModal('createSettlementModal');
        }
    } catch (error) {
        console.error('خطأ في تحميل المودال:', error);
        toastr.error('حدث خطأ في تحميل النموذج');
        hideModal('createSettlementModal');
    }
}

// فتح مودال التعديل
async function showEditSettlementModal(settlementId) {
    if (!settlementId) {
        toastr.error('معرف التسوية غير صحيح');
        return;
    }

    try {
        const existingModal = document.getElementById('editSettlementModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('editSettlementModal', 'تعديل التسوية');

        const response = await $.ajax({
            url: getRouteUrl('customers.settlements.edit', {
                customer: currentCustomerId,
                customerSettlement: settlementId
            }),
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#editSettlementModal .modal-content').html(response.html);

            // تطبيق الخصائص لمنع الإغلاق
            $('#editSettlementModal').attr('data-bs-backdrop', 'static');
            $('#editSettlementModal').attr('data-bs-keyboard', 'false');

            // تهيئة select2 بعد تحميل النموذج
            setTimeout(() => {
                initializeModalComponents('#editSettlementModal');
            }, 100);

            bindEditModalEvents();
        } else {
            toastr.error('حدث خطأ في تحميل البيانات');
            hideModal('editSettlementModal');
        }
    } catch (error) {
        console.error('خطأ في تحميل بيانات التعديل:', error);
        toastr.error('حدث خطأ في تحميل البيانات');
        hideModal('editSettlementModal');
    }
}

// تهيئة مكونات المودال
function initializeModalComponents(modalSelector) {
    if (!$(modalSelector).length) {
        return;
    }

    // تهيئة select2
    $(modalSelector + ' .select2-modal, select[name*="settlement_type"], select[name="settlement_type"]').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }

        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر نوع التسوية',
            allowClear: false,
            width: '100%',
            language: {
                noResults: function () {
                    return "لا توجد نتائج";
                },
                inputTooShort: function () {
                    return "الرجاء إدخال المزيد من الأحرف";
                }
            },
            dir: 'rtl',
            dropdownParent: $(modalSelector)
        }).on('select2:select select2:clear', function () {
            $(this).removeClass('is-invalid');
            $(this).closest('.col-md-6, .col-12').find('.invalid-feedback').remove();
        });
    });

    setupLiveValidation(modalSelector);
}

// إعداد التحقق المباشر للحقول
function setupLiveValidation(modalSelector) {
    $(modalSelector + ' input, ' + modalSelector + ' textarea').on('input blur', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });

    $(modalSelector + ' select').on('change', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });

    $(modalSelector + ' .select2-modal').on('select2:select select2:unselect', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });
}

// مسح رسائل التحقق من الحقل
function clearFieldValidation(field) {
    field.removeClass('is-invalid');
    field.siblings('.invalid-feedback').remove();
}

// إضافة حقل تسوية جديد
function addSettlementField() {
    settlementIndex++;

    const settlementTypesOptions = generateSettlementTypeOptions();

    const fieldHtml = `
        <div class="border rounded p-3 mb-3" data-index="${settlementIndex}">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">تاريخ التسوية <span class="text-danger">*</span></label>
                    <input type="date"
                           name="settlements[${settlementIndex}][settlement_date]"
                           class="form-control"
                           value="${getTodayDate()}"
                           required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">مبلغ التسوية <span class="text-danger">*</span></label>
                    <input type="number"
                           name="settlements[${settlementIndex}][settlement_amount]"
                           class="form-control"
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم التسوية <span class="text-danger">*</span></label>
                    <input type="text"
                           name="settlements[${settlementIndex}][settlement_number]"
                           class="form-control"
                           maxlength="100"
                           placeholder="أدخل رقم التسوية"
                           required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نوع التسوية <span class="text-danger">*</span></label>
                    <select name="settlements[${settlementIndex}][settlement_type]" class="form-control select2-modal" data-placeholder="اختر نوع التسوية" required>
                        <option value="">اختر نوع التسوية</option>
                        ${settlementTypesOptions}
                    </select>
                </div>
                <div class="col-md-10">
                    <label class="form-label">المرفق</label>
                    <input type="file"
                           name="settlements[${settlementIndex}][file]"
                           class="form-control"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                    <div class="form-text">الأنواع المدعومة: PDF, DOC, XLS, صور. الحد الأقصى: 10 ميجابايت</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm w-100 d-block js-remove-settlement-field-btn"
                            data-index="${settlementIndex}"
                            title="حذف هذه التسوية">
                        حـذف
                    </button>
                </div>
            </div>
        </div>
    `;

    const container = document.getElementById('settlements-container');
    if (container) {
        container.insertAdjacentHTML('beforeend', fieldHtml);

        // تهيئة select2 للحقل الجديد
        setTimeout(() => {
            const newSelect = container.querySelector(`[data-index="${settlementIndex}"] select`);
            if (newSelect) {
                // تحديد المودال الحالي
                const modalElement = $(newSelect).closest('.modal');

                $(newSelect).select2({
                    placeholder: $(newSelect).data('placeholder') || 'اختر نوع التسوية',
                    allowClear: false,
                    width: '100%',
                    language: {
                        noResults: function () {
                            return "لا توجد نتائج";
                        },
                        inputTooShort: function () {
                            return "الرجاء إدخال المزيد من الأحرف";
                        }
                    },
                    dir: 'rtl',
                    dropdownParent: modalElement
                }).on('select2:select select2:clear', function () {
                    $(this).removeClass('is-invalid');
                    $(this).closest('.col-md-6, .col-12').find('.invalid-feedback').remove();
                });
            }
        }, 100);
    }
}

// حذف حقل تسوية
function removeSettlementField(index) {
    const field = document.querySelector(`[data-index="${index}"]`);
    if (field) {
        field.remove();

        // إضافة حقل جديد إذا لم يتبق أي حقل
        const remainingFields = document.querySelectorAll('[data-index]');
        if (remainingFields.length === 0) {
            addSettlementField();
        }
    }
}

// توليد خيارات أنواع التسويات
function generateSettlementTypeOptions() {
    let options = '';
    if (window.settlementTypes) {
        for (const [key, value] of Object.entries(window.settlementTypes)) {
            options += `<option value="${key}">${value}</option>`;
        }
    }
    return options;
}

// الحصول على تاريخ اليوم بصيغة YYYY-MM-DD
function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// حذف تسوية محفوظة
function deleteSettlement(settlementId) {
    if (!settlementId) {
        toastr.error('معرف التسوية غير صحيح');
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
            $.ajax({
                url: getRouteUrl('customers.settlements.destroy', {
                    customer: currentCustomerId,
                    customerSettlement: settlementId
                }),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم حذف التسوية بنجاح');

                        // إزالة التسوية من القائمة
                        const settlementElement = document.getElementById(`settlement-${settlementId}`);
                        if (settlementElement) {
                            settlementElement.style.transition = 'opacity 0.3s';
                            settlementElement.style.opacity = '0';
                            setTimeout(() => {
                                settlementElement.remove();
                                checkAndShowNoSettlementsMessage();
                            }, 300);
                        }
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    console.error('خطأ في حذف التسوية:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف التسوية';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

function getRouteUrl(routeName, params = {}) {
    if (!window.routes || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }
    let url = window.routes[routeName];

    if (params.customer) url = url.replace(/CUSTOMER_ID/g, params.customer);
    if (params.customerSettlement) url = url.replace(/SETTLEMENT_ID/g, params.customerSettlement);

    if (!url.startsWith('http') && !url.startsWith('/')) url = '/' + url;
    return url;
}


// مودال التحميل
function showLoadingModal(modalId, title) {
    const loadingHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <div class="mt-3">جاري التحميل...</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#customer-settlement-modals-container').html(loadingHtml);
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

// ربط أحداث مودال الإضافة
function bindCreateModalEvents() {
    $('#saveSettlements').off('click.settlementModal');

    $('#saveSettlements').on('click.settlementModal', function () {
        const form = $('#createSettlementForm')[0];
        const button = $(this);
        const spinner = button.find('.spinner-border');

        if (!validateForm($(form))) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        // استخدام FormData لدعم رفع الملفات
        const formData = new FormData(form);

        $.ajax({
            url: getRouteUrl('customers.settlements.store', { customer: currentCustomerId }),
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
                    hideModal('createSettlementModal');

                    // تحديث قائمة التسويات بدون إعادة تحميل الصفحة
                    refreshSettlementsList();
                } else {
                    toastr.error(response.message || 'حدث خطأ في الحفظ');
                }
            },
            error: function (xhr) {
                console.error('خطأ في الحفظ:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors($(form), xhr.responseJSON.errors);
                } else {
                    toastr.error('حدث خطأ أثناء حفظ البيانات');
                }
            },
            complete: function () {
                button.prop('disabled', false);
                spinner.hide();
            }
        });
    });
}

// ربط أحداث مودال التعديل
function bindEditModalEvents() {
    $('#updateSettlement').off('click.settlementModal');

    $('#updateSettlement').on('click.settlementModal', function () {
        const form = $('#editSettlementForm')[0];
        const button = $(this);
        const spinner = button.find('.spinner-border');
        const settlementId = $(form).data('settlement-id');

        if (!validateForm($(form))) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        // استخدام FormData لدعم رفع الملفات
        const formData = new FormData(form);

        $.ajax({
            url: getRouteUrl('customers.settlements.destroy', {
                customer: currentCustomerId,
                customerSettlement: settlementId
            }),
            type: 'POST', // Laravel يستخدم POST مع _method
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    hideModal('editSettlementModal');

                    // تحديث التسوية في القائمة بدون إعادة تحميل الصفحة
                    updateSettlementInList(settlementId, response.data);
                } else {
                    toastr.error(response.message || 'حدث خطأ في التحديث');
                }
            },
            error: function (xhr) {
                console.error('خطأ في التحديث:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors($(form), xhr.responseJSON.errors);
                } else {
                    toastr.error('حدث خطأ أثناء تحديث البيانات');
                }
            },
            complete: function () {
                button.prop('disabled', false);
                spinner.hide();
            }
        });
    });
}

// تحديث قائمة التسويات بعد الإضافة
function refreshSettlementsList() {
    $.ajax({
        url: getRouteUrl('customers.settlements.list', { customer: currentCustomerId }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#saved-settlements').html(response.html);
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

// تحديث تسوية محددة في القائمة بعد التعديل
function updateSettlementInList(settlementId, updatedSettlement) {
    const settlementElement = document.getElementById(`settlement-${settlementId}`);
    if (settlementElement) {
        // تحديث البيانات في العنصر الحالي
        settlementElement.querySelector('.col-md-2:nth-child(1) .fw-medium').textContent = updatedSettlement.settlement_date_formatted;
        settlementElement.querySelector('.col-md-2:nth-child(2) .fw-medium').textContent = updatedSettlement.settlement_amount_formatted;
        settlementElement.querySelector('.col-md-2:nth-child(3) .fw-medium').textContent = updatedSettlement.settlement_number;
        settlementElement.querySelector('.col-md-2:nth-child(4) .text-muted').textContent = updatedSettlement.settlement_type_text;

        // البحث عن عمود الملف والتحقق من وجوده
        let fileColumn = null;
        for (let i = 4; i <= 6; i++) {
            const column = settlementElement.querySelector(`.col-md-2:nth-child(${i})`);
            if (column && (column.textContent.includes('المرفق') || column.innerHTML.includes('ti-paperclip'))) {
                fileColumn = column;
                break;
            }
        }

        if (fileColumn) {
            if (updatedSettlement.has_file && updatedSettlement.file_url) {
                fileColumn.innerHTML = `
                    <label class="form-label">المرفق</label>
                    <div class="text-muted small">
                        <i class="ti ti-paperclip me-1"></i>
                        <a href="${updatedSettlement.file_url}" target="_blank" class="text-primary">
                            ملف مرفق
                        </a>
                    </div>
                `;
            } else {
                // الاحتفاظ بعمود الملف فارغًا إذا لم يكن هناك ملف
                fileColumn.innerHTML = `
                    <label class="form-label">المرفق</label>
                    <div class="text-muted small">
                        لا يوجد مرفق
                    </div>
                `;
            }
        }

        // إضافة تأثير بصري للتحديث
        settlementElement.style.transition = 'background-color 0.5s';
        settlementElement.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            settlementElement.style.backgroundColor = '';
        }, 1000);
    } else {
        // إذا لم يتم العثور على العنصر، قم بتحديث القائمة بأكملها
        refreshSettlementsList();
    }
}

// التحقق من صحة النموذج
function validateForm(form) {
    let isValid = true;
    clearFormValidation(form);

    form.find('[required]').each(function () {
        if (!$(this).val() || $(this).val().trim() === '') {
            $(this).addClass('is-invalid');
            isValid = false;
        }
    });

    return isValid;
}

function clearFormValidation(form) {
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
}

function displayValidationErrors(form, errors) {
    $.each(errors, function (field, messages) {
        // للحقول العادية
        let fieldElement = form.find('[name="' + field + '"]');

        // للحقول المتعددة مثل settlements.0.settlement_date
        if (fieldElement.length === 0) {
            const fieldParts = field.split('.');
            if (fieldParts.length >= 3) {
                const fieldName = `${fieldParts[0]}[${fieldParts[1]}][${fieldParts[2]}]`;
                fieldElement = form.find('[name="' + fieldName + '"]');
            }
        }

        if (fieldElement.length && messages.length > 0) {
            fieldElement.addClass('is-invalid');

            // إزالة رسالة خطأ سابقة
            fieldElement.siblings('.invalid-feedback').remove();

            // إضافة رسالة الخطأ
            fieldElement.after(`<div class="invalid-feedback">${messages[0]}</div>`);
        }
    });
}

// التحقق من وجود تسويات وإظهار رسالة مناسبة
function checkAndShowNoSettlementsMessage() {
    const savedSettlements = document.getElementById('saved-settlements');
    const settlementElements = savedSettlements.querySelectorAll('.border');

    if (settlementElements.length === 0) {
        const noSettlementsHtml = `
            <div class="text-center py-5" id="no-settlements-message">
                <i class="ti ti-file-check fa-3x text-muted mb-3" style="font-size: 3rem;"></i>
                <p class="text-muted mb-0">لا توجد تسويات</p>
                <small class="text-muted">ابدأ بإضافة تسوية لهذا العميل</small>
            </div>
        `;
        savedSettlements.innerHTML = noSettlementsHtml;
    }
}