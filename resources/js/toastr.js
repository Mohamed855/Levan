import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// إعداد الخيارات الافتراضية لـ Toastr
toastr.options = {
    "closeButton": false,
    "progressBar": true,
    "positionClass": "toast-top-left", // تغيير الموقع إلى اليسار العلوي
    "timeOut": "5000",
};

// جعل Toastr متاحًا عالميًا
window.toastr = toastr;
