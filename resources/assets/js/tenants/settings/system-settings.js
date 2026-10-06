function updatePreview(imgId, fileInputId, defaultSrc) {
    const fileInput = document.getElementById(fileInputId);
    const imgElement = document.getElementById(imgId);

    if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            imgElement.src = e.target.result;
        };
        reader.readAsDataURL(fileInput.files[0]);
    } else {
        imgElement.src = defaultSrc;
    }
}

document
    .getElementById("uploadHorizontalHeader")
    .addEventListener("change", function () {
        updatePreview(
            "uploadedHorizontalHeader",
            "uploadHorizontalHeader",
            "{{ $settings->horizontal_header_image ? asset('storage/' . $settings->horizontal_header_image) : asset('static/brand/logos/logo.svg') }}",
        );
        updatePreview(
            "previewHorizontalHeader",
            "uploadHorizontalHeader",
            "{{ $settings->horizontal_header_image ? asset('storage/' . $settings->horizontal_header_image) : asset('static/brand/logos/logo.svg') }}",
        );
    });

document
    .getElementById("uploadHorizontalFooter")
    .addEventListener("change", function () {
        updatePreview(
            "uploadedHorizontalFooter",
            "uploadHorizontalFooter",
            "{{ $settings->horizontal_footer_image ? asset('storage/' . $settings->horizontal_footer_image) : asset('static/brand/logos/logo.svg') }}",
        );
        updatePreview(
            "previewHorizontalFooter",
            "uploadHorizontalFooter",
            "{{ $settings->horizontal_footer_image ? asset('storage/' . $settings->horizontal_footer_image) : asset('static/brand/logos/logo.svg') }}",
        );
    });

document
    .getElementById("uploadSignature")
    .addEventListener("change", function () {
        updatePreview(
            "uploadedSignature",
            "uploadSignature",
            "{{ $settings->signature ? asset('storage/' . $settings->signature) : asset('static/brand/logos/logo.svg') }}",
        );
        updatePreview(
            "previewSignature",
            "uploadSignature",
            "{{ $settings->signature ? asset('storage/' . $settings->signature) : asset('static/brand/logos/logo.svg') }}",
        );
    });

document.addEventListener("DOMContentLoaded", function () {
    $(".select2").select2({
        placeholder: function () {
            return $(this).data("placeholder");
        },
        allowClear: true,
        width: "100%",
        language: "ar",
        dir: "rtl",
    });

    // تعريف دالة لتحميل الصورة
    function readURL(input, imgElementId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById(imgElementId).src = e.target.result;
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    // قائمة الحقول والعناصر
    var fields = [
        {
            inputId: "uploadHorizontalHeader",
            imgId: "uploadedHorizontalHeader",
        },
        {
            inputId: "uploadVerticalHeader",
            imgId: "uploadedVerticalHeader",
        },
        {
            inputId: "uploadHorizontalFooter",
            imgId: "uploadedHorizontalFooter",
        },
        {
            inputId: "uploadVerticalFooter",
            imgId: "uploadedVerticalFooter",
        },
    ];

    // إضافة مستمعات الحدث لكل حقل ملف
    fields.forEach(function (field) {
        var inputElement = document.getElementById(field.inputId);
        if (inputElement) {
            inputElement.addEventListener("change", function () {
                readURL(this, field.imgId);
            });
        }
    });
});
