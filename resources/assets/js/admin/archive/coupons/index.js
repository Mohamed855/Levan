"use strict";

$(function () {
    initializeComponents();
    initializeDataTableEvents();
});

function initializeComponents() {
    $(".select2").select2({
        allowClear: true,
        placeholder: "الكل",
        dir: "rtl",
        width: "100%",
        language: "ar",
    });
}

function initializeDataTableEvents() {
    $("#coupon-archive-table").on("init.dt", function () {
        const table = $(this).DataTable();
        const tableWrapper = $("#coupon-archive-table_wrapper");

        bindSelectionEvents(table, tableWrapper);
        bindTableEvents(table);
    });
}

function bindSelectionEvents(table, tableWrapper) {
    tableWrapper.on("click", "#select-all", function () {
        const isChecked = this.checked;
        const rows = table.rows({ search: "applied" }).nodes();
        $("input.row-checkbox", rows).prop("checked", isChecked);
    });

    tableWrapper.on("change", ".row-checkbox", function () {
        if (!this.checked) {
            $("#select-all").prop("checked", false);
        }
    });
}

function bindTableEvents(table) {
    table.on("draw", function () {
        $("#select-all").prop("checked", false);
    });
}

document.addEventListener("click", function (e) {
    const btn = e.target.closest(".js-restore-btn");
    if (!btn) return;
    const id = btn.dataset.id;
    if (!id) return;

    confirmRestore(id);
});

document.addEventListener("click", function (e) {
    const btn = e.target.closest(".js-force-btn");
    if (!btn) return;
    const id = btn.dataset.id;
    if (!id) return;

    confirmForceDelete(id);
});

function confirmRestore(id) {
    if (!id || !validateCompanyId(id)) {
        showError("معرف المستخدم غير صحيح");
        return;
    }

    window.confirmAction({
        title: "هل أنت متأكد؟",
        text: "سيتم استرجاع هذا الكوبون من الأرشيف",
        confirmButtonText: "نعم، استرجع",
        confirmButtonClass: "btn btn-primary",
        icon: "ti ti-refresh text-primary",
        onConfirm: function () {
            executeRestore(id);
        },
    });
}

function executeRestore(id) {
    const form = createFormForAction(
        "PATCH",
        window.routes.restore.replace(":id", id),
    );
    document.body.appendChild(form);
    form.submit();
}

function confirmForceDelete(userId) {
    if (!userId || !validateCompanyId(userId)) {
        showError("معرف المستخدم غير صحيح");
        return;
    }

    window.confirmAction({
        title: "تحذير!",
        text: "سيتم حذف هذا الكوبون نهائياً ولا يمكن استرجاعه",
        confirmButtonText: "تأكيد الحذف نهائياً",
        confirmButtonClass: "btn btn-danger",
        icon: "ti ti-trash text-white",
        onConfirm: function () {
            executeForceDelete(userId);
        },
    });
}

function executeForceDelete(userId) {
    const form = createFormForAction(
        "DELETE",
        window.routes.forceDelete.replace(":id", userId),
    );
    document.body.appendChild(form);
    form.submit();
}

function handleMassRestore() {
    const selectedIds = getSelectedCompanyIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    window.confirmAction({
        title: "استعادة جماعية",
        text: `سيتم استرجاع ${selectedIds.length} كوبون من الأرشيف`,
        confirmButtonText: "استرجع الكل",
        confirmButtonClass: "btn btn-primary",
        icon: "ti ti-refresh text-primary",
        onConfirm: function () {
            executeMassRestore(selectedIds);
        },
    });
}

function executeMassRestore(selectedIds) {
    showLoadingSpinner("جاري الاستعادة...");

    $.ajax({
        url: window.routes.massRestore,
        type: "POST",
        data: {
            _token: window.csrfToken,
            ids: selectedIds,
        },
        success: function (response) {
            hideLoadingSpinner();
            handleMassRestoreSuccess(response);
        },
        error: function (xhr) {
            hideLoadingSpinner();
            handleMassRestoreError(xhr);
        },
    });
}

function handleMassForceDelete() {
    const selectedIds = getSelectedCompanyIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    window.confirmAction({
        title: "تحذير خطير!",
        text: `سيتم حذف ${selectedIds.length} كوبون نهائياً ولا يمكن استرجاعها. هل أنت متأكد؟`,
        confirmButtonText: "تأكيد الحذف الكل نهائياً",
        confirmButtonClass: "btn btn-danger",
        icon: "ti ti-trash text-white",
        onConfirm: function () {
            executeMassForceDelete(selectedIds);
        },
    });
}

function executeMassForceDelete(selectedIds) {
    showLoadingSpinner("جاري الحذف النهائي...");

    $.ajax({
        url: window.routes.massForceDelete,
        type: "DELETE",
        data: {
            _token: window.csrfToken,
            ids: selectedIds,
        },
        success: function (response) {
            hideLoadingSpinner();
            handleMassForceDeleteSuccess(response);
        },
        error: function (xhr) {
            hideLoadingSpinner();
            handleMassForceDeleteError(xhr);
        },
    });
}

function getSelectedCompanyIds() {
    const selectedIds = [];
    $(".row-checkbox:checked").each(function () {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

function validateSelectedIds(selectedIds) {
    if (selectedIds.length < 1) {
        showWarning("يرجى تحديد شركة واحدة على الأقل");
        return false;
    }
    return true;
}

function validateCompanyId(companyId) {
    return companyId && !isNaN(companyId) && parseInt(companyId) > 0;
}

function createFormForAction(method, actionUrl) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = actionUrl;

    const csrfToken = document.createElement("input");
    csrfToken.type = "hidden";
    csrfToken.name = "_token";
    csrfToken.value = window.csrfToken;
    form.appendChild(csrfToken);

    if (method !== "POST") {
        const methodField = document.createElement("input");
        methodField.type = "hidden";
        methodField.name = "_method";
        methodField.value = method;
        form.appendChild(methodField);
    }

    return form;
}

function refreshTableAndResetSelection() {
    if ($.fn.DataTable.isDataTable("#coupon-archive-table")) {
        $("#coupon-archive-table").DataTable().draw(false);
        $("#select-all").prop("checked", false);
    }
}

function handleMassRestoreSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || "حدث خطأ غير متوقع");
    }
}

function handleMassRestoreError(xhr) {
    const message =
        xhr.responseJSON?.message || "حدث خطأ أثناء الاستعادة الجماعية";
    showError(message);
}

function handleMassForceDeleteSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || "حدث خطأ غير متوقع");
    }
}

function handleMassForceDeleteError(xhr) {
    const message =
        xhr.responseJSON?.message || "حدث خطأ أثناء الحذف النهائي الجماعي";
    showError(message);
}

function showSuccess(message) {
    if (typeof toastr !== "undefined") {
        toastr.success(message);
    }
}

function showError(message) {
    if (typeof toastr !== "undefined") {
        toastr.error(message);
    }
}

function showWarning(message) {
    if (typeof toastr !== "undefined") {
        toastr.warning(message);
    }
}

function showLoadingSpinner(message = "جاري المعالجة...") {
    if (typeof toastr !== "undefined") {
        toastr.info(message);
    }
}

function hideLoadingSpinner() {
    // Handled by toastr auto-timeout
}

window.confirmRestore = confirmRestore;
window.confirmForceDelete = confirmForceDelete;
window.handleMassRestore = handleMassRestore;
window.handleMassForceDelete = handleMassForceDelete;
