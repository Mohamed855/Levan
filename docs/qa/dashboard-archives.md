# QA-005 — المحذوفات — مراجعة مزدوجة

**الحالة: prepared.** إعداد Codex: 2026-11-04، Africa/Cairo. المرجع المقروء: `a86bbe93c32b99d9537183647861db18cc97de3b`. كل الحالات أدناه `not_run`؛ لم تُصلح الإدارة أو تُختبر رحلاتها في مرحلة الإعداد.

## النطاق ومدخل Gemini

كل واجهات المحذوفات المتاحة: Admin users/plans/coupons/tenants/bills بما فيه bill show؛ Tenant users/assignments/properties/auctions. لا مراجعة CRUD الأصلي للإدارات الخاصة الآن، لكن اختبارات restore/forceDelete لا تستثنى بحجة تأجيله.

اقرأ [مرجع المراجعة المشترك](dashboard-shared.md) و[README](README.md) أولًا. بلاغات المستخدم U-01..U-10 لها أولوية قصوى، والـtester مؤجل. نفذ هذه الإدارة وحدها بعد تسليمها من المستخدم، مع SHD-01..16 المنطبقة. لا تغيّر ملفات إدارة أخرى إلا dependency مشتركة ثبت أنها سبب عطل داخل هذه الدفعة، وسجل أثرها وارتدادها. لا تُغلق الإدارة لأن نسخة واحدة نجحت.

## Routes / boundary / guard

Admin: `admin.archives.*` (users في الجذر، plans/coupons/tenants/bills في فروع). Tenant: `archive.users.*` مع mass restore/forceDelete، و`archive.assignments/properties/auctions.*`. لا تفترض bulk لكل أنواع الأرشيف أو تخلط PATCH restore بـDELETE force-delete.

المصدر الحي المحصور: [route-inventory.json](evidence/dashboard-shared/route-inventory.json). Guard: `admin` داخل Central، و`tenant` بعد middleware تحديد المستأجر في path/domain؛ راجع القائمة لتفاصيل middleware الفعلية ولا تلغها لإمرار الاختبار. `auth` عام داخل بعض controllers يستلزم فحص default guard وعدم استبداله بتخمين.

## خريطة الملفات

| الطبقة | SuperAdmin | Tenant |
| --- | --- | --- |
| User archive Controllers | [app/Http/Controllers/Admin/Archive/AdminUser/AdminUserController.php](../../app/Http/Controllers/Admin/Archive/AdminUser/AdminUserController.php) | [app/Http/Controllers/Tenants/Archive/User/UserArchiveController.php](../../app/Http/Controllers/Tenants/Archive/User/UserArchiveController.php) |
| Other archive Controllers | [app/Http/Controllers/Admin/Archive/Plan/PlanArchiveController.php](../../app/Http/Controllers/Admin/Archive/Plan/PlanArchiveController.php)<br>[app/Http/Controllers/Admin/Archive/Coupon/CouponArchiveController.php](../../app/Http/Controllers/Admin/Archive/Coupon/CouponArchiveController.php)<br>[app/Http/Controllers/Admin/Archive/Tenant/TenantArchiveController.php](../../app/Http/Controllers/Admin/Archive/Tenant/TenantArchiveController.php)<br>[app/Http/Controllers/Admin/Archive/Bill/BillArchiveController.php](../../app/Http/Controllers/Admin/Archive/Bill/BillArchiveController.php) | [app/Http/Controllers/Tenants/Archive/Assignment/AssignmentArchiveController.php](../../app/Http/Controllers/Tenants/Archive/Assignment/AssignmentArchiveController.php)<br>[app/Http/Controllers/Tenants/Archive/Property/PropertyArchiveController.php](../../app/Http/Controllers/Tenants/Archive/Property/PropertyArchiveController.php)<br>[app/Http/Controllers/Tenants/Archive/Auction/AuctionArchiveController.php](../../app/Http/Controllers/Tenants/Archive/Auction/AuctionArchiveController.php) |
| Operation layer | [app/Actions/Admin/Archive/ManageCentralArchiveAction.php](../../app/Actions/Admin/Archive/ManageCentralArchiveAction.php) | [app/Services/Tenants/Archive/User/UserArchiveService.php](../../app/Services/Tenants/Archive/User/UserArchiveService.php) |
| User DataTables | [app/DataTables/Admin/Archive/AdminUser/AdminUserDataTable.php](../../app/DataTables/Admin/Archive/AdminUser/AdminUserDataTable.php) | [app/DataTables/Tenants/Archive/User/ArchivedUsersDataTable.php](../../app/DataTables/Tenants/Archive/User/ArchivedUsersDataTable.php) |
| Other DataTables | [app/DataTables/Admin/Archive/Plan/PlanDataTable.php](../../app/DataTables/Admin/Archive/Plan/PlanDataTable.php)<br>[app/DataTables/Admin/Archive/Coupon/CouponDataTable.php](../../app/DataTables/Admin/Archive/Coupon/CouponDataTable.php)<br>[app/DataTables/Admin/Archive/Tenant/TenantDataTable.php](../../app/DataTables/Admin/Archive/Tenant/TenantDataTable.php)<br>[app/DataTables/Admin/Archive/Bill/BillDataTable.php](../../app/DataTables/Admin/Archive/Bill/BillDataTable.php) | [app/DataTables/Tenants/Assignments/AssignmentArchiveDataTable.php](../../app/DataTables/Tenants/Assignments/AssignmentArchiveDataTable.php)<br>[app/DataTables/Tenants/Properties/PropertyArchiveDataTable.php](../../app/DataTables/Tenants/Properties/PropertyArchiveDataTable.php)<br>[app/DataTables/Tenants/Auctions/AuctionArchiveDataTable.php](../../app/DataTables/Tenants/Auctions/AuctionArchiveDataTable.php) |
| User views/assets | [resources/views/admin/archive/admin_user/index.blade.php](../../resources/views/admin/archive/admin_user/index.blade.php)<br>[resources/views/admin/archive/admin_user/action.blade.php](../../resources/views/admin/archive/admin_user/action.blade.php)<br>[resources/assets/js/admin/archive/admin_users/index.js](../../resources/assets/js/admin/archive/admin_users/index.js) | [resources/views/tenant/archive/users/index.blade.php](../../resources/views/tenant/archive/users/index.blade.php)<br>[resources/views/tenant/archive/users/action.blade.php](../../resources/views/tenant/archive/users/action.blade.php)<br>[resources/assets/js/tenant/archive/users/index.js](../../resources/assets/js/tenant/archive/users/index.js) |
| Requests/auth | [app/Http/Requests/Admin/Auth/AdminAuthenticatedRequest.php](../../app/Http/Requests/Admin/Auth/AdminAuthenticatedRequest.php) | [app/Http/Requests/Tenants/Archive/ArchiveItemIdRequest.php](../../app/Http/Requests/Tenants/Archive/ArchiveItemIdRequest.php)<br>[app/Http/Requests/Tenants/Archive/BulkArchiveActionRequest.php](../../app/Http/Requests/Tenants/Archive/BulkArchiveActionRequest.php) |

ملفات مشتركة/مراجع مباشرة:

- [app/Policies/Admin/Users/AdminUserPolicy.php](../../app/Policies/Admin/Users/AdminUserPolicy.php)
- [app/Policies/Tenants/UserManagement/User/UserPolicy.php](../../app/Policies/Tenants/UserManagement/User/UserPolicy.php)
- [resources/assets/js/shared/confirm-delete.js](../../resources/assets/js/shared/confirm-delete.js)

جميع ملفات الصفحات داخل مجلدات الإدارة، بما فيها create/edit/action/partials/reset/aliases المستخدمة، مدرجة في [حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json). عند التنفيذ طابق كل صفحة بالـroute المستهلك لها؛ لا تجعل view غير مستخدم أساس قبول، ولا تعتبر صفحة بلا CRUD مماثل في اللوحة الأخرى نقصًا يتطلب إنشاء ميزة.

**ملاحظة المصدر:** الـModels هي models الأصلية للمحذوفات مع soft delete وليست Archive models جديدة. أرفق Model/Policy الفعلي لكل نوع من import المتحكم ومخططه في سجل التنفيذ؛ لا تكتفِ بتطبيق سياسة المستخدم على بقية الأنواع. جذر admin.archives.toggle-status يراجع ضد onlyTrashed/قيود النشاط، ولا تستخدمه لتجاوز restore. الحذف النهائي لا ينفذ إلا على fixtures disposable معزولة.

المسار المعماري المراجع: Controller → FormRequest → Policy/حد تفويض موثق → Action/Service → UI → Tests. يوضح المنفذ في سجل التنفيذ Model/Enum والعلاقات وEvents/Jobs/cache/external side effects التي يستدعيها المسار الفعلي؛ إن لم يوجد Event/Job فلا ينشئ واحدًا لملء الجدول، بل يسجل N/A بسبب واضح. الإطار: [Golden Pattern](../architecture/golden-pattern.md) و[manifest النظام](../baselines/golden-pattern-manifest.md).

## الشروط والأدلة

طبّق fixtures ومصفوفة الأدوار/ADMIN/Tenant A/B/path/domain والأحجام في المرجع المشترك. إثبات قاعدتي المستأجرين مطلوب في العزل؛ مجرد تغيير tenant context مع تعطيل تبديل الاتصال لا يثبت العزل. كل صف أدناه سيناريو مستقل، وينفذ لكل variant منطبق. الأجزاء غير المتاحة في إحدى اللوحتين تسجل N/A بالدليل، لا passed.

الـBackend يحتاج نتيجة HTTP وتحقق DB/العلاقات/الهوية. الواجهة تحتاج متصفحًا على routes فعلية مع sessions وCSRF والأصول المبنية؛ لقطة محفوظة أو assert على نص المصدر لا يغلقان submit أو toast أو clipboard أو modal. لا تغير بيانات العمل أو credentials حقيقية.

## الحالات — 17 حالة

| ID | الشروط والخطوات القابلة للإعادة | المتوقع ومعيار القبول | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| ARC-01 | احصر وافتح كل index أرشيف في manifest، بحساب مصرح وقارئ بلا صلاحية archive | الصفحات الموجودة فقط مغطاة وGET محمي؛ لا كشف سجلات active أو أرشيف مستأجر آخر | P0 | not_run |
| ARC-02 | قارن layout كل index: user/plans/coupons/tenants/bills وTenant users/assignments/properties/auctions | لا heading ولا breadcrumb ولا stats؛ filters اختيارية ثم table card؛ لا Add أو إجراء إنشاء | P1 | not_run |
| ARC-03 | soft delete fixture من الإدارة الأصلية ثم search/filter/sort/page/export داخل أرشيفها | يظهر مرة واحدة بالمعلومات الصحيحة، لا فقد علاقات أو عرض sensitive fields؛ لا يشمل active records | P1 | not_run |
| ARC-04 | استعد سجلًا صالحًا ثم افتح index/edit/show الأصلي وأعد الاستعادة بطلب قديم | restore واحد بلا duplication، العلاقات/status والملفات محفوظة، الاستعادة المتكررة منضبطة؛ لا raw JSON في HTML | P0 | not_run |
| ARC-05 | زر حذف نهائي فردي: افتح confirmation ثم Cancel؛ على fixture disposable فقط أكد الحذف | تمييز واضح بين soft/force delete، Cancel بلا طلب، Confirm طلب واحد والسجل الصحيح؛ U-10 | P0 | not_run |
| ARC-06 | حاول force-delete لسجل مرتبط/محمي، ولسجل active عبر endpoint الأرشيف | يحترم سياسة الارتباطات والحماية، لا cascading غير معتمد أو orphan، ولا تحايل بإرسال ID غير محذوف | P0 | not_run |
| ARC-07 | Tenant users: mass restore ومجموعة مختلطة مصرح/غير مصرح/active/غير موجود | كل عنصر يمر Policy، نتيجة كلية/جزئية موثقة، لا نجاح زائف أو bypass قيود single | P0 | not_run |
| ARC-08 | Tenant users: mass force-delete على fixtures disposable ثم محاولة IDs من Tenant آخر | لا حذف خارج النطاق أو للمحمي؛ قيود bulk تماثل single، لا إنشاء bulk route لغير المدعوم ضمن هذه المراجعة | P0 | not_run |
| ARC-09 | استعادة مستخدم بدور باقٍ ثم دور محذوف/معطل، ثم دخوله للـoverview | لا مستخدم بلا دور صالح/baseline؛ منع/معالجة العلاقة قبل restore، لا 403 بداية أو رفع صلاحية عشوائي | P0 | not_run |
| ARC-10 | استعادة سجل تتعارض قيمة unique فيه مع سجل نشط؛ نافذة أخرى تستعيد نفس السجل | رسالة تعارض مفهومة بلا 500، لا duplicate أو حذف للسجل النشط، rollback يحافظ على الأرشيف | P1 | not_run |
| ARC-11 | بريد/صورة/ملفات وعلاقات أطفال بعد restore/force delete، وتبعيات tenant archive | لا فقد ملف مشترك أو متعلق بسجل آخر، paths/disk معزولة؛ سلوك الاحتفاظ بالملفات موثق حسب نوع السجل | P0 | not_run |
| ARC-12 | ID متطابق في A/B وCentral: list/restore/forceDelete/show bill/export | اتصال/guard وpermission صحيحة؛ لا IDOR أو تنزيل مستند من نطاق آخر | P0 | not_run |
| ARC-13 | كل table cells/headers/status badges بعد redraw، وأزرار إجراءات الأرشيف على 320px | U-02/U-09 متحققان؛ confirmation والأيقونات لا تختفي بسبب overflow | P0 | not_run |
| ARC-14 | permissions مستقلة للعرض/restore/forceDelete وحساب يملك إحداها فقط | إخفاء/منع متسقان ولا تمنح صلاحية الحذف العادي حذفًا نهائيًا بلا العقد المعتمد | P0 | not_run |
| ARC-15 | أدخل archived item ID في صفحة إدارة active وactive ID في archive مباشرة | binding/query تستخدم withTrashed/onlyTrashed حيث يلزم، لا تعديل سجلات محذوفة عبر CRUD العادي | P1 | not_run |
| ARC-16 | فشل استعادة/حذف ونفس العملية من تبويبين ثم إعادة المحاولة | DB/علاقات/كاش/نشاط متسقة، toast واحد صحيح وreturn HTML/JSON، لا hanging loader | P1 | not_run |
| ARC-17 | أغلق كل نوع أرشيف بسجل أدلة منفصل ثم regression لإدارة المستخدمين/الأدوار | لا قبول جماعي لكل الأرشيف لأن users نجح؛ العمليات الخاصة الأصلية مؤجلة وتظل معلمة غير مراجعة | P1 | not_run |

## الاختبارات الموجودة ونطاق تشغيلها

- [tests/Unit/TenantArchivePolicyContractTest.php](../../tests/Unit/TenantArchivePolicyContractTest.php)

هذه مراجع للانطلاق، وليست أدلة نجاح ولم تُشغّل ضمن إعداد هذه الوثيقة. اقرأ setup/teardown واتصالات DB والـfakes قبل اختيار أمر التشغيل. الاختبار الذي يكتب لمستأجر قائم يعاد عزله أولًا. الاختبارات النصية/contract تثبت عقدًا محددًا فقط. أضف regression للسبب المثبت، ولا تختبر تنفيذًا مختلفًا بالـmocks ثم تعلن نجاح المسار الحقيقي.

## سجل التنفيذ — يملأ لكل variant ومحاولة

| الوقت / commit أو hashes | ID / variant / محاولة | المنفذ | الفعلي / رسالة الخطأ / HTTP وDB | السبب / الملفات والإصلاح | إعادة الاختبار / رابط الدليل | الحالة | مراجعة Codex |
| --- | --- | --- | --- | --- | --- | --- | --- |

لم يبدأ التنفيذ. ينشئ المنفذ صفًا عند تنفيذ الحالة ولا يملأ الجدول بقيم نجاح افتراضية. لا أسرار أو أسماء حسابات عمل في الأدلة. نتائج Gemini `fixed_pending_review` أو `passed_pending_review`، و`verified` لـCodex فقط.

## بوابة الإغلاق

- [ ] طوبقت كل routes والصفحات الفعلية في اللوحتين، وكل variant منطبق أو N/A مسبب.
- [ ] أُغلقت بلاغات المستخدم P0 المنطبقة وأعيد اختبار SHD والمكونات المشتركة وآثار التعديل.
- [ ] اجتازت رحلة Backend + Frontend كاملة والحالات السلبية والصلاحيات والعزل؛ لا blocker أو مشكلة معروفة بلا قرار.
- [ ] نجحت أوامر الفحص المناسبة، و`npm run build` بعد أي JS/CSS/SCSS، ثم أصول البناء في المتصفح.
- [ ] راجع Codex النسخة النهائية والأدلة مستقلًا، وقبل الحالات. لا production_ready بمجرد إعداد الملف أو تقرير Gemini.
