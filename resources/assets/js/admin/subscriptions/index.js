'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

let currentAction = null;
let currentTargetIds = [];

$(function () {
    initializeTableStatusToggles();

    $(document).on('click', '#subscriptions-table_wrapper #select-all, #select-all', function () {
        if (!$.fn.DataTable.isDataTable('#subscriptions-table')) return;
        const table = $('#subscriptions-table').DataTable();
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    $(document).on('change', '#subscriptions-table_wrapper .row-checkbox, .row-checkbox', function () {
        if (!$.fn.DataTable.isDataTable('#subscriptions-table')) return;
        const table = $('#subscriptions-table').DataTable();
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        } else {
            const totalRows = $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).length;
            const checkedRows = $('.row-checkbox:checked', table.rows({ search: 'applied' }).nodes()).length;
            $('#select-all').prop('checked', totalRows > 0 && totalRows === checkedRows);
        }
    });

    $('#subscriptions-table').on('draw.dt', function () {
        $('#select-all').prop('checked', false);
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('#subscriptions-table [data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
            });
        }
    });

    document.addEventListener('table-status-toggled', () => {
        if ($.fn.DataTable.isDataTable('#subscriptions-table')) {
            $('#subscriptions-table').DataTable().ajax.reload(null, false);
        }
    });

    bindSubscriptionActionEvents();
    initConfirmModalEvents();
});

function bindSubscriptionActionEvents() {
    $(document).on('click', '.js-subscription-action', function (e) {
        e.preventDefault();
        const $button = $(this).closest('.js-subscription-action');
        const action = $button.attr('data-action') || $button.data('action');
        const rawId = $button.attr('data-subscription-id') || $button.data('subscription-id');
        const companyName = $button.attr('data-company-name') || $button.data('company-name') || '';

        const subId = Number(rawId);
        if (!subId || isNaN(subId)) {
            if (typeof toastr !== 'undefined') {
                toastr.error('تعذر تحديد رقم الاشتراك');
            }
            return;
        }

        openConfirmModal(action, subId, companyName);
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد اشتراك واحد على الأقل');
        }
        return;
    }

    if (typeof confirmDeleteSelectedmss === 'function') {
        confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Subscription');
    }
}

function handleMassAccept() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        const val = Number($(this).val());
        if (!isNaN(val) && val > 0) {
            selectedIds.push(val);
        }
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد اشتراك واحد على الأقل للقبول');
        }
        return;
    }

    openConfirmModal('mass-accept', selectedIds);
}

function handleMassReject() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        const val = Number($(this).val());
        if (!isNaN(val) && val > 0) {
            selectedIds.push(val);
        }
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد اشتراك واحد على الأقل للرفض');
        }
        return;
    }

    openConfirmModal('mass-reject', selectedIds);
}

function openConfirmModal(action, ids, name = '') {
    currentAction = action;
    currentTargetIds = Array.isArray(ids) ? ids : [ids];

    const $modal = $('#subscriptionConfirmModal');
    const $title = $('#subscriptionConfirmModalTitle');
    const $heading = $('#subscriptionConfirmModalHeading');
    const $message = $('#subscriptionConfirmModalMessage');
    const $iconContainer = $('#subscriptionConfirmModalIcon');
    const $submitBtn = $('#subscriptionConfirmModalSubmitBtn');
    const $rejectReasonContainer = $('#subscriptionRejectReasonContainer');
    const $rejectReason = $('#subscription_reject_reason');
    const $rejectError = $('#subscription_reject_reason_error');

    $rejectReason.val('');
    $rejectError.addClass('d-none').text('');
    $rejectReasonContainer.addClass('d-none');

    $submitBtn.removeClass('btn-success btn-danger btn-primary');
    $iconContainer.removeClass('bg-label-success bg-label-danger bg-label-warning');

    if (action === 'accept') {
        $title.text('تأكيد قبول طلب الاشتراك');
        $heading.text('تأكيد قبول الاشتراك');
        const displayName = name ? `<strong class="text-heading">${name}</strong>` : 'هذا الاشتراك';
        $message.html(`هل أنت متأكد من رغبتك في قبول طلب الاشتراك الخاص بـ ${displayName}؟`);
        $iconContainer.addClass('bg-label-success').html('<i class="ti ti-check ti-lg"></i>');
        $submitBtn.addClass('btn-success').text('تأكيد القبول');
    } else if (action === 'reject') {
        $title.text('تأكيد رفض طلب الاشتراك');
        $heading.text('تأكيد رفض الاشتراك');
        const displayName = name ? `<strong class="text-heading">${name}</strong>` : 'هذا الاشتراك';
        $message.html(`هل أنت متأكد من رغبتك في رفض طلب الاشتراك الخاص بـ ${displayName}؟`);
        $iconContainer.addClass('bg-label-danger').html('<i class="ti ti-x ti-lg"></i>');
        $rejectReasonContainer.removeClass('d-none');
        $submitBtn.addClass('btn-danger').text('تأكيد الرفض');
    } else if (action === 'mass-accept') {
        $title.text('تأكيد قبول الاشتراكات المحددة');
        $heading.text(`تأكيد قبول ${currentTargetIds.length} طلب اشتراك`);
        $message.html(`هل أنت متأكد من رغبتك في قبول <strong class="text-heading">${currentTargetIds.length}</strong> من طلبات الاشتراك المحددة؟`);
        $iconContainer.addClass('bg-label-success').html('<i class="ti ti-check ti-lg"></i>');
        $submitBtn.addClass('btn-success').text('تأكيد القبول');
    } else if (action === 'mass-reject') {
        $title.text('تأكيد رفض الاشتراكات المحددة');
        $heading.text(`تأكيد رفض ${currentTargetIds.length} طلب اشتراك`);
        $message.html(`هل أنت متأكد من رغبتك في رفض <strong class="text-heading">${currentTargetIds.length}</strong> من طلبات الاشتراك المحددة؟`);
        $iconContainer.addClass('bg-label-danger').html('<i class="ti ti-x ti-lg"></i>');
        $rejectReasonContainer.removeClass('d-none');
        $submitBtn.addClass('btn-danger').text('تأكيد الرفض');
    }

    const modalElement = $modal.get(0);
    if (!modalElement) {
        if (typeof toastr !== 'undefined') {
            toastr.error('تعذر فتح نافذة تأكيد الإجراء');
        }
        return;
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    } else if (typeof $modal.modal === 'function') {
        $modal.modal('show');
    }
}

function initConfirmModalEvents() {
    $('#subscriptionConfirmModalSubmitBtn').off('click').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const rejectReason = $('#subscription_reject_reason').val()?.trim() || '';

        let url = '';
        const csrfToken = $('meta[name="csrf-token"]').attr('content')
            || $('input[name="_token"]').val()
            || window.csrfToken;

        let payload = {
            _token: csrfToken
        };

        if (currentAction === 'accept' || currentAction === 'reject') {
            const targetId = currentTargetIds[0];
            if (!targetId || isNaN(Number(targetId))) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('لم يتم تحديد رقم اشتراك صالح للعملية');
                }
                return;
            }

            const routeTpl = currentAction === 'accept'
                ? window.routes?.acceptSubscription
                : window.routes?.rejectSubscription;

            if (!routeTpl) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('مسار العملية غير معرف');
                }
                return;
            }

            url = routeTpl.replace(':id', targetId).replace('%3Aid', targetId);
            if (currentAction === 'reject' && rejectReason) {
                payload.reject_reason = rejectReason;
            }
        } else if (currentAction === 'mass-accept' || currentAction === 'mass-reject') {
            const validIds = currentTargetIds.map(id => Number(id)).filter(id => !isNaN(id) && id > 0);
            if (validIds.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('يرجى تحديد اشتراك واحد على الأقل');
                }
                return;
            }

            url = currentAction === 'mass-accept'
                ? window.routes?.massAccept
                : window.routes?.massReject;

            if (!url) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('مسار العملية غير معرف');
                }
                return;
            }

            payload.ids = validIds;
            if (currentAction === 'mass-reject' && rejectReason) {
                payload.reject_reason = rejectReason;
            }
        }

        if (!url) {
            if (typeof toastr !== 'undefined') {
                toastr.error('مسار العملية غير معرف');
            }
            return;
        }

        const originalBtnText = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جاري التنفيذ...');

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (response) {
                closeModal();

                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'تم تنفيذ العملية بنجاح');
                    }
                    if ($.fn.DataTable.isDataTable('#subscriptions-table')) {
                        $('#subscriptions-table').DataTable().ajax.reload(null, false);
                    }
                    $('#select-all').prop('checked', false);
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message || 'حدث خطأ ما');
                    }
                }
            },
            error: function (xhr) {
                let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstError = Object.values(xhr.responseJSON.errors)[0];
                    errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                } else if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMessage = 'غير مصرح لك بتنفيذ هذه العملية';
                } else if (xhr.status === 404) {
                    errorMessage = 'الاشتراك المطلوب غير موجود';
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
}

function closeModal() {
    const modalElement = document.getElementById('subscriptionConfirmModal');
    if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const inst = bootstrap.Modal.getInstance(modalElement) || bootstrap.Modal.getOrCreateInstance(modalElement);
        inst.hide();
    } else if ($('#subscriptionConfirmModal').length && typeof $('#subscriptionConfirmModal').modal === 'function') {
        $('#subscriptionConfirmModal').modal('hide');
    }

    setTimeout(() => {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }, 300);
}

window.handleMassDelete = handleMassDelete;
window.handleMassAccept = handleMassAccept;
window.handleMassReject = handleMassReject;
window.openConfirmModal = openConfirmModal;
