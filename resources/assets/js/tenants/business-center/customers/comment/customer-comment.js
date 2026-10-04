'use strict';

import Tribute from 'tributejs';
import 'tributejs/dist/tribute.css';

let tribute;
let currentCustomerId;

/**
 * تهيئة Tribute.js للمنشنات
 */
function initializeTribute() {
    if (!window.routes || !window.routes['customers.comments.mentionable-users']) {
        // console.error('Mentionable users route not defined.');
        toastr.error('لم يتم تهيئة روابط المنشنات بشكل صحيح.');
        return;
    }

    fetch(window.routes['customers.comments.mentionable-users'])
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.users) {
                const users = data.users.map(user => ({
                    key: user.name,
                    value: user.name,
                    label: user.name
                }));

                tribute = new Tribute({
                    values: users,
                    lookup: 'key',
                    fillAttr: 'value',
                    selectTemplate: function (item) {
                        return '@' + item.original.value;
                    },
                    menuItemTemplate: function (item) {
                        return `<div class="tribute-item">${item.original.label}</div>`;
                    },
                    noMatchTemplate: function () {
                        return '<div class="text-muted p-2">لم يتم العثور على مستخدم</div>';
                    },
                    menuItemLimit: 10,
                    containerClass: 'tribute-container',
                });

                const textarea = document.getElementById('comment_text');
                if (textarea) {
                    tribute.attach(textarea);
                    // console.log('✅ Tribute.js initialized successfully');
                }
            } else {
                // console.warn('No users found for mentions.');
                toastr.warning('لا توجد بيانات مستخدمين للمنشن.');
            }
        })
        .catch(error => {
            console.error('Error fetching mentionable users:', error);
            toastr.error('حدث خطأ أثناء تحميل قائمة المستخدمين.');
        });
}

/**
 * تحديث قائمة التعليقات
 */
function refreshCommentsList() {
    if (!window.routes || !window.routes['customers.comments.list']) {
        // console.error('Comments list route not defined.');
        toastr.error('لم يتم تهيئة رابط تحميل التعليقات.');
        return;
    }

    fetch(window.routes['customers.comments.list'])
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const savedCommentsContainer = document.getElementById('saved-comments');
                if (savedCommentsContainer) {
                    savedCommentsContainer.innerHTML = data.html;
                    attachDeleteEventListeners();
                    console.log('✅ Comments list refreshed');
                }
            } else {
                toastr.error(data.message || 'حدث خطأ في تحديث القائمة');
            }
        })
        .catch(error => {
            // console.error('Error refreshing comments:', error);
            toastr.error('حدث خطأ في تحديث القائمة.');
        });
}

/**
 * إضافة تعليق جديد للـ DOM
 */
function addCommentToDOM(comment) {
    const commentsContainer = document.getElementById('saved-comments');
    if (!commentsContainer) return;

    const noCommentsMsg = document.getElementById('no-comments-message');
    if (noCommentsMsg) {
        noCommentsMsg.remove();
    }

    // بناء HTML المنشنات
    let mentionsHtml = '';
    if (comment.mentions && comment.mentions.length > 0) {
        const mentionBadges = comment.mentions.map(m =>
            `<span class="badge bg-label-info">${m.mentioned_user.name}</span>`
        ).join('');

        mentionsHtml = `
            <div class="mt-3 pt-2 border-top">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <small class="text-muted">
                        <i class="ti ti-users me-1"></i>تمت الإشارة إلى:
                    </small>
                    ${mentionBadges}
                </div>
            </div>
        `;
    }

    const commentHtml = `
        <div class="comment-item border rounded p-3 mb-3 shadow-sm" data-comment-id="${comment.id}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            ${comment.user.name.charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">${comment.user.name}</h6>
                        <small class="text-muted">
                            <i class="ti ti-clock me-1"></i>منذ لحظات
                        </small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-comment-btn" title="حذف التعليق">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
            <div class="comment-content">
                <p class="mb-0">${comment.parsed_comment_text}</p>
            </div>
            ${mentionsHtml}
        </div>
    `;

    commentsContainer.insertAdjacentHTML('afterbegin', commentHtml);

    const newCommentElement = commentsContainer.firstElementChild;
    const deleteBtn = newCommentElement.querySelector('.delete-comment-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', handleDeleteComment);
    }

    // console.log('✅ Comment added to DOM');
}

/**
 * ربط أحداث الحذف
 */
function attachDeleteEventListeners() {
    document.querySelectorAll('.delete-comment-btn').forEach(button => {
        button.removeEventListener('click', handleDeleteComment);
        button.addEventListener('click', handleDeleteComment);
    });
    // console.log('✅ Delete listeners attached');
}

/**
 * معالج حذف التعليق
 */
function handleDeleteComment(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const commentElement = button.closest('.comment-item');
    const commentId = commentElement.getAttribute('data-comment-id');

    if (!commentId) {
        toastr.error('معرف التعليق غير صحيح.');
        return;
    }

    const deleteUrl = window.routes['customers.comments.destroy'].replace('COMMENT_ID', commentId);

    Swal.fire({
        title: 'هل أنت متأكد من الحذف؟',
        text: "لا يمكن التراجع عن هذا الإجراء!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد الحذف',
        cancelButtonText: 'إلغاء',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message || 'تم حذف التعليق بنجاح.');
                        commentElement.style.transition = 'opacity 0.3s, transform 0.3s';
                        commentElement.style.opacity = '0';
                        commentElement.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            commentElement.remove();
                            checkAndShowNoCommentsMessage();
                        }, 300);
                    } else {
                        toastr.error(data.message || 'حدث خطأ في الحذف.');
                    }
                })
                .catch(error => {
                    // console.error('Error deleting comment:', error);
                    toastr.error('حدث خطأ أثناء حذف التعليق.');
                });
        }
    });
}

/**
 * التحقق من وجود تعليقات
 */
function checkAndShowNoCommentsMessage() {
    const savedComments = document.getElementById('saved-comments');
    if (!savedComments) return;

    const commentElements = savedComments.querySelectorAll('.comment-item');

    if (commentElements.length === 0) {
        const noCommentsHtml = `
            <div class="text-center py-5" id="no-comments-message">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-secondary">
                        <i class="ti ti-message-circle-2" style="font-size: 2rem;"></i>
                    </span>
                </div>
                <h6 class="text-muted mb-1">لا توجد تعليقات</h6>
                <p class="text-muted small mb-0">كن أول من يضيف تعليقاً لهذا العميل</p>
            </div>
        `;
        savedComments.innerHTML = noCommentsHtml;
    }
}

/**
 * التهيئة الرئيسية
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.customerId === 'undefined' || !window.routes) {
        // console.error('Required variables (customerId or routes) not defined.');
        toastr.error('خطأ في تهيئة الصفحة.');
        return;
    }

    currentCustomerId = window.customerId;
    // console.log('✅ Customer Comment JS initialized - Customer ID:', currentCustomerId);
    // console.log('Routes:', window.routes);

    const form = document.getElementById('customerCommentsForm');
    if (!form) {
        // console.error('Customer Comments Form not found!');
        return;
    }

    const textarea = document.getElementById('comment_text');
    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = document.getElementById('save-comment-spinner');

    // تهيئة Tribute
    initializeTribute();

    // معالج إرسال النموذج
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const commentText = formData.get('comment_text');

        if (!commentText || commentText.trim().length < 3) {
            toastr.error('نص التعليق يجب أن يكون 3 أحرف على الأقل.');
            return;
        }

        submitButton.disabled = true;
        spinner.style.display = 'inline-block';

        if (!window.routes['customers.comments.store']) {
            // console.error('Store comment route not defined.');
            toastr.error('خطأ في إعدادات النموذج.');
            submitButton.disabled = false;
            spinner.style.display = 'none';
            return;
        }

        fetch(window.routes['customers.comments.store'], {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                comment_text: commentText
            })
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    toastr.success(data.message || 'تم إضافة التعليق بنجاح.');
                    form.reset();
                    addCommentToDOM(data.comment);
                } else {
                    toastr.error(data.message || 'حدث خطأ في حفظ التعليق.');
                }
            })
            .catch(error => {
                // console.error('Error saving comment:', error);
                toastr.error(error.message || 'حدث خطأ أثناء حفظ التعليق.');
            })
            .finally(() => {
                submitButton.disabled = false;
                spinner.style.display = 'none';
            });
    });

    // ربط أحداث الحذف
    attachDeleteEventListeners();
});
