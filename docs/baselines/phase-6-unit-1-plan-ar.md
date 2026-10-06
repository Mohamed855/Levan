# خطة المرحلة 6 — الوحدة 1: الهوية وإدارة المستخدمين

**النطاق:** Authentication، Profile، Users، Teams، Roles.  
**خارج النطاق:** Branches؛ أُلغي المنتج الخاص بها، ولا تُنشأ routes أو models أو migrations لها في هذه الوحدة.

## ترتيب التنفيذ وبوابات القبول

1. **Authentication**
   - تحويل كل mutation نشط في login/reset/confirm password/2FA إلى FormRequest.
   - توحيد صفحات النماذج النشطة مع `shared/form-validation.js` وFormRequest الهدف.
   - التحقق من tenant context ومن POST-only ومن 422/403 في النجاح والفشل.

   **الحالة: مكتمل — 6 أكتوبر 2026.**
   - استبدلت mutations النشطة في استعادة كلمة المرور، تأكيدها، تأكيد كلمة المرور، وتحدّي 2FA بطلبات `FormRequest` مخصصة.
   - أزيل التحقق المضمن من `ResolveTwoFactorLogin`، وأصبح يستقبل البيانات التي تم التحقق منها فقط. مواءمة Super Admin تمت في التغيير نفسه لأنّه يستخدم الـAction ذاته.
   - صفحات login، forgot/reset password، confirm password، وتحدّي 2FA أصبحت تستخدم `shared/form-validation.js` وعقد الحقول المشترك. endpoint الضيف مقيد بقائمة Actions خاصة بهذه النماذج، ولا يقبل Routes المستأجر الأخرى.
   - أصلح مسار 2FA قراءة خيار «تذكرني» قبل تنظيف session، ويزيل قيم OTP/Recovery غير النشطة عند التبديل بين الطريقتين.
   - الدليل: `TenantAuthenticationContractTest` و`TenantAuthenticationValidationFeatureTest` (7 اختبارات/51 assertion)، واختبارا العقود المتداخلان مع الإدارة (10 اختبارات/122 assertion). نجح `npm run build`؛ وتحذيرات الأصول المعروفة فقط ظلت قائمة.

2. **Profile**
   - توحيد تغيير كلمة المرور وصورة الملف والخلفية؛ لا يغيّر المستخدم غير المصرح له ملف مستخدم آخر.
   - فحص uploads وحدود الحجم والـMIME واستجابة JSON الموحدة.

   **الحالة: مكتمل — 6 أكتوبر 2026.**
   - صور الملف والخلفية وكلمة المرور الحالية لها FormRequests مستقلة وتفويض يمنع المستخدم من تعديل ملف مستخدم آخر بلا `users.edit`.
   - صفحة الملف وكلمة المرور تستخدم عقد التحقق المشترك، واستجابات الرفع تبقى JSON موحدة.
   - الدليل: `TenantProfileUploadFeatureTest` (اختبارا 403 و422، 4 assertions).

3. **Users**
   - مراجعة CRUD، الأرشيف، status toggle، الصلاحيات، وعزل tenant A عن tenant B.
   - إزالة قواعد JavaScript التجارية المكررة وربط النماذج النشطة بالمحرك المشترك.

   **الحالة: مكتمل — 6 أكتوبر 2026.**
   - نماذج الإنشاء والتعديل تستخدم wizard/controls والمحرك المشترك؛ أزيل ملف FormValidation القديم غير المستخدم.
   - تغيير الحالة يستقبل `active|inactive` من toggle الموحد عبر FormRequest مستقل وصلاحية `users.edit`، ولا يقلبها المتحكم ضمنياً.
   - أدوار الإنشاء والتعديل والخدمة مقيدة بالـtenant guard، وتغيير الحالة يتطلب `users.edit`. الدليل: دورة المستخدم والأرشيف في `TenantCrudFeatureTest` (15 assertion)، واختبارا 403 و422 في `TenantUserManagementValidationFeatureTest`.

4. **Teams ثم Roles**
   - مراجعة FormRequests والخدمات والعلاقات والـDataTables وترتيب status/actions.
   - توحيد AJAX role/status contracts واختبارات الصلاحية.

   **الحالة: مكتمل — 6 أكتوبر 2026.**
   - الفرق: مكتملة؛ تستعمل wizard/controls المشتركة، وقواعد العضوية والقائد في FormRequest، وحالة الجدول تمر عبر toggle الموحد وطلب حالة مخصص. الدليل: دورة الفرق في `TenantCrudFeatureTest` (12 assertion).
   - الأدوار: نماذج الإنشاء والتعديل مرتبطة بالمحرك الموحد؛ معرّفات الأدوار والصلاحيات مقيدة بالـtenant guard وبمفاتيح إنجليزية. الدليل: دورة الأدوار في `TenantCrudFeatureTest` (10 assertions).
   - جدول إسناد الدور يعرض الحالة قبل الإجراءات مباشرة، ويستخدم `js-table-status-toggle` فقط؛ أزيلت معالجات الحالة وحركة النص الخاصة بالصفحة.

5. **بوابة الوحدة**
   - لا `Request::validate()` في mutations هذه الوحدة.
   - لا FormValidation legacy في صفحاتها النشطة.
   - اختبارات HTTP تشمل 200/302، 422، 403، وعزل tenant.
   - `npm run build` بعد أي تعديل frontend، واختبارات Laravel المركزة ناجحة.

   **الحالة: مكتمل — 6 أكتوبر 2026.**
   - اجتاز `TenantUserManagementValidationFeatureTest` (4 اختبارات/11 assertion) وملفات دورة المستخدم/الفرق/الأدوار في `TenantCrudFeatureTest` (3 اختبارات/37 assertion)، إضافة إلى Unit suite (28 اختباراً/1309 assertion).
