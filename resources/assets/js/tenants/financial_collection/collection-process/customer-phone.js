'use strict';

/**
 * Customer Phone Manager
 * معالج إدارة أرقام هواتف العملاء
 */
class CustomerPhoneManager {
    constructor() {
        this.routes = window.routes || {};
        this.csrfToken = window.csrfToken;
        this.currentCustomer = window.currentCustomer;
        this.init();
    }

    /**
     * تهيئة معالج أرقام الهواتف
     */
    init() {
        $(document).ready(() => {
            this.bindEventHandlers();
            // console.log('Customer Phone Manager initialized');
        });
    }

    /**
     * ربط معالجات الأحداث
     */
    bindEventHandlers() {
        // حفظ رقم الهاتف الجديد
        $(document).on('click', '#savePhoneBtn', () => {
            this.savePhone();
        });

        // تعيين رقم هاتف افتراضي
        $(document).on('click', '.set-primary-phone-btn', (e) => {
            const btn = $(e.currentTarget);
            const phoneId = btn.data('phone-id');
            const phoneNumber = btn.data('phone-number');
            this.setPrimaryPhone(phoneId, phoneNumber);
        });

        // التحقق من صحة إدخال رقم الهاتف
        $(document).on('input', '#phone_number', (e) => {
            const input = $(e.currentTarget);
            input.val(input.val().replace(/[^0-9]/g, ''));
        });
    }

    /**
     * عرض مودال إضافة رقم هاتف
     */
    showAddPhoneModal(customerId) {
        $('#phone_customer_id').val(customerId);
        $('#addPhoneForm')[0].reset();
        
        const modalElement = document.getElementById('addPhoneModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    /**
     * حفظ رقم الهاتف الجديد
     */
    savePhone() {
        const form = $('#addPhoneForm');
        const customerId = $('#phone_customer_id').val();
        const btn = $('#savePhoneBtn');
        const spinner = btn.find('.spinner-border');
        
        // التحقق من صحة النموذج
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        // تعطيل الزر وإظهار المؤشر
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        const formData = new FormData(form[0]);
        const url = this.routes.addPhone.replace('CUSTOMER_ID', customerId);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // إغلاق المودال
                    const modalElement = document.getElementById('addPhoneModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // إعادة تحميل الصفحة لعرض الرقم الجديد
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: (xhr) => {
                let errorMessage = 'حدث خطأ أثناء إضافة رقم الهاتف';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: () => {
                // إعادة تفعيل الزر وإخفاء المؤشر
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    }

    /**
     * تعيين رقم هاتف افتراضي
     */
    setPrimaryPhone(phoneId, phoneNumber) {
        const customerId = this.currentCustomer.id;
        
        Swal.fire({
            title: 'تعيين الرقم الافتراضي',
            text: `هل تريد تعيين الرقم ${phoneNumber} كرقم افتراضي؟`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم',
            cancelButtonText: 'لا',
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const url = this.routes.setPrimaryPhone.replace('CUSTOMER_ID', customerId);
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        phone_id: phoneId,
                        _token: this.csrfToken
                    },
                    success: (response) => {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: (xhr) => {
                        let errorMessage = 'حدث خطأ أثناء تعيين الرقم الافتراضي';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    }
                });
            }
        });
    }
}

// تهيئة معالج أرقام الهواتف عند تحميل الصفحة
$(document).ready(function () {
    if (window.routes && window.csrfToken && window.currentCustomer) {
        window.CustomerPhoneManager = new CustomerPhoneManager();
        
        // تعريف الدالة العامة لعرض المودال
        window.showAddPhoneModal = function(customerId) {
            window.CustomerPhoneManager.showAddPhoneModal(customerId);
        };
    }
});