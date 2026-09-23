/**
 * Primary Phone Management for Customer Details
 * إدارة الأرقام الافتراضية في تفاصيل العميل
 */
$(document).ready(function () {

    $(document).on('click', '.set-primary-phone-btn', function () {
        const $button = $(this);
        const phoneId = parseInt($button.data('phone-id')) || parseInt($button.attr('data-phone-id'));
        const phoneNumber = $button.data('phone-number') || $button.attr('data-phone-number');
        const customerId = window.location.pathname.split('/')[2];

        if (!phoneId || phoneId === 0 || isNaN(phoneId)) {
            showErrorMessage('معرف رقم الهاتف غير صحيح');
            return;
        }

        if (!phoneNumber) {
            showErrorMessage('رقم الهاتف غير محدد');
            return;
        }

        if (!customerId) {
            showErrorMessage('معرف العميل غير صحيح');
            return;
        }

        if ($button.hasClass('loading')) {
            return;
        }

        Swal.fire({
            title: 'تعيين رقم افتراضي',
            text: `هل تريد تعيين رقم ${phoneNumber} كرقم افتراضي؟`,
            icon: 'question',
            showCancelButton: true,
            showConfirmButton: true,
            showDenyButton: false,
            buttonsStyling: false,
            customClass: {
                popup: 'custom-popup',
                title: 'custom-title',
                text: 'custom-text',
                confirmButton: 'btn btn-success custom-confirm',
                cancelButton: 'btn btn-danger custom-cancel'
            },
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، تعيين',
            cancelButtonText: 'إلغاء',
            buttonsStyling: false,
            reverseButtons: false,
        }).then((result) => {
            if (result.isConfirmed) {
                setPrimaryPhone(customerId, phoneId, phoneNumber, $button);
            }
        });
    });

    function setPrimaryPhone(customerId, phoneId, phoneNumber, $button) {
        if (!phoneId || phoneId === 0 || isNaN(phoneId)) {
            showErrorMessage('معرف رقم الهاتف غير صحيح');
            return;
        }

        $button.addClass('loading').prop('disabled', true);

        $.ajax({
            url: window.routes['customers.set-primary-phone'],
            method: 'POST',
            data: {
                phone_id: parseInt(phoneId),
                _token: window.csrfToken || $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('تم تعيين الرقم كافتراضي بنجاح');
                    }

                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);

                } else {
                    $button.removeClass('loading').prop('disabled', false);
                    showErrorMessage(response.message || 'حدث خطأ غير متوقع');
                }
            },
            error: function (xhr) {
                $button.removeClass('loading').prop('disabled', false);

                let errorMessage = 'حدث خطأ أثناء تعيين الرقم الافتراضي';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
                } else if (xhr.status === 404) {
                    errorMessage = 'العميل أو رقم الهاتف غير موجود';
                }

                showErrorMessage(errorMessage);
            }
        });
    }

    function updatePhoneTableDisplay(newPrimaryPhoneId, phoneNumber) {
        $('tbody tr').removeClass('table-warning');
        $('.ti-star-filled.text-warning').remove();
        $('.badge.bg-warning').replaceWith('<span class="badge bg-info" style="font-size: 0.65rem;">إضافي</span>');
        $('.text-success').parent().html(`
            <button type="button" 
                    class="btn btn-warning btn-xs set-primary-phone-btn" 
                    data-phone-id=""
                    data-phone-number=""
                    title="تعيين كافتراضي"
                    style="font-size: 0.7rem; padding: 2px 6px;">
                <i class="ti ti-star"></i>
            </button>
        `);

        const $newPrimaryRow = $(`.set-primary-phone-btn[data-phone-id="${newPrimaryPhoneId}"]`).closest('tr');

        $newPrimaryRow.addClass('table-warning animate__animated animate__pulse');

        $newPrimaryRow.find('td:nth-child(2)').html(`
            <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">
               افتراضي
            </span>
        `);

        $newPrimaryRow.find('td:nth-child(3)').html(`
            <span class="text-success" style="font-size: 0.7rem;">
                افتراضي
            </span>
        `);

        $newPrimaryRow.find('td:first-child .fw-medium').after(`
            <i class="ti ti-star-filled text-warning ms-1" title="الرقم الافتراضي"></i>
        `);

        $('.alert-info.primary-phone-info strong').next().text(phoneNumber);

        setTimeout(() => {
            $newPrimaryRow.removeClass('animate__pulse');
        }, 2000);

        // if (typeof toastr !== 'undefined') {
        //     toastr.success('تم تعيين الرقم كافتراضي بنجاح', 'نجح');
        // }
    }

    function showErrorMessage(message) {
        Swal.fire({
            title: 'خطأ!',
            text: message,
            icon: 'error',
            confirmButtonText: 'موافق',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-danger'
            }
        });

        if (typeof toastr !== 'undefined') {
            toastr.error(message, 'خطأ');
        }
    }

    function initializePhoneTableEffects() {
        $('.table-warning').addClass('animate__animated animate__fadeIn');

        $('.set-primary-phone-btn').hover(
            function () {
                $(this).addClass('animate__animated animate__heartBeat');
            },
            function () {
                $(this).removeClass('animate__animated animate__heartBeat');
            }
        );

        setTimeout(() => {
            $('.animate__fadeIn').removeClass('animate__animated animate__fadeIn');
        }, 1000);
    }

    initializePhoneTableEffects();

    $(document).on('click', '.set-primary-phone-btn', function () {
        if (!$(this).hasClass('loading')) {
            $(this).addClass('animate__animated animate__pulse');
            setTimeout(() => {
                $(this).removeClass('animate__animated animate__pulse');
            }, 1000);
        }
    });
});