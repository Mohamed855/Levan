'use strict';

/**
 * =============================================================================
 * Follow With Accounts Manager
 * =============================================================================
 * إدارة عملية حفظ المتابعة مع اختيار الحسابات المرتبطة
 */

// متغيرات عامة لإدارة المودال
let selectedAccountIds = [];
let accountsModal = null;
let accountsModalCallback = null;

/**
 * التحقق مما إذا كان يجب عرض مودال الحسابات بناءً على نتيجة الاتصال
 */
function shouldShowAccountsModal(contactResultId) {
    const resultsToShowModal = [3, 4, 5, 6, 7, 8];
    return resultsToShowModal.includes(parseInt(contactResultId));
}

/**
 * عرض مودال اختيار الحسابات ثم الحفظ
 */
function showAccountsModalAndSave(form, button, spinner) {
    const contactResultId = $('#contact_result_id').val();

    if (shouldShowAccountsModal(contactResultId)) {
        checkAndShowAccountsModal(currentCustomerId, function (selectedIds) {
            proceedWithSave(form, button, spinner, selectedIds);
        });
    } else {
        proceedWithSave(form, button, spinner, [currentCustomerId]);
    }
}

/**
 * ✅ التحقق من وجود حسابات مرتبطة قبل عرض المودال
 */
function checkAndShowAccountsModal(customerId, callback) {
    accountsModalCallback = callback;
    selectedAccountIds = [customerId];

    const url = getRouteUrl('customers.follows.get-related-accounts', { customer: customerId });

    $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
            if (response.success && response.data && response.data.length > 0) {
                console.log('[FollowWithAccounts] Found', response.data.length, 'related accounts');
                showAccountsModalWithData(response.data);
            } else {
                console.log('[FollowWithAccounts] No related accounts found, saving directly');
                if (accountsModalCallback) {
                    accountsModalCallback([customerId]);
                }
            }
        },
        error: function (xhr) {
            console.error('[FollowWithAccounts] Error loading accounts:', xhr);
            toastr.error('حدث خطأ في جلب الحسابات المرتبطة');
            if (accountsModalCallback) {
                accountsModalCallback([customerId]);
            }
        }
    });
}

/**
 * ✅ عرض المودال مع البيانات المحملة مسبقاً
 */
function showAccountsModalWithData(accountsData) {
    if (!accountsModal) {
        accountsModal = new bootstrap.Modal(document.getElementById('followAccountsModal'));
    }

    document.getElementById('accountsLoadingState').style.display = 'none';
    document.getElementById('accountsListContainer').style.display = 'block';

    renderAccountsList(accountsData);
    bindAccountSelectionEvents();

    accountsModal.show();
}

/**
 * عرض قائمة الحسابات في المودال
 */
function renderAccountsList(accounts) {
    const container = document.getElementById('relatedAccountsList');
    container.innerHTML = '';

    accounts.forEach(account => {
        const isCurrentAccount = account.id === currentCustomerId;
        const remaining = Number(account.remaining_debt || 0);
        const remainingText = formatCurrency(Math.abs(remaining));

        const debtBg = remaining > 0 ? '#fdecec' : '#e8f7f6';
        const debtColor = remaining > 0 ? '#dc3545' : '#198754';
        const debtBorder = remaining > 0 ? '#f5c2c7' : '#4cc1bb';

        const accountElement = document.createElement('div');
        accountElement.className = 'border-bottom px-2 py-2';
        if (isCurrentAccount) accountElement.classList.add('bg-light');

        accountElement.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="form-check m-0">
                    <input 
                        class="form-check-input account-checkbox" 
                        type="checkbox" 
                        value="${account.id}" 
                        id="account_${account.id}"
                        ${isCurrentAccount ? 'checked disabled' : ''}
                        data-account-id="${account.id}">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold" style="font-size: 0.82rem;">
                                ${account.account_number || 'غير محدد'}
                            </span>
                            ${isCurrentAccount ? `
                            <span class="badge bg-primary" style="font-size: 0.62rem; padding: 2px 6px;">
                                الحالي
                            </span>` : ''}
                        </div>
                        <span class="px-2 py-1"
                            style="
                                font-size: 0.72rem;
                                border-radius: 8px;
                                background: ${debtBg};
                                color: ${debtColor};
                                border: 1px solid ${debtBorder};
                                line-height: 1;
                                white-space: nowrap;
                            ">
                            المديونية: <strong>${remainingText}</strong>
                        </span>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(accountElement);
    });
}

/**
 * تنسيق العملة
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
}

/**
 * ربط الأحداث الخاصة باختيار الحسابات
 */
function bindAccountSelectionEvents() {
    // إزالة الأحداث القديمة لتجنب التكرار
    $('#selectAllAccounts').off('change');
    $('.account-checkbox').off('change');
    $('#confirmAccountsSelection').off('click');

    // حدث تحديد الكل
    $('#selectAllAccounts').on('change', function () {
        const isChecked = this.checked;
        $('.account-checkbox:not(:disabled)').each(function () {
            this.checked = isChecked;
            const accountId = parseInt(this.value);

            if (isChecked && !selectedAccountIds.includes(accountId)) {
                selectedAccountIds.push(accountId);
            } else if (!isChecked && accountId !== currentCustomerId) {
                selectedAccountIds = selectedAccountIds.filter(id => id !== accountId);
            }
        });
    });

    // أحداث تحديد الحسابات الفردية
    $('.account-checkbox:not(:disabled)').on('change', function () {
        const accountId = parseInt(this.value);

        if (this.checked && !selectedAccountIds.includes(accountId)) {
            selectedAccountIds.push(accountId);
        } else if (!this.checked) {
            selectedAccountIds = selectedAccountIds.filter(id => id !== accountId);
        }

        // تحديث حالة "تحديد الكل"
        const allCheckboxes = $('.account-checkbox:not(:disabled)').length;
        const checkedCheckboxes = $('.account-checkbox:not(:disabled):checked').length;
        $('#selectAllAccounts').prop('checked', allCheckboxes === checkedCheckboxes);
    });

    // حدث زر التأكيد
    $('#confirmAccountsSelection').on('click', function () {
        const spinner = $(this).find('.spinner-border');
        spinner.show();

        setTimeout(() => {
            if (accountsModal) accountsModal.hide();
            if (accountsModalCallback) accountsModalCallback(selectedAccountIds);
            spinner.hide();
        }, 300);
    });
}

/**
 * حفظ المتابعة مع الحسابات المختارة
 */
function proceedWithSave(form, button, spinner, selectedAccountIds) {
    console.log('[FollowWithAccounts] Saving follow for accounts:', selectedAccountIds);

    button.prop('disabled', true);
    spinner.show();

    const url = getRouteUrl('customers.follows.store', { customer: currentCustomerId });
    const formData = new FormData(form[0]);

    formData.append('selected_account_ids', JSON.stringify(selectedAccountIds));

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                toastr.success('تم حفظ المتابعة بنجاح');
                hideModal('createFollowModal');
                refreshFollowsTable();
            } else {
                toastr.error('حدث خطأ في الحفظ');
            }
        },
        error: function (xhr) {
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
}

/**
 * ربط أحداث الحفظ للمتابعة الجديدة
 */
function bindCreateModalEventsWithAccounts() {
    $('#saveCustomerFollow').off('click.followModal');

    $('#saveCustomerFollow').on('click.followModal', function () {
        const form = $('#createCustomerFollowForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');

        const contactedPerson = form.find('[name="contacted_person"]').val();
        const contactedPersonNameField = form.find('[name="contacted_person_name"]');

        if (contactedPerson === 'customer') {
            contactedPersonNameField.removeAttr('name');
        } else {
            contactedPersonNameField.attr('name', 'contacted_person_name');
        }

        if (!validateForm(form)) {
            return;
        }

        const contactResultId = $('#contact_result_id').val();
        const paymentReceiptElement = $('#payment_receipt');
        const partialPaymentReceiptElement = $('#partial_payment_receipt');

        const hasPaymentReceipt = paymentReceiptElement.length > 0 && paymentReceiptElement[0].files.length > 0;
        const hasPartialPaymentReceipt = partialPaymentReceiptElement.length > 0 && partialPaymentReceiptElement[0].files.length > 0;

        if ((contactResultId == '1' && !hasPaymentReceipt) ||
            (contactResultId == '2' && !hasPartialPaymentReceipt)) {

            Swal.fire({
                title: '<span style="font-size:1.1rem;">هل تريد حفظ المتابعة دون إيصال ؟</span>',
                text: "لم يتم رفع إيصال السداد",
                icon: 'warning',
                showCancelButton: true,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success custom-confirm',
                    cancelButton: 'btn btn-danger custom-cancel'
                },
                confirmButtonText: 'تأكيد',
                cancelButtonText: 'إلغاء',
            }).then((result) => {
                if (result.isConfirmed) {
                    showAccountsModalAndSave(form, button, spinner);
                }
            });
        } else {
            showAccountsModalAndSave(form, button, spinner);
        }
    });
}

/**
 * ✅ إعادة تعيين حالة المودال عند الإغلاق
 */
$(document).ready(function () {
    $('#followAccountsModal').on('hidden.bs.modal', function () {
        document.getElementById('accountsLoadingState').style.display = 'block';
        document.getElementById('accountsListContainer').style.display = 'none';
        document.getElementById('relatedAccountsList').innerHTML = '';
        document.getElementById('selectAllAccounts').checked = false;
        selectedAccountIds = [];
        accountsModalCallback = null;
    });
});

// ✅ تصدير الدوال
window.bindCreateModalEventsWithAccounts = bindCreateModalEventsWithAccounts;
window.showAccountsModalAndSave = showAccountsModalAndSave;
window.proceedWithSave = proceedWithSave;
window.shouldShowAccountsModal = shouldShowAccountsModal;