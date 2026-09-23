'use strict';

let planIndex = 0;
let currentCustomerId;

// تصدير الدوال للنافذة العامة
window.showCreatePaymentPlanModal = showCreatePaymentPlanModal;
window.showEditPaymentPlanModal = showEditPaymentPlanModal;
window.deletePaymentPlan = deletePaymentPlan;
window.addPaymentPlanField = addPaymentPlanField;
window.removePaymentPlanField = removePaymentPlanField;

$(document).ready(function () {
    currentCustomerId = window.customerId;

    $(document).on('click', '.js-create-payment-plan-btn', function () {
        showCreatePaymentPlanModal();
    });

    $(document).on('click', '.js-add-payment-plan-field-btn', function () {
        addPaymentPlanField();
    });

    $(document).on('click', '.js-edit-payment-plan-btn', function () {
        showEditPaymentPlanModal($(this).data('id'));
    });

    $(document).on('click', '.js-delete-payment-plan-btn', function () {
        deletePaymentPlan($(this).data('id'));
    });

    $(document).on('click', '.js-remove-payment-plan-field-btn', function () {
        removePaymentPlanField($(this).data('index'));
    });
});

// فتح مودال الإضافة
async function showCreatePaymentPlanModal() {
    if (!currentCustomerId) {
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    try {
        const existingModal = document.getElementById('createPaymentPlanModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('createPaymentPlanModal', 'إضافة خطة سداد جديدة');

        const response = await $.ajax({
            url: getRouteUrl('customers.payment-plans.create', { customer: currentCustomerId }),
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#createPaymentPlanModal .modal-content').html(response.html);

            // إضافة حقل افتراضي
            planIndex = 0;
            addPaymentPlanField();

            bindCreateModalEvents();
        } else {
            toastr.error('حدث خطأ في تحميل النموذج');
            hideModal('createPaymentPlanModal');
        }
    } catch (error) {
        console.error('خطأ في تحميل المودال:', error);
        toastr.error('حدث خطأ في تحميل النموذج');
        hideModal('createPaymentPlanModal');
    }
}

// فتح مودال التعديل
async function showEditPaymentPlanModal(planId) {
    if (!planId) {
        toastr.error('معرف خطة السداد غير صحيح');
        return;
    }

    try {
        const existingModal = document.getElementById('editPaymentPlanModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('editPaymentPlanModal', 'تعديل خطة السداد');

        const response = await $.ajax({
            url: getRouteUrl('customers.payment-plans.edit', {
                customer: currentCustomerId,
                customerPaymentPlan: planId
            }),
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#editPaymentPlanModal .modal-content').html(response.html);
            bindEditModalEvents();
        } else {
            toastr.error('حدث خطأ في تحميل البيانات');
            hideModal('editPaymentPlanModal');
        }
    } catch (error) {
        console.error('خطأ في تحميل بيانات التعديل:', error);
        toastr.error('حدث خطأ في تحميل البيانات');
        hideModal('editPaymentPlanModal');
    }
}

// إضافة حقل خطة سداد جديد
function addPaymentPlanField() {
    planIndex++;

    const fieldHtml = `
        <div class="border rounded p-3 mb-3" data-index="${planIndex}">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                    <input type="date"
                           name="payment_plans[${planIndex}][due_date]"
                           class="form-control"
                           min="${getTodayDate()}"
                           value="${getTodayDate()}"
                           required>
                    <div class="form-text">التاريخ يجب أن يكون اليوم أو بعده</div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">مبلغ الاستحقاق <span class="text-danger">*</span></label>
                    <input type="number"
                           name="payment_plans[${planIndex}][due_amount]"
                           class="form-control"
                           step="0.01"
                           min="0.01"
                           placeholder="0.00"
                           required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm w-100 d-block js-remove-payment-plan-field-btn"
                            data-index="${planIndex}"
                            title="حذف هذه الخطة">
                        حـذف
                    </button>
                </div>
            </div>
        </div>
    `;

    const container = document.getElementById('payment-plans-container');
    if (container) {
        container.insertAdjacentHTML('beforeend', fieldHtml);
    }
}

// حذف حقل خطة سداد
function removePaymentPlanField(index) {
    const field = document.querySelector(`[data-index="${index}"]`);
    if (field) {
        field.remove();

        // إضافة حقل جديد إذا لم يتبق أي حقل
        const remainingFields = document.querySelectorAll('[data-index]');
        if (remainingFields.length === 0) {
            addPaymentPlanField();
        }
    }
}

// الحصول على تاريخ اليوم بصيغة YYYY-MM-DD
function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// توليد رابط بناءً على اسم المسار والمعاملات
function getRouteUrl(routeName, params = {}) {
    if (typeof window.routes === 'undefined' || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }
    let url = window.routes[routeName];

    if (params.customerPaymentPlan) {
        url = url.replace(/PLAN_ID/g, params.customerPaymentPlan);
    }
    if (params.customer) {
        // (في حالة وجود CUSTOMER_ID في بعض الروابط)
        url = url.replace(/CUSTOMER_ID/g, params.customer);
    }

    // توحيد البداية
    if (!url.startsWith('http') && !url.startsWith('/')) {
        url = '/' + url;
    }
    return url;
}


// حذف خطة سداد محفوظة
function deletePaymentPlan(planId) {
    if (!planId) {
        toastr.error('معرف خطة السداد غير صحيح');
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
                url: getRouteUrl('customers.payment-plans.destroy', {
                    customer: currentCustomerId,
                    customerPaymentPlan: planId
                }),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم حذف خطة السداد بنجاح');

                        // إزالة الخطة من القائمة
                        const planElement = document.getElementById(`plan-${planId}`);
                        if (planElement) {
                            planElement.style.transition = 'opacity 0.3s';
                            planElement.style.opacity = '0';
                            setTimeout(() => {
                                planElement.remove();
                                checkAndShowNoPlansMessage();
                            }, 300);
                        }
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    console.error('خطأ في حذف خطة السداد:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف خطة السداد';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

// مودال التحميل
function showLoadingModal(modalId, title) {
    const loadingHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog modal-lg">
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

    $('#customer-payment-plan-modals-container').html(loadingHtml);
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

// ربط أحداث مودال الإضافة
function bindCreateModalEvents() {
    $('#savePaymentPlans').off('click.paymentPlanModal');

    $('#savePaymentPlans').on('click.paymentPlanModal', function () {
        const form = $('#createPaymentPlanForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');

        if (!validateForm(form)) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        $.ajax({
            url: getRouteUrl('customers.payment-plans.store', { customer: currentCustomerId }),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    hideModal('createPaymentPlanModal');

                    // تحديث قائمة خطط السداد بدون إعادة تحميل الصفحة
                    refreshPaymentPlansList();
                } else {
                    toastr.error(response.message || 'حدث خطأ في الحفظ');
                }
            },
            error: function (xhr) {
                console.error('خطأ في الحفظ:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors(form, xhr.responseJSON.errors);
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
    $('#updatePaymentPlan').off('click.paymentPlanModal');

    $('#updatePaymentPlan').on('click.paymentPlanModal', function () {
        const form = $('#editPaymentPlanForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');
        const planId = form.data('plan-id');

        if (!validateForm(form)) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        $.ajax({
            url: getRouteUrl('customers.payment-plans.update', {
                customer: currentCustomerId,
                customerPaymentPlan: planId
            }),
            type: 'POST',
            data: form.serialize() + '&_method=PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    hideModal('editPaymentPlanModal');

                    // تحديث خطة السداد في القائمة بدون إعادة تحميل الصفحة
                    updatePaymentPlanInList(planId, response.data);
                } else {
                    toastr.error(response.message || 'حدث خطأ في التحديث');
                }
            },
            error: function (xhr) {
                console.error('خطأ في التحديث:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors(form, xhr.responseJSON.errors);
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

// تحديث قائمة خطط السداد بعد الإضافة
function refreshPaymentPlansList() {
    $.ajax({
        url: getRouteUrl('customers.payment-plans.list', { customer: currentCustomerId }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#saved-payment-plans').html(response.html);
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

// تحديث خطة سداد محددة في القائمة بعد التعديل
function updatePaymentPlanInList(planId, updatedPlan) {
    const planElement = document.getElementById(`plan-${planId}`);
    if (planElement) {
        // تحديث البيانات في العنصر الحالي
        planElement.querySelector('.col-md-3:nth-child(1) .fw-medium').textContent = updatedPlan.due_date_formatted;
        planElement.querySelector('.col-md-3:nth-child(2) .fw-medium').textContent = updatedPlan.due_amount_formatted;

        // إضافة تأثير بصري للتحديث
        planElement.style.transition = 'background-color 0.5s';
        planElement.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            planElement.style.backgroundColor = '';
        }, 1000);
    } else {
        // إذا لم يتم العثور على العنصر، قم بتحديث القائمة بأكملها
        refreshPaymentPlansList();
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

        // للحقول المتعددة مثل payment_plans.0.due_date
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

// التحقق من وجود خطط وإظهار رسالة مناسبة
function checkAndShowNoPlansMessage() {
    const savedPlans = document.getElementById('saved-payment-plans');
    const planElements = savedPlans.querySelectorAll('.border');

    if (planElements.length === 0) {
        const noPlansHtml = `
            <div class="text-center py-5" id="no-plans-message">
                <p class="text-muted mb-0">لا توجد خطط سداد</p>
                <small class="text-muted">ابدأ بإضافة خطة سداد لهذا العميل</small>
            </div>
        `;
        savedPlans.innerHTML = noPlansHtml;
    }
}