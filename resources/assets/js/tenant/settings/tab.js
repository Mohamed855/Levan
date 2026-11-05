$(document).ready(function () {
    // 1. تفعيل التبويب بناءً على الـ Hash في الرابط عند التحميل
    if (window.location.hash) {
        var hash = window.location.hash;
        var tabTrigger = document.querySelector(
            '.nav-tabs [href="' + hash + '"], ' +
            '.nav-tabs [data-bs-target="' + hash + '"], ' +
            '[data-bs-toggle="tab"][data-bs-target="' + hash + '"], ' +
            '[data-bs-toggle="tab"][href="' + hash + '"]'
        );

        if (tabTrigger) {
            if (window.bootstrap && window.bootstrap.Tab) {
                var tab = window.bootstrap.Tab.getOrCreateInstance(tabTrigger);
                tab.show();
            } else if (window.jQuery) {
                $(tabTrigger).tab('show');
            }
        }
    }

    // 2. تحديث الـ URL بالـ Hash الجديد عند تغيير التبويب
    $(document).on('shown.bs.tab', '[data-bs-toggle="tab"]', function (e) {
        var targetHash = $(e.target).data('bs-target') || e.target.hash;
        if (targetHash && typeof targetHash === 'string' && targetHash.startsWith('#')) {
            history.replaceState(null, null, targetHash);
        }
    });

    // 3. تبديل مظهر الأزرار التفاعلية (btn-primary / btn-outline-primary) في مجموعات الأزرار
    $(document).on('show.bs.tab', '.btn-group [data-bs-toggle="tab"], .btn-group-vertical [data-bs-toggle="tab"]', function (e) {
        var $group = $(e.target).closest('.btn-group, .btn-group-vertical');
        $group.find('[data-bs-toggle="tab"]').each(function () {
            var $btn = $(this);
            if ($btn.hasClass('btn-primary')) {
                $btn.removeClass('btn-primary').addClass('btn-outline-primary');
            }
        });
        $(e.target).removeClass('btn-outline-primary').addClass('btn-primary');
    });
});
