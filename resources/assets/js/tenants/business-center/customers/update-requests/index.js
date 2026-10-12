"use strict";

$(document).ready(function () {
    initializeComponents();
    bindEvents();

    // تحديث عدد الطلبات المعلقة كل 30 ثانية
    setInterval(updatePendingCount, 30000);
});

/*
|--------------------------------------------------------------------------
| Component Initialization
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    // تهيئة Select2 للفلاتر
    $(".select2").select2({
        placeholder: "اختر",
        allowClear: true,
        width: "100%",
        language: "ar",
        dir: "rtl",
    });
}

/*
|--------------------------------------------------------------------------
| Event Binding
|--------------------------------------------------------------------------
*/
function bindEvents() {
    // حدث النقر على زر الموافقة
    $(document).on("click", ".js-approve-request-btn", function () {
        const requestId = $(this).data("id");
        approveUpdateRequest(requestId);
    });

    // حدث النقر على زر الرفض
    $(document).on("click", ".js-reject-request-btn", function () {
        const requestId = $(this).data("id");
        showRejectionModal(requestId);
    });

    // حدث النقر على زر عرض التفاصيل
    $(document).on("click", ".js-view-request-btn", function () {
        const requestId = $(this).data("id");
        viewUpdateRequest(requestId);
    });

    // حدث تأكيد الرفض
    $("#confirmRejection").on("click", function () {
        const requestId = $("#rejectionRequestId").val();
        const reason = $("#rejectionReason").val();
        rejectUpdateRequest(requestId, reason);
    });

    // حدث تأكيد الرفض الجماعي
    $("#confirmBulkRejection").on("click", function () {
        const requestIds = JSON.parse($("#bulkRejectionRequestIds").val());
        const reason = $("#bulkRejectionReason").val();
        bulkRejectRequests(requestIds, reason);
    });

    // حدث تحديد الكل
    $("#select-all").on("change", function () {
        $(".row-checkbox").prop("checked", $(this).prop("checked"));
    });
}

/*
|--------------------------------------------------------------------------
| Request Operations
|--------------------------------------------------------------------------
*/
function approveUpdateRequest(requestId) {
    Swal.fire({
        title: "تأكيد الموافقة",
        text: "هل أنت متأكد من الموافقة على هذا الطلب؟",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "موافقة",
        cancelButtonText: "إلغاء",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-success me-2",
            cancelButton: "btn btn-secondary",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .post(window.routes.approveRequest.replace(":id", requestId), {
                    _token: window.csrfToken,
                })
                .then((response) => {
                    if (response.data.success) {
                        toastr.success(response.data.message);
                        $("#customer-update-requests-table")
                            .DataTable()
                            .ajax.reload();
                        updatePendingCount();
                    } else {
                        toastr.error(response.data.message);
                    }
                })
                .catch((error) => {
                    toastr.error("حدث خطأ أثناء الموافقة على الطلب");
                });
        }
    });
}

function rejectUpdateRequest(requestId, reason) {
    axios
        .post(window.routes.rejectRequest.replace(":id", requestId), {
            _token: window.csrfToken,
            reason: reason,
        })
        .then((response) => {
            if (response.data.success) {
                toastr.success(response.data.message);
                $("#rejectionReasonModal").modal("hide");
                $("#customer-update-requests-table").DataTable().ajax.reload();
                updatePendingCount();
            } else {
                toastr.error(response.data.message);
            }
        })
        .catch((error) => {
            toastr.error("حدث خطأ أثناء رفض الطلب");
        });
}

function viewUpdateRequest(requestId) {
    // فتح الصفحة في تبويب جديد
    window.open(window.routes.viewRequest.replace(":id", requestId), "_blank");
}

function bulkApproveRequests(requestIds) {
    Swal.fire({
        title: "تأكيد الموافقة الجماعية",
        text: `هل أنت متأكد من الموافقة على ${requestIds.length} طلب؟`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "موافقة",
        cancelButtonText: "إلغاء",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-success me-2",
            cancelButton: "btn btn-secondary",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .post(window.routes.bulkApprove, {
                    _token: window.csrfToken,
                    request_ids: requestIds,
                })
                .then((response) => {
                    if (response.data.success) {
                        toastr.success(response.data.message);
                        $("#customer-update-requests-table")
                            .DataTable()
                            .ajax.reload();
                        updatePendingCount();
                    } else {
                        toastr.error(response.data.message);
                    }
                })
                .catch((error) => {
                    toastr.error("حدث خطأ أثناء الموافقة على الطلبات");
                });
        }
    });
}

function bulkRejectRequests(requestIds, reason) {
    axios
        .post(window.routes.bulkReject, {
            _token: window.csrfToken,
            request_ids: requestIds,
            reason: reason,
        })
        .then((response) => {
            if (response.data.success) {
                toastr.success(response.data.message);
                $("#bulkRejectionReasonModal").modal("hide");
                $("#customer-update-requests-table").DataTable().ajax.reload();
                updatePendingCount();
            } else {
                toastr.error(response.data.message);
            }
        })
        .catch((error) => {
            toastr.error("حدث خطأ أثناء رفض الطلبات");
        });
}

/*
|--------------------------------------------------------------------------
| Export Update Requests
|--------------------------------------------------------------------------
*/
function exportUpdateRequests() {
    const filters = {
        status: $("#filter-status").val() || "",
        requested_by: $("#filter-requested-by").val() || "",
        search_query: $("#filter-search").val() || "",
    };

    $.ajax({
        url: window.routes.exportFields,
        type: "POST",
        data: {
            _token: window.csrfToken,
            ...filters,
        },
        success: function (response) {
            if (response.success) {
                showExportFieldsModal(response.fields, response.count, filters);
            }
        },
        error: function () {
            toastr.error("حدث خطأ أثناء جلب بيانات التصدير");
        },
    });
}

/*
|--------------------------------------------------------------------------
| Show Export Fields Modal
|--------------------------------------------------------------------------
*/
function showExportFieldsModal(fields, count, filters) {
    const modalHtml = `
        <div class="modal fade" id="exportFieldsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تصدير طلبات تحديث بيانات العملاء</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3">
                            <small>
                                <strong>عدد السجلات:</strong> ${count.toLocaleString()} طلب
                                <span class="mx-2">|</span>
                                <span id="selectedFieldsCount">0</span> حقل محدد
                            </small>
                        </div>
                        
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 mx-1" id="selectAllFields">
                                تحديد الكل
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm py-1 px-2 mx-1" id="deselectAllFields">
                                إلغاء التحديد
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm py-1 px-2 mx-1" id="selectDefaultFields">
                                الافتراضية
                            </button>
                        </div>
                        
                        <div class="card">
                            <div class="card-header py-2" data-bs-toggle="collapse" data-bs-target="#fieldsCollapse" aria-expanded="false" style="cursor: pointer;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">الحقول المتاحة</h6>
                                    <div>
                                        <span class="badge bg-secondary" id="allFieldsCount">0/${fields.length}</span>
                                        <i class="ti ti-chevron-down ms-2"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="collapse" id="fieldsCollapse">
                                <div class="card-body py-3">
                                    <div class="row g-2">
                                        ${generateFieldsGrid(fields)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <button type="button" class="btn btn-primary" id="confirmExport">تصدير</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // إزالة المودال إذا كان موجودًا بالفعل
    $("#exportFieldsModal").remove();

    // إضافة المودال الجديد
    $("body").append(modalHtml);

    // عرض المودال
    $("#exportFieldsModal").modal("show");

    // تحديث عدد الحقول
    updateFieldsCount();

    // ربط الأحداث
    bindExportModalEvents(filters);

    // أحداث القائمة المنسدلة
    $("#fieldsCollapse").on("show.bs.collapse", function () {
        $(".ti-chevron-down")
            .removeClass("ti-chevron-down")
            .addClass("ti-chevron-up");
    });

    $("#fieldsCollapse").on("hide.bs.collapse", function () {
        $(".ti-chevron-up")
            .removeClass("ti-chevron-up")
            .addClass("ti-chevron-down");
    });
}

/*
|--------------------------------------------------------------------------
| Generate Fields Grid
|--------------------------------------------------------------------------
*/
function generateFieldsGrid(fields) {
    let html = "";

    fields.forEach((field, index) => {
        html += `
            <div class="col-md-4 col-sm-6">
                <div class="form-check">
                    <input class="form-check-input export-field" 
                           type="checkbox" 
                           value="${field.key}" 
                           id="field_${field.key}"
                           ${field.selected ? "checked" : ""}>
                    <label class="form-check-label" 
                           for="field_${field.key}">
                        ${field.label}
                    </label>
                </div>
            </div>
        `;
    });

    return html;
}

/*
|--------------------------------------------------------------------------
| Bind Export Modal Events
|--------------------------------------------------------------------------
*/
function bindExportModalEvents(filters) {
    $("#selectAllFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", true);
            updateFieldsCount();
        });

    $("#deselectAllFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", false);
            updateFieldsCount();
        });

    $("#selectDefaultFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", false);
            const defaultFields = [
                "id",
                "customer_name",
                "customer_identity_number",
                "requested_by_name",
                "created_at",
                "status",
            ];
            defaultFields.forEach((key) => {
                $(`#field_${key}`).prop("checked", true);
            });
            updateFieldsCount();
        });

    $(".export-field").on("change", function () {
        updateFieldsCount();
    });

    $("#confirmExport")
        .off("click")
        .on("click", function () {
            const selectedFields = [];
            $(".export-field:checked").each(function () {
                selectedFields.push($(this).val());
            });

            if (selectedFields.length === 0) {
                toastr.warning("يرجى اختيار حقل واحد على الأقل");
                return;
            }

            $("#exportFieldsModal").modal("hide");
            executeExportWithFields(filters, selectedFields);
        });
}

/*
|--------------------------------------------------------------------------
| Update Fields Count
|--------------------------------------------------------------------------
*/
function updateFieldsCount() {
    const totalChecked = $(".export-field:checked").length;
    $("#selectedFieldsCount").text(totalChecked);

    const allTotal = $(".export-field").length;
    $("#allFieldsCount").text(`${totalChecked}/${allTotal}`);

    const btnText = totalChecked > 0 ? `تصدير (${totalChecked} حقل)` : "تصدير";
    $("#confirmExport").text(btnText);
}

/*
|--------------------------------------------------------------------------
| Execute Export With Fields
|--------------------------------------------------------------------------
*/
function executeExportWithFields(filters, selectedFields) {
    // عرض نافذة التحميل هنا فقط
    showLoadingModal("جاري إنشاء ملف Excel...");

    axios
        .post(
            window.routes.exportExcel,
            {
                _token: window.csrfToken,
                selected_fields: selectedFields,
                ...filters,
            },
            {
                responseType: "blob",
            },
        )
        .then((response) => {
            // === تعديل 2: التحقق من وجود الاستجابة والبيانات ===
            if (!response || !response.data) {
                throw new Error("لم يتم استلام أي بيانات من الخادم");
            }

            // === تعديل 3: التحقق مما إذا كانت الاستجابة خطأ JSON بدلاً من ملف ===
            // إذا كان نوع المحتوى هو JSON، فهذا يعني أن الخادم أرجع خطأ
            const contentType = response.headers["content-type"];
            if (contentType && contentType.includes("application/json")) {
                // قراءة البيانات كنص JSON ثم تحليلها
                return response.data.text().then((text) => {
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.message || "حدث خطأ غير متوقع");
                });
            }

            // === تعديل 4: إنشاء رابط تحميل وتنزيل الملف في نفس التبويب ===
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href = url;

            // استخراج اسم الملف من رأس الاستجابة أو استخدام اسم افتراضي
            const contentDisposition = response.headers["content-disposition"];
            let fileName = "customer_update_requests.xlsx";
            if (contentDisposition) {
                const fileNameMatch =
                    contentDisposition.match(/filename="(.+)"/);
                if (fileNameMatch && fileNameMatch.length === 2)
                    fileName = fileNameMatch[1];
            }

            link.setAttribute("download", fileName);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            // إخفاء التحميل فقط عند النجاح
            hideLoadingModal();
            toastr.success("تم تصدير البيانات بنجاح");
        })
        .catch((error) => {
            // إخفاء التحميل فقط عند الخطأ
            hideLoadingModal();
            console.error("Export error:", error);

            // عرض رسالة الخطأ من الخادم إذا كانت موجودة
            const errorMessage =
                error.response?.data?.message ||
                error.message ||
                "حدث خطأ أثناء تصدير البيانات";
            toastr.error(errorMessage);
        });
}
/*
|--------------------------------------------------------------------------
| Show Loading Modal
|--------------------------------------------------------------------------
*/
function showLoadingModal(message = "جاري المعالجة...") {
    const modalHtml = `
        <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">${message}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    // إزالة المودال إذا كان موجودًا
    $("#loadingModal").remove();

    // إضافة المودال الجديد وعرضه
    $("body").append(modalHtml);
    $("#loadingModal").modal("show");
}

/*
|--------------------------------------------------------------------------
| Hide Loading Modal
|--------------------------------------------------------------------------
*/
function hideLoadingModal() {
    $("#loadingModal").modal("hide");
}

/*
|--------------------------------------------------------------------------
| UI Helpers
|--------------------------------------------------------------------------
*/
function showRejectionModal(requestId) {
    $("#rejectionRequestId").val(requestId);
    $("#rejectionReason").val("");
    $("#rejectionReasonModal").modal("show");
}

function showBulkRejectionModal(requestIds) {
    $("#bulkRejectionRequestIds").val(JSON.stringify(requestIds));
    $("#bulkRejectionReason").val("");
    $("#bulkRejectionReasonModal").modal("show");
}

function getSelectedIds() {
    const ids = [];
    $(".row-checkbox:checked").each(function () {
        ids.push($(this).val());
    });
    return ids;
}

function updatePendingCount() {
    axios
        .get(window.routes.pendingCount)
        .then((response) => {
            if (response.data.success) {
                const count = response.data.count;
                $(".pending-count-badge").text(count);

                if (count > 0) {
                    $(".pending-count-badge").show();
                } else {
                    $(".pending-count-badge").hide();
                }
            }
        })
        .catch((error) => {
            console.error("Error updating pending count:", error);
        });
}

/*
|--------------------------------------------------------------------------
| Global Functions
|--------------------------------------------------------------------------
*/
window.approveUpdateRequest = approveUpdateRequest;
window.rejectUpdateRequest = rejectUpdateRequest;
window.viewUpdateRequest = viewUpdateRequest;
window.showRejectionModal = showRejectionModal;
window.exportUpdateRequests = exportUpdateRequests;

window.approveSelectedRequests = function () {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning("يرجى تحديد طلب واحد على الأقل");
        return;
    }
    bulkApproveRequests(selectedIds);
};

window.rejectSelectedRequests = function () {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning("يرجى تحديد طلب واحد على الأقل");
        return;
    }
    showBulkRejectionModal(selectedIds);
};
