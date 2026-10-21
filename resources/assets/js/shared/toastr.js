import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// إعداد الخيارات الافتراضية لـ Toastr
toastr.options = {
    "closeButton": false,
    "progressBar": true,
    "positionClass": "toast-top-left", // تغيير الموقع إلى اليسار العلوي
    "timeOut": "5000",
};

['success', 'info', 'warning', 'error'].forEach((type) => {
    const showToast = toastr[type].bind(toastr);

    toastr[type] = (...args) => {
        toastr.clear();

        return showToast(...args);
    };
});

// جعل Toastr متاحًا عالميًا
window.toastr = toastr;
