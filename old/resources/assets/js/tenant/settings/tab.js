$(document).ready(function () {
    // التحقق مما إذا كان هناك hash في الرابط عند التحميل
    if (window.location.hash) {
        var hash = window.location.hash;
        // تنشيط علامة التبويب بناءً على الـ hash
        $('.nav-tabs a[href="' + hash + '"]').tab('show');
    }

    // عند تغيير التبويب، قم بتحديث الـ URL بالـ hash
    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        var targetHash = e.target.hash; // الحصول على الـ hash الجديد
        if (targetHash) {
            history.replaceState(null, null, targetHash); // تحديث الـ URL دون إعادة تحميل الصفحة
        }
    });
});
