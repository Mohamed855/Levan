# قائمة Smoke Tests اليدوية

> تستخدم بعد كل مرحلة تمسّ التدفق المذكور. ضع النتيجة والتاريخ واسم المنفذ في سجل التنفيذ، ولا تستبدل الاختبارات الآلية.

## 1. Website

- [ ] الصفحة الرئيسية تعرض الباقات النشطة والعملاء والأعمال دون أخطاء.
- [ ] نموذج التواصل: حقل ناقص، بريد غير صالح، reCAPTCHA مرفوض، نجاح الخدمة الخارجية، وفشلها.
- [ ] طلب التجربة: الأخطاء والنجاح والـredirect والـflash message.
- [ ] checkout: باقة غير نشطة، coupon صالح، coupon مرفوض، بطاقة، تحويل بنكي، وإعادة زيارة callback.
- [ ] لا يظهر placeholder متحرك ولا content movement داخل الأزرار عند hover/focus.

## 2. Super Admin

- [ ] تسجيل الدخول، الحساب غير النشط، 2FA، logout، واستعادة كلمة المرور.
- [ ] الباقات: create/edit، خمس خطوات، Next disabled، dynamic features، save/restore/discard draft، preview، reorder، toggle.
- [ ] الشركات: uniqueness checks، إنشاء Tenant، edit، archive/restore، والصلاحيات.
- [ ] الاشتراكات والفواتير: إنشاء، اعتماد، رفض، طباعة، تحويل بنكي، وإعادة تنشيط.
- [ ] الكوبونات والمستخدمون والأدوار والإصدارات والإعدادات: CRUD، validation، status، و403 للصلاحية المفقودة.
- [ ] كل DataTable: loading، empty، filter، search، status قبل actions، وحذف مؤكد.

## 3. Tenant: الوصول والعزل

- [ ] الدخول عبر path tenancy وعبر domain tenancy.
- [ ] عدم الوصول من central domain عند وجوب منع ذلك.
- [ ] اشتراك منتهٍ، license مفقود، maintenance mode، و2FA.
- [ ] Tenant A لا يرى أو يعدل سجلات Tenant B في أي URL أو AJAX endpoint أو API token.

## 4. Tenant: المستخدمون والعمل الأساسي

- [ ] users/teams/roles/branches: CRUD، status، الصلاحيات، والـprofile.
- [ ] companies/contracts/portfolios/tasks: CRUD، route-model binding، archive/restore، وتصدير إن وجد.
- [ ] customers: create/edit/show/delete، phones، fields، attachments، notes، comments، follows، payments، settlements، plans، SMS، update requests.
- [ ] import/bulk update/distribution/export: upload خاطئ، progress، cancel، file download، والنتيجة النهائية.

## 5. Tenant: المال والرسائل والدومينات

- [ ] collections/admin collections/targets/collection process/reconciliation: الصلاحيات، approval/reject، progress، التقارير.
- [ ] message templates/logs/letters: preview، send، failure، archive/download.
- [ ] subscription/bills/licenses/tickets/versions: دفع، callback، bank receipt، reply/rating، upgrade.
- [ ] domains: search، DNS، transfer، CPanel limits، payment، callback، idempotency، ورفض GET للـmutation بعد المرحلة 1.

## 6. Form validation وواجهة المستخدم

- [ ] كل form مستهدفة تعرض `is-invalid` عند الخطأ و`is-valid` بعد التحقق الناجح.
- [ ] الرسالة تظهر مرة واحدة في مكان accessible ومربوط بالحقل.
- [ ] Select2، input groups، files، repeaters، وarrays تعمل مع validation.
- [ ] زر Next معطل بدايةً ولا يتفعّل قبل صلاحية الخطوة.
- [ ] restore draft يعيد القيم والـdynamic rows؛ discard لا يعيدها.
- [ ] لا تبقى صفحة تستورد validation قديم بعد اعتمادها للمحرك المشترك.

## 7. API والأخطاء

- [ ] API customer token: token مفقود/خاطئ/صحيح وscope Tenant صحيح.
- [ ] API upgrade: طلب غير مصرح، rate limit، audit entry، منع تشغيل متوازٍ، وفشل migration دون كشف exception.
- [ ] Website/Admin/Tenant: 403 و404 و500 تعرض قالب النطاق الصحيح.
- [ ] AJAX errors تستخدم JSON contract متسقاً؛ HTML forms تبقي redirect مع flash message.
