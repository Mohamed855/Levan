# سجل إغلاق المرحلة 6 — Tenant

**التاريخ:** 7 أكتوبر 2026  
**الحالة:** مكتملة تقنياً ومهيأة للانتقال إلى مراجعة خطة المرحلة 7.  
**المرجع:** `docs/system-unification-implementation-plan-ar.md`، وسجل الإدارات الجديدة `phase-1-5-new-tenant-modules-register-2026-10-07.md`.

## الوحدات المغلقة

| الوحدة | دليل الإغلاق |
|---|---|
| الهوية، الملف الشخصي، المستخدمون، الفرق، الأدوار | FormRequests والصلاحيات الإنجليزية واختبارات authentication/user-management. Branches خارج المنتج ولا توجد ضمن نطاق Tenant. |
| الإعدادات، الإشعارات، activity logs، archive | عقود FormRequest والاستجابات، وتصحيح صلاحية/حفظ أسباب طلب المستندات. |
| الشركات، العقود، المحافظ، المهام | CRUD وarchive وبيانات DataTables مغطاة باختبارات Tenant وCompany/Subscription. |
| الإسنادات، العقارات، المزادات، الأرشيف | تحقق العلاقة Assignment→Property→Auction، Wizard validation، حالة النشاط، دورة الملفات، والحذف الآمن. |
| العملاء وعملياتهم الجماعية | Forms، عمليات الطلبات الخلفية، ownership لمهام import/bulk-update، واختبارات subresources. |
| التحصيل والأهداف والاستيراد والمطابقة | Requests مخصصة، حراسة tenant، progress/cache بمفتاح المالك، واختبارات collection process. |
| المراسلات والقوالب والخطابات والسجلات | Requests وصلاحيات tenant وفحوص communication/company letters. |
| الاشتراكات والفواتير والتراخيص والتذاكر والإصدارات | عقود checkout/receipt/renewal، عزل tenant، واختبارات subscriptions/bills/tickets. |
| الدومينات وCPanel والمدفوعات/التحويلات | اختبارات subscription/domain guard ومسارات tenant النشطة. |
| التقارير ولوحات المتابعة | اختبارات dashboard، metrics، reports، والاستجابات JSON. |

## قبول الإدارات الجديدة

- كل mutation يستعمل method HTTP صريحاً؛ restore هو `PATCH` فقط.
- الـWizard يستعمل الـFormRequest الفعلي، ويرفض ربط عقار بإسناد غير مطابق.
- الحذف الجماعي للكيانات الثلاثة محمي بصلاحية الحذف الخاصة بها.
- الحذف الناعم/الاستعادة يعيدان JSON عند AJAX وflash redirect للطلبات العادية.
- لا يحذف الإسناد إن كان له عقار أو مزاد مرتبط؛ الحذف النهائي للمزاد ينظف الغلاف والمرفقات من التخزين.
- تعديل المزاد يدير العقارات التابعة للإسناد والمرفقات الجديدة بالعقد المشترك.

## أدلة التحقق

- `php artisan test`: **117 passed, 2,579 assertions**.
- `npm run build`: **نجح**؛ تحذيرات خطوط/أصول ثابتة لا تمنع البناء.
- `git diff --check`: يجب أن يمر قبل الـcommit.

## ما لا يُنفذ في هذه المرحلة

- التدقيق الأفقي النهائي لاستجابات DataTables وترتيب أعمدة الحالة هو Stage 7.
- حذف الملفات/Imports القديمة غير المستعملة هو Stage 8 بعد مراجعة الاستهلاك.
- smoke checks المتصفحية والإصدار الإنتاجي هي Stage 9.
