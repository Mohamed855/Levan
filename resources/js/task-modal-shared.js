/**
 * Shared functions for task modal operations
 * Used in both Kanban and List views
 */

// تعريف المتغيرات العامة
let defaultAvatarUrl;

// دالة تهيئة المتغيرات
function initializeSharedVariables(config) {
    defaultAvatarUrl = config.defaultAvatarUrl;
}

// دالة مساعدة للحصول على تفاصيل الأولوية
function getPriorityDetails(priority) {
    switch (priority) {
        case "high":
            return {
                text: "عالية",
                icon: "alert-triangle",
                color: "danger",
                bgColor: "rgba(255, 62, 29, 0.16)",
            };
        case "medium":
            return {
                text: "متوسطة",
                icon: "alert-circle",
                color: "warning",
                bgColor: "rgba(255, 171, 0, 0.16)",
            };
        case "low":
            return {
                text: "منخفضة",
                icon: "alert-octagon",
                color: "success",
                bgColor: "rgba(72, 199, 142, 0.16)",
            };
        default:
            return {
                text: "غير محددة",
                icon: "help-circle",
                color: "secondary",
                bgColor: "rgba(133, 146, 163, 0.16)",
            };
    }
}

// دالة تحديث عرض المكلفين
function updateAssigneesDisplay(assignees) {
    const container = document.getElementById("show_assignees");
    if (container) {
        container.innerHTML = assignees
            .map(
                (assignee) => `
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-2">
                    <img src="${assignee.profile_picture || defaultAvatarUrl}" 
                         alt="${assignee.name}" class="rounded-circle">
                </div>
                <div class="name">${assignee.name}</div>
            </div>
        `,
            )
            .join("");
    }
}

// دالة تحديث البيانات الوصفية للمهمة
function updateTaskMetadata(task) {
    document.getElementById("show_created_by").textContent =
        task.creator?.name || "-";
    document.getElementById("show_created_at").textContent = new Date(
        task.created_at,
    ).toLocaleString("ar-EG", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        calendar: "gregory",
    });

    const updatedInfo = document.getElementById("show_updated_info");
    if (task.updated_by) {
        updatedInfo.classList.remove("d-none");
        document.getElementById("show_updated_at").textContent = new Date(
            task.updated_at,
        ).toLocaleString("ar-EG", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            calendar: "gregory",
        });
    } else {
        updatedInfo.classList.add("d-none");
    }
}

// دالة تحديث المرفقات
function updateAttachmentsDisplay(task) {
    const container = document.getElementById("show_attachments");
    if (!container) return;
    container.innerHTML = "";
    if (task.attachments && task.attachments.length > 0) {
        task.attachments.forEach(function (attachment) {
            const attachmentUrl = "/storage/" + attachment.path;
            const div = document.createElement("div");
            div.className = "mb-2";
            div.innerHTML = `<i class="ti ti-paperclip me-1"></i>
            <a href="${attachmentUrl}" target="_blank" style="text-decoration: none; color: inherit;">
                ${attachment.name}
            </a>`;
            container.appendChild(div);
        });
    } else {
        container.innerHTML = '<p class="text-muted">لا توجد مرفقات</p>';
    }
}

function formatPriorityOption(priority) {
    if (!priority.id) return priority.text;

    const priorityDetails = {
        high: { text: "عالية", color: "danger", icon: "alert-triangle" },
        medium: { text: "متوسطة", color: "warning", icon: "alert-circle" },
        low: { text: "منخفضة", color: "success", icon: "alert-octagon" },
    };

    const details = priorityDetails[priority.id] || {
        text: priority.text,
        color: "secondary",
        icon: "help-circle",
    };

    return `<span>
                <i class="ti ti-${details.icon} text-${details.color} me-1"></i>
                ${details.text}
            </span>`;
}

function formatEmployeeOption(employee) {
    if (!employee.id) return employee.text;

    const avatarSrc = employee.avatar || defaultAvatarUrl;

    return $(`
      <div class="d-flex align-items-center py-1">
          <div class="flex-shrink-0">
              <img src="${avatarSrc}" 
                   alt="${employee.text}" 
                   class="rounded-circle" 
                   style="width: 26px; height: 26px; object-fit: cover;">
          </div>
          <div class="flex-grow-1 ms-2">
              <div style="font-size: 0.875rem; line-height: 1.2;">${employee.text}</div>
          </div>
      </div>
  `);
}

function updateEditForm(task) {
    // تحديث معرف المهمة في النموذج
    $("#update_task_id").val(task.id);

    $("#update_hours").val(task.hours ? task.hours : "");

    // تهيئة وتحديث حقول select2
    initializeSelects(task);

    // تحديث المرفقات مع تمرير معرف المهمة
    updateAttachments(task.attachments || [], task.id);

    // تحديث قيمة الأولوية بعد تهيئة select2
    setTimeout(() => {
        $("#update_priority").val(task.priority).trigger("change");
    }, 100);
}

function initializeSelects(task) {
    const assigneesSelect = $("#update_assignees");
    console.log(
        "Initializing select2 for assignees with task.assignees:",
        task.assignees,
    );
    if (assigneesSelect.length) {
        const employees = window.employeesList || [];
        assigneesSelect.select2({
            dropdownParent: $("#showTaskOffcanvas"),
            placeholder: "اختر الموظفين المكلفين",
            allowClear: true,
            data: employees.map((emp) => ({
                id: emp.id,
                text: emp.name,
                avatar: emp.profile_picture,
            })),
            templateResult: formatEmployeeOption,
            templateSelection: formatEmployeeOption,
        });
        if (task && task.assignees) {
            assigneesSelect
                .val(task.assignees.map((a) => a.id.toString()))
                .trigger("change");
        }
    }
}

function updateAttachments(attachments, taskId) {
    const container = $("#current_attachments");
    console.log("Updating attachments with:", attachments);
    container.empty();
    if (attachments && attachments.length > 0) {
        attachments.forEach((attachment) => {
            const attachmentUrl = "/storage/" + attachment.path;
            container.append(`
                <div class="attachment-item d-flex align-items-center mb-2" data-task-id="${taskId}">
                    <i class="ti ti-paperclip me-2"></i>
                    <a href="${attachmentUrl}" target="_blank" class="attachment-link">${attachment.name}</a>
                    <button type="button" class="btn btn-icon text-danger ms-auto delete-attachment" 
                            data-attachment-id="${attachment.id}">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            `);
        });
    } else {
        container.html('<p class="text-muted mb-0">لا توجد مرفقات</p>');
    }
}

function addAttachmentField() {
    const template = `
      <div class="attachment-input mb-2">
        <div class="input-group">
          <input type="file" class="form-control" name="attachments[]">
          <button type="button" class="btn btn-danger remove-attachment">
            <i class="ti ti-x"></i>
          </button>
        </div>
      </div>
    `;
    $(".attachment-group").append(template);
}

// ربط حدث النقر لزر إضافة المرفقات
$(document).on("click", ".add-more-attachments", function (e) {
    e.preventDefault();
    addAttachmentField();
});

// ربط حدث النقر لزر حذف حقل المرفق
$(document).on("click", ".remove-attachment", function (e) {
    e.preventDefault();
    $(this).closest(".attachment-input").remove();
});

// دالة تحديث التعليق
function updateComment(commentId, newContent, taskId) {
    fetch(
        `/projects/${window.projectId}/sectors/${window.sectorId}/tasks/${taskId}/comments/${commentId}`,
        {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
            body: JSON.stringify({ content: newContent }),
        },
    )
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                toastr.success("تم تحديث التعليق بنجاح");
                window.taskModalFunctions.loadTaskComments(taskId);
            } else {
                toastr.error(result.message || "حدث خطأ أثناء تحديث التعليق");
            }
        })
        .catch((error) => {
            console.error("Error updating comment:", error);
            toastr.error("حدث خطأ غير متوقع أثناء تحديث التعليق");
        });
}

// دالة حذف التعليق
function deleteComment(commentId, taskId) {
    fetch(
        `/projects/${window.projectId}/sectors/${window.sectorId}/tasks/${taskId}/comments/${commentId}`,
        {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
        },
    )
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                toastr.success("تم حذف التعليق بنجاح");
                window.taskModalFunctions.loadTaskComments(taskId);
            } else {
                toastr.error(result.message || "حدث خطأ أثناء حذف التعليق");
            }
        })
        .catch((error) => {
            console.error("Error deleting comment:", error);
            toastr.error("حدث خطأ غير متوقع أثناء حذف التعليق");
        });
}

// دالة تحميل التعليقات
function loadTaskComments(taskId) {
    // إضافة شيفرة CSS الخاصة بتنسيق أزرار التعليقات إذا لم تكن مُضافة
    if (!document.getElementById("comments-interaction-style")) {
        const style = document.createElement("style");
        style.id = "comments-interaction-style";
        style.textContent = `
        /* تحسينات التمرير */
        .comments-list {
            max-height: 400px; 
            overflow-y: auto;
            padding-left: 10px;
        }
        .comments-list::-webkit-scrollbar {
            width: 8px;
        }
        .comments-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .comments-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .comments-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* تحسينات التعليقات */
        .comment-item {
            position: relative;
            transition: background-color 0.3s ease;
        }
        .comment-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .comment-actions {
            position: absolute;
            top: 10px;
            left: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
        }
        .comment-item:hover .comment-actions {
            opacity: 1;
        }
        .comment-buttons {
            display: flex;
            gap: 5px;
            background-color: rgba(255,255,255,0.9);
            padding: 5px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .comment-buttons .btn {
            padding: 4px 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 30px;
            width: 30px;
        }
        .edit-comment-form textarea {
            min-height: 100px;
        }
    `;
        document.head.appendChild(style);
    }

    const projectId = window.projectId;
    const sectorId = window.sectorId;
    const url = `/projects/${projectId}/sectors/${sectorId}/tasks/${taskId}/comments`;

    fetch(url)
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                const commentsContainer =
                    document.getElementById("taskComments");
                if (!commentsContainer) return;

                if (!Array.isArray(result.data) || result.data.length === 0) {
                    commentsContainer.innerHTML =
                        '<p class="text-muted text-center my-4">لا توجد تعليقات بعد</p>';
                    return;
                }

                // توليد HTML للتعليقات باستخدام نفس النمط كما في الكانبان
                commentsContainer.innerHTML = `
                    <div class="comments-list ${result.data.length > 5 ? "overflow" : ""}">
                        ${result.data
                            .map(
                                (comment) => `
                            <div class="comment-item mb-3 position-relative" data-comment-id="${comment.id}">
                                <div class="border rounded p-3 bg-white comment-container">
                                    <div class="comment-actions">
                                        <div class="comment-buttons">
                                            <button type="button" class="btn btn-icon edit-comment-btn" title="تعديل">
                                                <i class="ti ti-edit text-primary"></i>
                                            </button>
                                            <button type="button" class="btn btn-icon delete-comment-btn" title="حذف">
                                                <i class="ti ti-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="avatar avatar-xs">
                                            <img src="${comment.user.profile_picture || defaultAvatarUrl}" 
                                                 alt="${comment.user.name}" 
                                                 class="rounded-circle">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="mb-1">
                                                <span class="fw-semibold">${comment.user.name}</span>
                                            </div>
                                            <div class="comment-content mb-2">
                                                <p class="mb-0 text-body">${comment.content}</p>
                                            </div>
                                            <div class="comment-footer">
                                                <small class="text-muted">
                                                    <i class="ti ti-clock-hour-4 me-1"></i>
                                                    ${new Date(
                                                        comment.created_at,
                                                    ).toLocaleString("ar-EG", {
                                                        year: "numeric",
                                                        month: "long",
                                                        day: "numeric",
                                                        hour: "2-digit",
                                                        minute: "2-digit",
                                                        calendar: "gregory",
                                                    })}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `,
                            )
                            .join("")}
                    </div>
                `;

                // تحديث عداد التعليقات
                const commentsCount = document.getElementById("comments-count");
                if (commentsCount) {
                    commentsCount.textContent = `${result.data.length} تعليقات`;
                }

                // ربط أحداث أزرار التعديل
                const containerEl = document.getElementById("taskComments");
                containerEl
                    .querySelectorAll(".edit-comment-btn")
                    .forEach((btn) => {
                        btn.addEventListener("click", (e) => {
                            const commentItem =
                                e.target.closest(".comment-item");
                            const commentId = commentItem.dataset.commentId;
                            const contentElement =
                                commentItem.querySelector(".comment-content p");
                            const originalContent = contentElement.textContent;

                            // تحويل النص إلى حقل تحرير (textarea)
                            contentElement.innerHTML = `
                            <div class="edit-comment-form">
                                <textarea class="form-control mb-2" rows="4">${originalContent}</textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm save-edit-btn">حفظ</button>
                                    <button type="button" class="btn btn-secondary btn-sm cancel-edit-btn">إلغاء</button>
                                </div>
                            </div>
                        `;

                            contentElement
                                .querySelector(".save-edit-btn")
                                .addEventListener("click", () => {
                                    const newContent =
                                        contentElement.querySelector(
                                            "textarea",
                                        ).value;
                                    updateComment(
                                        commentId,
                                        newContent,
                                        taskId,
                                    );
                                });

                            contentElement
                                .querySelector(".cancel-edit-btn")
                                .addEventListener("click", () => {
                                    contentElement.innerHTML = originalContent;
                                });
                        });
                    });

                // ربط أحداث أزرار الحذف
                containerEl
                    .querySelectorAll(".delete-comment-btn")
                    .forEach((btn) => {
                        btn.addEventListener("click", (e) => {
                            const commentItem =
                                e.target.closest(".comment-item");
                            const commentId = commentItem.dataset.commentId;
                            Swal.fire({
                                title: "هل أنت متأكد من عملية الحذف؟",
                                text: "لا يمكن التراجع عن هذا الإجراء!",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "تأكيد",
                                cancelButtonText: "إلغاء",
                                customClass: {
                                    popup: "custom-popup",
                                    title: "custom-title",
                                    text: "custom-text",
                                    confirmButton:
                                        "btn btn-success custom-confirm",
                                    cancelButton:
                                        "btn btn-danger custom-cancel",
                                },
                                buttonsStyling: false,
                                reverseButtons: false,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    deleteComment(commentId, taskId);
                                }
                            });
                        });
                    });
            }
        })
        .catch((error) => {
            console.error("Error loading comments:", error);
            const commentsContainer = document.getElementById("taskComments");
            if (commentsContainer) {
                commentsContainer.innerHTML =
                    '<p class="text-danger text-center">حدث خطأ أثناء تحميل التعليقات</p>';
            }
            toastr.error("حدث خطأ أثناء تحميل التعليقات");
        });
}

document.addEventListener("DOMContentLoaded", function () {
    const addCommentForm = document.getElementById("addCommentForm");
    if (addCommentForm) {
        addCommentForm.addEventListener("submit", function (e) {
            e.preventDefault(); // منع السلوك الافتراضي للنموذج
            const taskId = document.getElementById("comment_task_id").value;
            const content = document
                .getElementById("comment_content")
                .value.trim();
            if (!content) {
                toastr.error("الرجاء إدخال محتوى التعليق");
                return;
            }
            const url = `/projects/${window.projectId}/sectors/${window.sectorId}/tasks/${taskId}/comments`;
            console.log("Sending comment to URL:", url);
            fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    Accept: "application/json",
                },
                body: JSON.stringify({ content }),
            })
                .then((response) => response.json())
                .then((result) => {
                    console.log("Comment add result:", result);
                    if (result.success) {
                        document.getElementById("comment_content").value = "";
                        window.taskModalFunctions.loadTaskComments(taskId);
                        toastr.success("تم إضافة التعليق بنجاح");
                    } else {
                        toastr.error(
                            result.message || "حدث خطأ أثناء إضافة التعليق",
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error adding comment:", error);
                    toastr.error("حدث خطأ غير متوقع أثناء إضافة التعليق");
                });
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const updateTaskForm = document.getElementById("quickUpdateForm");
    if (updateTaskForm) {
        updateTaskForm.addEventListener("submit", function (e) {
            e.preventDefault(); // منع إعادة تحميل الصفحة بشكل افتراضي

            const formData = new FormData(updateTaskForm);
            const taskId = formData.get("task_id"); // تأكد من أن اسم الحقل في الـinput هو "task_id"
            formData.append("_method", "patch"); // إذا كان الراوت يستخدم PATCH

            fetch(
                `/projects/${window.projectId}/sectors/${window.sectorId}/tasks/${taskId}/quick-update`,
                {
                    method: "POST", // نستخدم POST مع _method لتحديد PATCH
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                },
            )
                .then(async (response) => {
                    // إذا كان الرد 422 => نقرأ JSON ونرميه كخطأ مخصص
                    if (response.status === 422) {
                        const data = await response.json();
                        throw { code: 422, data };
                    }
                    // أي رد غير ناجح (≥ 400) عدا 422 => نرمي خطأ عام
                    if (!response.ok) {
                        throw new Error(
                            `HTTP error! status: ${response.status}`,
                        );
                    }
                    // إذا كان الرد 200 أو ناجح => حوّله إلى JSON
                    return response.json();
                })
                .then((result) => {
                    if (result.success) {
                        toastr.success("تم تحديث المهمة بنجاح");
                        // إعادة تحميل الصفحة بعد 1 ثانية مثلاً
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(
                            result.message || "حدث خطأ أثناء تحديث المهمة",
                        );
                    }
                })
                .catch((error) => {
                    // إذا كان الخطأ برمز 422 => عرض الرسالة التفصيلية من السيرفر
                    if (error.code === 422) {
                        const msg =
                            error.data && error.data.message
                                ? error.data.message
                                : "خطأ في البيانات المدخلة (422)";
                        toastr.error(msg);
                    } else {
                        // أي خطأ آخر (500، 403، إلخ)
                        console.error("Error updating task:", error);
                        toastr.error("حدث خطأ غير متوقع أثناء تحديث المهمة");
                    }
                });
        });
    }
});

$(document).on("click", ".delete-attachment", function (e) {
    e.preventDefault();
    const attachmentId = $(this).data("attachment-id");
    const $btn = $(this);
    // الحصول على معرف المهمة من العنصر الأب (attachment-item)
    const taskId = $btn.closest(".attachment-item").data("task-id");
    Swal.fire({
        title: "هل أنت متأكد من عملية الحذف؟",
        text: "لا يمكن التراجع عن هذا الإجراء!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "تأكيد",
        cancelButtonText: "إلغاء",
        customClass: {
            popup: "custom-popup",
            title: "custom-title",
            text: "custom-text",
            confirmButton: "btn btn-success custom-confirm",
            cancelButton: "btn btn-danger custom-cancel",
        },
        buttonsStyling: false,
        reverseButtons: false,
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(
                `/projects/${window.projectId}/sectors/${window.sectorId}/tasks/${taskId}/attachments/${attachmentId}`,
                {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                },
            )
                .then((response) => response.json())
                .then((result) => {
                    if (result.success) {
                        toastr.success(result.message);
                        $btn.closest(".attachment-item").remove();
                    } else {
                        toastr.error(
                            result.message || "حدث خطأ أثناء حذف المرفق",
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error deleting attachment:", error);
                    toastr.error("حدث خطأ أثناء حذف المرفق");
                });
        }
    });
});

const prioritySelect = $("#update_priority");
if (prioritySelect.length) {
    prioritySelect.select2({
        dropdownParent: $("#showTaskOffcanvas"),
        minimumResultsForSearch: Infinity,
        templateResult: formatPriorityOption,
        templateSelection: formatPriorityOption,
        data: [
            { id: "high", text: "عالية" },
            { id: "medium", text: "متوسطة" },
            { id: "low", text: "منخفضة" },
        ],
        escapeMarkup: function (markup) {
            return markup;
        },
    });
}

// تصدير الدوال المطلوبة ضمن كائن مشترك
window.taskModalFunctions = {
    initializeSharedVariables,
    getPriorityDetails,
    updateAssigneesDisplay,
    updateTaskMetadata,
    updateAttachmentsDisplay,
    initializeSelects,
    formatPriorityOption,
    formatEmployeeOption,
    updateEditForm,
    updateAttachments,
    loadTaskComments,
    updateComment,
    deleteComment,
};

//*******  الدوال الخاصة بالتحديد في الجدول الرئيسي  والجداول الفرعية  *******//

// تحديث معالج حدث "تحديد الكل"
$("#select-all").on("change", function () {
    const checked = $(this).prop("checked");

    // تحديث حالة مربعات الاختيار في الجدول الرئيسي
    $(".module-select-checkbox").prop("checked", checked);

    // التعامل مع الصفوف في الجدول الرئيسي
    window.table.rows().every(function () {
        const row = this;
        const $tr = $(this.node());
        const data = row.data();
        const moduleId = data.id;

        if (checked) {
            // إذا كان الصف غير موسع، قم بتوسيعه
            if (!row.child.isShown() && data && data.tasks) {
                // إنشاء الجدول الفرعي
                const { html, tableId } = createTasksTable(
                    data.tasks,
                    moduleId,
                );
                row.child(html).show();
                $tr.addClass("shown");
                $tr.find(".collapse-indicator").addClass("rotate-90");

                // تهيئة DataTable للجدول الفرعي
                $(`#${tableId}`).DataTable({
                    paging: true,
                    searching: true,
                    lengthChange: true,
                    pageLength: 10,
                    language: {
                        url: "/static/i18n/ar.json",
                    },
                    drawCallback: function () {
                        // تفعيل tooltips وتحديث ألوان حقول الحالة
                        $(`#${tableId} [data-bs-toggle="tooltip"]`).tooltip();
                        $(`#${tableId} .status-select`).each(function () {
                            updateSelectColor($(this));
                        });
                    },
                    initComplete: function () {
                        // تحديد جميع المهام في الجدول الفرعي الجديد
                        setTimeout(() => {
                            $(
                                `.child-select-checkbox[data-module-id="${moduleId}"]`,
                            ).prop("checked", checked);
                            $(
                                `.child-select-all[data-module-id="${moduleId}"]`,
                            ).prop("checked", checked);
                        }, 100);
                    },
                });
            } else if (row.child.isShown()) {
                // إذا كان الجدول الفرعي مرئياً بالفعل، تحديث حالة الاختيار فقط
                setTimeout(() => {
                    $(
                        `.child-select-checkbox[data-module-id="${moduleId}"]`,
                    ).prop("checked", checked);
                    $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
                        "checked",
                        checked,
                    );
                }, 100);
            }
        } else {
            // إلغاء تحديد جميع المهام في الجداول الفرعية
            $(`.child-select-checkbox[data-module-id="${moduleId}"]`).prop(
                "checked",
                false,
            );
            $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
                "checked",
                false,
            );
        }
    });

    // تحديث حالة جميع مربعات الاختيار في الجداول الفرعية المفتوحة
    $(".child-select-checkbox").prop("checked", checked);
    $(".child-select-all").prop("checked", checked);
});

// تحديث معالج تحديد الوحدة
$(document).on("change", ".module-select-checkbox", function () {
    const moduleId = $(this).data("module-id");
    const isChecked = $(this).prop("checked");
    const $moduleRow = $(this).closest("tr");
    const row = window.table.row($moduleRow);

    // تحديث مربعات الاختيار المرتبطة
    $(`.child-select-checkbox[data-module-id="${moduleId}"]`).prop(
        "checked",
        isChecked,
    );
    $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
        "checked",
        isChecked,
    );

    if (isChecked && !row.child.isShown()) {
        const data = row.data();
        if (data && data.tasks) {
            // إنشاء وعرض الجدول الفرعي
            const { html, tableId } = createTasksTable(data.tasks, data.id);
            row.child(html).show();
            $moduleRow.addClass("shown");
            $moduleRow.find(".collapse-indicator").addClass("rotate-90");

            // تهيئة DataTable للجدول الفرعي
            $(`#${tableId}`).DataTable({
                paging: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                language: {
                    url: "/static/i18n/ar.json",
                },
                drawCallback: function () {
                    $(`#${tableId} [data-bs-toggle="tooltip"]`).tooltip();
                },
            });
        }
    }

    // تحديث حالة "تحديد الكل" الرئيسي
    updateMainSelectAllState();
});

// دالة مساعدة لتحديث حالة "تحديد الكل" الرئيسي
function updateMainSelectAllState() {
    const totalModules = $(".module-select-checkbox").length;
    const selectedModules = $(".module-select-checkbox:checked").length;
    $("#select-all").prop("checked", totalModules === selectedModules);
}

// تحديث معالج تحديد الكل للجدول الفرعي
$(document).on("change", ".child-select-all", function () {
    const moduleId = $(this).data("module-id");
    const isChecked = $(this).prop("checked");

    // تحديث مربعات الاختيار في الجدول الفرعي
    $(`.child-select-checkbox[data-module-id="${moduleId}"]`).prop(
        "checked",
        isChecked,
    );

    // تحديث مربع اختيار الوحدة الرئيسي
    $(`.module-select-checkbox[data-module-id="${moduleId}"]`).prop(
        "checked",
        isChecked,
    );

    // تحديث حالة "تحديد الكل" الرئيسي
    updateMainSelectAllState();
});

// تحديث معالج تحديد المهام الفردية
$(document).on("change", ".child-select-checkbox", function () {
    const moduleId = $(this).data("module-id");

    // التحقق من حالة تحديد جميع المهام في الوحدة
    const totalTasks = $(
        `.child-select-checkbox[data-module-id="${moduleId}"]`,
    ).length;
    const selectedTasks = $(
        `.child-select-checkbox[data-module-id="${moduleId}"]:checked`,
    ).length;

    // تحديث مربع "تحديد الكل" للجدول الفرعي والوحدة
    $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
        "checked",
        totalTasks === selectedTasks,
    );
    $(`.module-select-checkbox[data-module-id="${moduleId}"]`).prop(
        "checked",
        totalTasks === selectedTasks,
    );

    // تحديث حالة "تحديد الكل" الرئيسي
    updateMainSelectAllState();
});

// تحديث معالج تحديد الوحدة
$(document).on("change", ".module-select-checkbox", function () {
    const moduleId = $(this).data("module-id");
    const isChecked = $(this).prop("checked");
    const $moduleRow = $(this).closest("tr");
    const row = window.table.row($moduleRow);

    if (isChecked) {
        // إذا تم تحديد الوحدة، نعرض الجدول الفرعي ونحدد جميع المهام
        if (!row.child.isShown()) {
            const data = row.data();
            if (data && data.tasks) {
                // إنشاء وعرض الجدول الفرعي
                const { html, tableId } = createTasksTable(data.tasks, data.id);
                row.child(html).show();
                $moduleRow.addClass("shown");
                $moduleRow.find(".collapse-indicator").addClass("rotate-90");

                // تهيئة DataTable للجدول الفرعي
                $(`#${tableId}`).DataTable({
                    paging: true,
                    searching: true,
                    lengthChange: true,
                    pageLength: 10,
                    language: {
                        url: "/static/i18n/ar.json",
                    },
                    drawCallback: function () {
                        // تفعيل tooltips
                        $(`#${tableId} [data-bs-toggle="tooltip"]`).tooltip();

                        // تحديث لون حقول select الحالة
                        $(`#${tableId} .status-select`).each(function () {
                            updateSelectColor($(this));
                        });
                    },
                    initComplete: function () {
                        // تحديد جميع المهام في الجدول الفرعي
                        $(
                            `.child-select-checkbox[data-module-id="${moduleId}"]`,
                        ).prop("checked", true);
                        $(
                            `.child-select-all[data-module-id="${moduleId}"]`,
                        ).prop("checked", true);
                    },
                });
            }
        } else {
            // إذا كان الجدول الفرعي مرئياً بالفعل، نحدد فقط جميع المهام
            $(`.child-select-checkbox[data-module-id="${moduleId}"]`).prop(
                "checked",
                true,
            );
            $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
                "checked",
                true,
            );
        }
    } else {
        // إذا تم إلغاء تحديد الوحدة، نلغي تحديد جميع المهام دون إخفاء الجدول الفرعي
        $(`.child-select-checkbox[data-module-id="${moduleId}"]`).prop(
            "checked",
            false,
        );
        $(`.child-select-all[data-module-id="${moduleId}"]`).prop(
            "checked",
            false,
        );
    }

    // تحديث حالة مربع "تحديد الكل" الرئيسي
    updateMainSelectAllState();
});

//*******  الدوال الخاصة بالتحديد في الجدول الرئيسي  والجداول الفرعية  *******//
// دالة الحذف الفردي الخاصة بالمهام في جدول
function confirmDelete(taskId) {
    Swal.fire({
        title: "هل أنت متأكد من عملية الحذف؟",
        text: "لا يمكن التراجع عن هذا الإجراء!",
        icon: "warning",
        showCancelButton: true,
        showConfirmButton: true,
        buttonsStyling: false,
        customClass: {
            popup: "custom-popup",
            title: "custom-title",
            text: "custom-text",
            confirmButton: "btn btn-success custom-confirm",
            cancelButton: "btn btn-danger custom-cancel",
        },
        confirmButtonText: "تأكيد",
        cancelButtonText: "إلغاء",
        reverseButtons: false,
    }).then((result) => {
        if (result.isConfirmed) {
            // إرسال طلب حذف المهمة بشكل غير متزامن
            $.ajax({
                url: `/projects/{{ $project->id }}/sectors/{{ $sector->id }}/tasks/${taskId}`,
                type: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                        "content",
                    ),
                },
                success: function (response) {
                    if (response.success) {
                        // إعادة تحميل جدول المهام
                        $("#tasksTable").DataTable().ajax.reload();

                        // عرض رسالة النجاح باستخدام toastr
                        toastr.success(response.message, {
                            positionClass: "toast-top-left",
                            closeButton: true,
                            progressBar: true,
                            timeOut: 3000,
                        });
                    }
                },
                error: function (xhr) {
                    // عرض رسالة الخطأ باستخدام toastr
                    toastr.error("حدث خطأ أثناء حذف المهمة", {
                        positionClass: "toast-top-left",
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000,
                    });
                },
            });
        }
    });
}

//*******  الفلاتر في الجدول الرئيسي  *******//
// فلترة الأولوية (الأزرار)
$(".priority-filter-btn").on("click", function (e) {
    e.preventDefault();
    $(".priority-filter-btn").removeClass("active");
    $(this).addClass("active");
    table.ajax.reload();
});

// فلترة المرحلة
$(".dropdown-menu a[data-phase-filter]").on("click", function (e) {
    e.preventDefault();
    var phase = $(this).data("phase-filter");
    // تحديث النص في زر المرحلة وتعيين القيمة في data-phase
    var $toggle = $(this).closest(".dropdown").find(".phase-filter-btn");
    $toggle.text($(this).text());
    $toggle.data("phase", phase);
    table.ajax.reload();
});

// فلترة الوحدة
$(".dropdown-menu a[data-module-filter]").on("click", function (e) {
    e.preventDefault();
    var module = $(this).data("module-filter");
    var $toggle = $(this).closest(".dropdown").find(".module-filter-btn");
    $toggle.text($(this).text());
    $toggle.data("module", module);
    table.ajax.reload();
});

// فلترة الحالة
$(".dropdown-menu a[data-status-filter]").on("click", function (e) {
    e.preventDefault();
    var status = $(this).data("status-filter");
    var $toggle = $(this).closest(".dropdown").find(".status-filter-btn");
    $toggle.text($(this).text());
    $toggle.data("status", status);
    table.ajax.reload();
});

// فلترة الترتيب
$(".dropdown-menu a[data-sort-by]").on("click", function (e) {
    e.preventDefault();
    var sortBy = $(this).data("sort-by");
    var $toggle = $(this).closest(".dropdown").find(".sort-filter-btn");
    $toggle.text("ترتيب حسب: " + $(this).text());
    $toggle.data("sort-by", sortBy);
    table.ajax.reload();
});

//*******  الفلاتر في الجدول الرئيسي  *******//
