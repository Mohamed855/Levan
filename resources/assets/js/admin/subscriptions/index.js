'use strict';

let currentAction = null;
let currentTargetIds = [];

$(document).ready(function () {
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#subscriptions-table').on('init.dt', function () {
        bindTableEvents();
    });

    initConfirmModalEvents();
});

function initializeComponents() {
    $('.filters .select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('.card-body')
        });
    });
}

function bindTableEvents() {
    const table = $('#subscriptions-table').DataTable();

    // الفلاتر
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.draw();
    });

    const tableWrapper = $('#subscriptions-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        } else {
            const allChecked = $('.row-checkbox:checked', table.rows({ search: 'applied' }).nodes()).length ===
                               $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).length;
            $('#select-all').prop('checked', allChecked);
        }
    });

    $('#subscriptions-table').on('draw.dt', function () {
        $('#select-all').prop('checked', false);
        // Initialize tooltips if bootstrap tooltips are available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد اشتراك واحد على الأقل');
        return;
    }

    if (typeof confirmDeleteSelectedmss === 'function') {
        confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Subscription');
    }
}

function handleMassAccept() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد اشتراك واحد على الأقل للقبول');
        return;
    }

    openConfirmModal('mass-accept', selectedIds);
}

function handleMassReject() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد اشتراك واحد على الأقل للرفض');
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

    // إعادة تعيين الحقول
    $rejectReason.val('');
    $rejectError.addClass('d-none').text('');
    $rejectReasonContainer.addClass('d-none');

    // إعادة تعيين فئات الأزرار والأيقونات
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

    $modal.modal('show');
}

function initConfirmModalEvents() {
    $('#subscriptionConfirmModalSubmitBtn').on('click', function () {
        const $btn = $(this);
        const rejectReason = $('#subscription_reject_reason').val().trim();

        let url = '';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        let payload = {
            _token: csrfToken
        };

        if (currentAction === 'accept') {
            if (!window.routes?.acceptSubscription) return;
            url = window.routes.acceptSubscription.replace(':id', currentTargetIds[0]);
        } else if (currentAction === 'reject') {
            if (!window.routes?.rejectSubscription) return;
            url = window.routes.rejectSubscription.replace(':id', currentTargetIds[0]);
            payload.reject_reason = rejectReason;
        } else if (currentAction === 'mass-accept') {
            if (!window.routes?.massAccept) return;
            url = window.routes.massAccept;
            payload.ids = currentTargetIds;
        } else if (currentAction === 'mass-reject') {
            if (!window.routes?.massReject) return;
            url = window.routes.massReject;
            payload.ids = currentTargetIds;
            payload.reject_reason = rejectReason;
        }

        if (!url) {
            toastr.error('مسار العملية غير معرف');
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
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                $('#subscriptionConfirmModal').modal('hide');
                if (response.success) {
                    toastr.success(response.message || 'تم تنفيذ العملية بنجاح');
                    if ($.fn.DataTable.isDataTable('#subscriptions-table')) {
                        $('#subscriptions-table').DataTable().ajax.reload(null, false);
                    }
                    $('#select-all').prop('checked', false);
                } else {
                    toastr.error(response.message || 'حدث خطأ ما');
                }
            },
            error: function (xhr) {
                let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const firstError = Object.values(xhr.responseJSON.errors)[0];
                    errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                } else if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
}

// تصدير الدوال للاستخدام العام
window.handleMassDelete = handleMassDelete;
window.handleMassAccept = handleMassAccept;
window.handleMassReject = handleMassReject;
window.openConfirmModal = openConfirmModal;
