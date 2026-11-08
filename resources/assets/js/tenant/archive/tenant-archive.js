"use strict";

$(function () {
    $(document).on("click", ".js-restore-btn", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        if (!url) return;

        if (typeof window.confirmAction === "function") {
            window.confirmAction({
                title: "هل أنت متأكد؟",
                text: "سيتم استرجاع هذا السجل من الأرشيف",
                confirmButtonText: "نعم، استرجع",
                confirmButtonClass: "btn btn-primary",
                icon: "ti ti-refresh text-primary",
                onConfirm: function () {
                    submitArchiveForm("PATCH", url);
                },
            });
        } else {
            submitArchiveForm("PATCH", url);
        }
    });

    $(document).on("click", ".js-force-delete-btn", function (e) {
        e.preventDefault();
        const url = $(this).data("url");
        if (!url) return;

        window.confirmRecordDelete({
            url,
            table: this.closest("table"),
            title: "تحذير!",
            text: "سيتم حذف هذا السجل نهائياً ولا يمكن استرجاعه. هل أنت متأكد؟",
            confirmText: "تأكيد الحذف نهائياً",
        });
    });
});

function submitArchiveForm(method, url) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = url;

    const csrfToken = document.createElement("input");
    csrfToken.type = "hidden";
    csrfToken.name = "_token";
    csrfToken.value = $('meta[name="csrf-token"]').attr("content") || window.csrfToken || "";
    form.appendChild(csrfToken);

    if (method !== "POST") {
        const methodField = document.createElement("input");
        methodField.type = "hidden";
        methodField.name = "_method";
        methodField.value = method;
        form.appendChild(methodField);
    }

    document.body.appendChild(form);
    form.submit();
}
