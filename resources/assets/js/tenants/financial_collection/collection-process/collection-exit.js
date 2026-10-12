"use strict";

/**
 * Collection Exit Handler
 * معالج إنهاء عملية التحصيل
 */
class CollectionExitHandler {
    constructor() {
        this.init();
    }

    /**
     * تهيئة معالج الإنهاء
     */
    init() {
        $(document).ready(() => {
            this.initializeToastr();
            this.bindEventHandlers();
            // console.log('Collection Exit Handler initialized');
        });
    }

    /**
     * تهيئة إشعارات Toastr
     */
    initializeToastr() {
        const toastrMessages = {
            success: window.toastrSuccess || null,
            error: window.toastrError || null,
            info: window.toastrInfo || null,
            warning: window.toastrWarning || null,
        };

        Object.entries(toastrMessages).forEach(([type, message]) => {
            if (message && typeof toastr !== "undefined") {
                toastr[type](message);
            }
        });
    }

    /**
     * ربط معالجات الأحداث
     */
    bindEventHandlers() {
        const exitBtn = document.getElementById("exitCollectionBtn");
        if (exitBtn) {
            exitBtn.addEventListener("click", (e) => {
                e.preventDefault();
                this.showExitConfirmation();
            });
        }
    }

    /**
     * عرض نافذة تأكيد الإنهاء
     */
    showExitConfirmation() {
        if (typeof Swal === "undefined") {
            console.error("SweetAlert2 is not loaded");
            return;
        }

        Swal.fire({
            title: "إنهاء عملية التحصيل",
            text: "هل أنت متأكد من إنهاء عملية التحصيل اليومية؟",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "نعم، إنهاء",
            cancelButtonText: "إلغاء",
            customClass: {
                confirmButton: "btn btn-primary me-2",
                cancelButton: "btn btn-secondary",
            },
            buttonsStyling: false,
        }).then((result) => {
            if (result.isConfirmed) {
                this.exitCollection();
            }
        });
    }

    /**
     * تنفيذ عملية الإنهاء
     */
    exitCollection() {
        fetch(window.routes.exit, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.csrfToken,
            },
            body: JSON.stringify({}),
        })
            .then((response) => {
                if (response.ok) {
                    Swal.fire({
                        title: "تم الإنهاء بنجاح",
                        text: "جاري تحويلك إلى لوحة التحكم...",
                        icon: "success",
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = window.routes.dashboard;
                    });
                } else {
                    throw new Error("فشلت عملية الإنهاء");
                }
            })
            .catch((error) => {
                console.error("Exit error:", error);
                Swal.fire({
                    title: "حدث خطأ",
                    text: "فشلت عملية الإنهاء، يرجى المحاولة مرة أخرى",
                    icon: "error",
                    confirmButtonText: "حسناً",
                    customClass: {
                        confirmButton: "btn btn-primary",
                    },
                    buttonsStyling: false,
                });
            });
    }
}

// تهيئة معالج الإنهاء عند تحميل الصفحة
$(document).ready(function () {
    if (window.routes && window.csrfToken) {
        window.CollectionExitHandler = new CollectionExitHandler();
    }
});
