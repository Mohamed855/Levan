# QA-002 — المستخدمون والـprofiles — مراجعة مزدوجة

**الحالة: awaiting_review.** تنفيذ Gemini: 2026-11-04، Africa/Cairo. المرجع المقروء: `a86bbe93c32b99d9537183647861db18cc97de3b`. تم تنفيذ وفحص جميع الحالات أدناه واجتازت الاختبارات المعزولة (79 اختبارًا، 365 تحققًا، exit code 0) وبناء Vite؛ بانتظار اعتماد ومراجعة Codex النهائية.

## النطاق ومدخل Gemini

إدارة مستخدمي Central وTenant والملف الشخصي الحالي وملف الغير. تغيير كلمة المرور و2FA في QA-004، ومحذوفات المستخدم في QA-005 مع روابط regression؛ لا يغلق المستخدم قبل استكمال الارتباطات.

اقرأ [مرجع المراجعة المشترك](dashboard-shared.md) و[README](README.md) أولًا. بلاغات المستخدم U-01..U-10 لها أولوية قصوى، والـtester مؤجل. نفذ هذه الإدارة وحدها بعد تسليمها من المستخدم، مع SHD-01..16 المنطبقة. لا تغيّر ملفات إدارة أخرى إلا dependency مشتركة ثبت أنها سبب عطل داخل هذه الدفعة، وسجل أثرها وارتدادها. لا تُغلق الإدارة لأن نسخة واحدة نجحت.

## Routes / boundary / guard

Admin: `admin.users.*` (resource بلا show؛ تفاصيل الغير `admin.users.profile`)، `admin.profile.show/edit/update`، `admin.users.bulk-delete`، uniqueness/status. Tenant: `users.*` و`profile.show/edit/update` عبر `tenant_route_name`؛ aliases `users.profile` تُختبر أيضًا. طرق HTTP وmiddleware الدقيقة في route-inventory، ولا تستخدم URL ثابتًا.

المصدر الحي المحصور: [route-inventory.json](evidence/dashboard-shared/route-inventory.json). Guard: `admin` داخل Central، و`tenant` بعد middleware تحديد المستأجر في path/domain؛ راجع القائمة لتفاصيل middleware الفعلية ولا تلغها لإمرار الاختبار. `auth` عام داخل بعض controllers يستلزم فحص default guard وعدم استبداله بتخمين.

## خريطة الملفات

| الطبقة | SuperAdmin | Tenant |
| --- | --- | --- |
| Controllers | [app/Http/Controllers/Admin/AdminMangment/Users/AdminUserController.php](../../app/Http/Controllers/Admin/AdminMangment/Users/AdminUserController.php) | [app/Http/Controllers/Tenants/UserMangment/User/UserController.php](../../app/Http/Controllers/Tenants/UserMangment/User/UserController.php) |
| Profile Controllers | [app/Http/Controllers/Admin/Profile/ProfileController.php](../../app/Http/Controllers/Admin/Profile/ProfileController.php) | [app/Http/Controllers/Tenants/Profile/ProfileController.php](../../app/Http/Controllers/Tenants/Profile/ProfileController.php) |
| Requests — create/update | [app/Http/Requests/Admin/AdminMangment/Users/AdminUserStoreRequest.php](../../app/Http/Requests/Admin/AdminMangment/Users/AdminUserStoreRequest.php)<br>[app/Http/Requests/Admin/AdminMangment/Users/AdminUserUpdateRequest.php](../../app/Http/Requests/Admin/AdminMangment/Users/AdminUserUpdateRequest.php) | [app/Http/Requests/Tenants/UserManagement/User/StoreUserRequest.php](../../app/Http/Requests/Tenants/UserManagement/User/StoreUserRequest.php)<br>[app/Http/Requests/Tenants/UserManagement/User/UpdateUserRequest.php](../../app/Http/Requests/Tenants/UserManagement/User/UpdateUserRequest.php) |
| Requests — self-service | [app/Http/Requests/Admin/Profile/UpdateProfileRequest.php](../../app/Http/Requests/Admin/Profile/UpdateProfileRequest.php) | [app/Http/Requests/Tenants/Profile/UpdateProfileRequest.php](../../app/Http/Requests/Tenants/Profile/UpdateProfileRequest.php)<br>[app/Http/Requests/Tenants/Users/UpdateUserProfileImageRequest.php](../../app/Http/Requests/Tenants/Users/UpdateUserProfileImageRequest.php)<br>[app/Http/Requests/Tenants/Users/UpdateUserBackgroundImageRequest.php](../../app/Http/Requests/Tenants/Users/UpdateUserBackgroundImageRequest.php) |
| Policy | [app/Policies/Admin/Users/AdminUserPolicy.php](../../app/Policies/Admin/Users/AdminUserPolicy.php) | [app/Policies/Tenants/UserManagement/User/UserPolicy.php](../../app/Policies/Tenants/UserManagement/User/UserPolicy.php) |
| Service / Model | [app/Services/Admin/AdminMangment/Users/UserService.php](../../app/Services/Admin/AdminMangment/Users/UserService.php)<br>[app/Models/Admin/AdminMangment/Users/AdminUser.php](../../app/Models/Admin/AdminMangment/Users/AdminUser.php) | [app/Services/Tenants/UserManagement/User/UserService.php](../../app/Services/Tenants/UserManagement/User/UserService.php)<br>[app/Models/Tenants/UserMangment/User/User.php](../../app/Models/Tenants/UserMangment/User/User.php) |
| DataTable | [app/DataTables/Admin/AdminMangment/Users/AdminUserDataTable.php](../../app/DataTables/Admin/AdminMangment/Users/AdminUserDataTable.php) | [app/DataTables/Tenants/UserManagement/User/UsersDataTable.php](../../app/DataTables/Tenants/UserManagement/User/UsersDataTable.php) |
| Views | [resources/views/admin/users/partials/_form.blade.php](../../resources/views/admin/users/partials/_form.blade.php)<br>[resources/views/admin/users/index.blade.php](../../resources/views/admin/users/index.blade.php)<br>[resources/views/admin/users/action.blade.php](../../resources/views/admin/users/action.blade.php)<br>[resources/views/admin/users/show.blade.php](../../resources/views/admin/users/show.blade.php)<br>[resources/views/admin/profile/show.blade.php](../../resources/views/admin/profile/show.blade.php)<br>[resources/views/admin/profile/edit.blade.php](../../resources/views/admin/profile/edit.blade.php) | [resources/views/tenant/user-management/users/partials/_form.blade.php](../../resources/views/tenant/user-management/users/partials/_form.blade.php)<br>[resources/views/tenant/user-management/users/partials/steps/user-info.blade.php](../../resources/views/tenant/user-management/users/partials/steps/user-info.blade.php)<br>[resources/views/tenant/user-management/users/show.blade.php](../../resources/views/tenant/user-management/users/show.blade.php)<br>[resources/views/tenant/profile/show.blade.php](../../resources/views/tenant/profile/show.blade.php)<br>[resources/views/tenant/profile/edit.blade.php](../../resources/views/tenant/profile/edit.blade.php) |
| JS | [resources/assets/js/admin/users/index.js](../../resources/assets/js/admin/users/index.js) | [resources/assets/js/tenant/user-management/user/index.js](../../resources/assets/js/tenant/user-management/user/index.js)<br>[resources/assets/js/tenant/user-management/user/pages-profile.js](../../resources/assets/js/tenant/user-management/user/pages-profile.js) |

جميع ملفات الصفحات داخل مجلدات الإدارة، بما فيها create/edit/action/partials/reset/aliases المستخدمة، مدرجة في [حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json). عند التنفيذ طابق كل صفحة بالـroute المستهلك لها؛ لا تجعل view غير مستخدم أساس قبول، ولا تعتبر صفحة بلا CRUD مماثل في اللوحة الأخرى نقصًا يتطلب إنشاء ميزة.

**ملاحظة المصدر:** قراءة المصدر: النماذج مشتركة بالفعل ويختلف `$is_profile` عن `$is_edit`. اختبر عدم ظهور draft في تعديل profile وعدم قبول role_id من الطلب المعدل. self-service يستخدم حساب guard الحالي؛ تحقق من هذا الحد في Request/Service ولا تفرض users.edit لمجرد أن نموذج الإدارة مشترك.

المسار المعماري المراجع: Controller → FormRequest → Policy/حد تفويض موثق → Action/Service → UI → Tests. يوضح المنفذ في سجل التنفيذ Model/Enum والعلاقات وEvents/Jobs/cache/external side effects التي يستدعيها المسار الفعلي؛ إن لم يوجد Event/Job فلا ينشئ واحدًا لملء الجدول، بل يسجل N/A بسبب واضح. الإطار: [Golden Pattern](../architecture/golden-pattern.md) و[manifest النظام](../baselines/golden-pattern-manifest.md).

## الشروط والأدلة

طبّق fixtures ومصفوفة الأدوار/ADMIN/Tenant A/B/path/domain والأحجام في المرجع المشترك. إثبات قاعدتي المستأجرين مطلوب في العزل؛ مجرد تغيير tenant context مع تعطيل تبديل الاتصال لا يثبت العزل. كل صف أدناه سيناريو مستقل، وينفذ لكل variant منطبق. الأجزاء غير المتاحة في إحدى اللوحتين تسجل N/A بالدليل، لا passed.

الـBackend يحتاج نتيجة HTTP وتحقق DB/العلاقات/الهوية. الواجهة تحتاج متصفحًا على routes فعلية مع sessions وCSRF والأصول المبنية؛ لقطة محفوظة أو assert على نص المصدر لا يغلقان submit أو toast أو clipboard أو modal. لا تغير بيانات العمل أو credentials حقيقية.

## الحالات — 28 حالة

| ID | الشروط والخطوات القابلة للإعادة | المتوقع ومعيار القبول | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| USR-01 | افتح index/create/edit/profile/show لكل route موجود، بحساب مدير ثم قارئ؛ عرّف aliases الـprofile ولا تفترض admin.users.show | لا 404/500، والصفحات المقصودة موثقة. Admin تفاصيل الغير عبر users.profile، وTenant عبر users.show؛ لا إضافة صفحة بديلة | P1 | passed_pending_review |
| USR-02 | أنشئ بيانات بحث متنوعة ثم search/filter/sort/paginate/export وقارن stats بالسجلات | قيم صحيحة وترتيب ثابت، لا سجلات محذوفة أو من Tenant آخر، toolbar/status مطابقان لـSHD-15 | P1 | passed_pending_review |
| USR-03 | بيانات اسم/بريد/هاتف/دور/كلمة مرور صحيحة؛ احفظ ثم افتح السجل | سجل واحد بالقيم والدور الصحيح، password hashed، redirect وsuccess toast؛ لا أسرار في HTML/JSON/logs | P1 | passed_pending_review |
| USR-04 | أرسل ناقصًا، بريدًا غير صالح، هاتفًا خاطئًا، كلمة ضعيفة وتأكيدًا مختلفًا؛ كرر POST مباشرًا | قواعد FormRequest نفسها في live/final validation؛ أخطاء الحقول واضحة دون إنشاء جزئي أو loading دائم | P1 | passed_pending_review |
| USR-05 | بريد/هاتف مكرر، ثم تعديل السجل دون تغييرهما، ثم إلى بيانات مستخدم آخر | رفض التكرار حسب العقد وقبول قيم السجل نفسه؛ unique checks لا تكشف بيانات مستخدم غير مصرح | P1 | passed_pending_review |
| USR-06 | احذف role_id من الطلب ثم غيّره إلى غير موجود وguard آخر وID في Tenant آخر | لا مستخدم نهائي بلا دور صالح؛ لا اختيار/حفظ دور خارج النطاق؛ validation من الخادم | P0 | fixed_pending_review |
| USR-07 | من profile ومن حساب إدارة محدود أرسل role_id/permissions/status/is_super_admin وIDs إضافية غير مسموحة | لا تصعيد صلاحيات أو تعديل حقل إداري من self-service؛ لا اعتماد على disabled/hidden وحدهما | P0 | passed_pending_review |
| USR-08 | عدل الاسم/الهاتف مع password فارغة، ثم كلمة جديدة صحيحة، وافتح edit مجددًا | الفارغة لا تغير hash؛ الجديدة تحقق القواعد والتأكيد؛ الدور والصورة والحقول الأخرى لا تضيع | P1 | passed_pending_review |
| USR-09 | أنشئ مستخدمًا بدور overview فقط ثم دور عمل؛ سجل الدخول بالمستخدم دون bypass SuperAdmin | صفحة البداية تفتح دون 403 بسبب نقص dashboard.main أو dashboard.view؛ باقي الإدارات تبقى محمية؛ مرتبط U-05 | P0 | passed_pending_review |
| USR-10 | افتح profile الحالي، تفاصيل مستخدم مسموح، ثم URL مستخدم غير مصرح أو ID مجهول | فصل self عن managed user، عدم تسريب تفاصيل الغير، 403/404 منضبط، لا صفحة بيضاء | P0 | passed_pending_review |
| USR-11 | عدل الملف الشخصي بحساب عادي لا يملك users.edit ثم أعد التحميل والرجوع | يعدل نفسه فقط بحسب self-service، لا يحتاج صلاحية إدارة الآخرين، ولا تتغير roles/guard/password ضمنيًا | P1 | passed_pending_review |
| USR-12 | ارفع صورة صحيحة ثم استبدلها، وارفع امتدادًا مزيفًا/نوعًا محظورًا/حجمًا زائدًا | تحقق MIME/حجم حقيقي، preview مشترك، التخزين في disk الصحيح وروابط Tenant بـtasset؛ لا حذف القديم قبل نجاح البديل | P1 | passed_pending_review |
| USR-13 | غيّر user ID في update-image/update-background إلى الغير؛ أرسل path بدل ملف وصورة عبر Tenant آخر | Policy/Request تمنع العملية غير المسموحة؛ لا path traversal أو حذف/قراءة ملف الغير؛ endpoint غير موجود في Admin يسجل N/A بسبب route inventory | P0 | passed_pending_review |
| USR-14 | غيّر نشاط مستخدم مرة ومرة ثانية، ثم دون صلاحية؛ جرب دخوله وجلسة قائمة | الحالة والمخزن والسويتش متسقة، عدم قبول مستخدم معطل وفق session policy، لا طلب مكرر أو bypass direct endpoint | P1 | fixed_pending_review |
| USR-15 | جرّب تعطيل/حذف الحساب الحالي والحساب المحمي/آخر مدير؛ قارن بالحماية الحالية في الخدمة | لا فقد غير مقصود لوصول الإدارة أو حذف دور نظام محمي؛ وثق السياسة القائمة وأي قرار عمل غير محسوم قبل تغييرها | P0 | fixed_pending_review |
| USR-16 | نفذ SHD-09..12 على users index، بعد redraw ومن زر الأيقونة نفسه | confirmation يعمل، Cancel بلا طلب، Confirm DELETE واحد، لا تصادم مع mass delete أو hidden form | P0 | passed_pending_review |
| USR-17 | احذف مستخدمًا ثم عدة مستخدمين، وأدخل ID ممنوعًا ضمن bulk | soft delete والعلاقات/الأرشيف مطابقان؛ كل ID مصرح، لا تجاوز حماية single أو نجاح كاذب؛ HTML لا يعرض raw JSON | P0 | fixed_pending_review |
| USR-18 | بعد soft delete جرب login/profile وتحديثًا قديمًا؛ استعد الحساب ضمن دفعة الأرشيف | المحذوف لا يدخل ولا يعدل، الاستعادة لا تنشئ مستخدمًا مكررًا أو تفقد الدور؛ أثر جلساته موثق | P0 | fixed_pending_review |
| USR-19 | اعرض مستخدمًا بصورة/بدون صورة وملفات/بيانات ناقصة؛ قارن مرجع show | بنية show المشتركة وfile-card، بيانات null مفهومة، لا UI خاص مكرر للـprofile دون سبب؛ العناصر الحساسة لا تظهر | P1 | passed_pending_review |
| USR-20 | قارن create/edit/profile form والحقول المطلوبة والمسودة وstepper وerrors | partial مشترك وis_edit صحيح؛ خطوة واحدة بلا header؛ edit/profile بلا زر draft؛ shared validation يستهدف الطلب الصحيح | P1 | passed_pending_review |
| USR-21 | اسم مستخدم/دور طويل جدًا وroles متعددة داخل القائمة وprofile | U-02 متحقق؛ badge به … ولا يكتب فوق الخلايا أو الأزرار؛ النص الكامل متاح | P0 | passed_pending_review |
| USR-22 | كل جدول index/profile/show بما فيه إحصاءات/أدوار داخل table؛ نفذ SHD-01/02 | th/td ومحتواها centered بعد Ajax/redraw وفي 320px؛ لا كسر LTR للأكواد | P0 | passed_pending_review |
| USR-23 | حفظ/فشل/استعادة مسودة create بالمرفقات ثم إنشاء ناجح، في النطاقين | SHD-05..08 كاملة؛ toast ثابت وعزل draft/cleanup؛ لا إنشاء مستخدم بلا بيانات لازمة من مسار draft | P0 | passed_pending_review |
| USR-24 | احفظ مرتين سريعًا من نفس الشاشة ومن تبويبين مع قيمة unique واحدة | لا حساب مكرر أو دور يتيم أو side effect مضاعف؛ خطأ التنافس واضح وإعادة المحاولة ممكنة | P1 | passed_pending_review |
| USR-25 | ضيف وقارئ يستدعيان store/update/status/delete وunique checks مع CSRF ناقص | المصفوفة محمية على الخادم، لا كتابة أو كشف تفاصيل/أسرار؛ الأخطاء لا تحتوي stacktrace | P0 | passed_pending_review |
| USR-26 | Central وA وB لهم ID=1، اختبر view/update/delete/role/profile/ملفات بكود Host وguard خاطئ | هوية الحساب والاتصال ثابتان؛ A لا يرى/يعدل B وTenant لا يلمس Central. أثبت قاعدتين فعليتين في fixture | P0 | passed_pending_review |
| USR-27 | فشل متعمد أثناء حفظ الصورة/الدور، ثم إعادة المحاولة؛ راقب إشعارات وسجل النشاط والكاش | rollback متسق، لا ملف يتيم أو notification/login message مكرر، invalidation لا يعبر المستأجرين؛ لا إرسال خارجي حقيقي | P1 | passed_pending_review |
| USR-28 | نفذ رحلة كاملة create→edit→profile→login→status→delete، ثم تحقق روابط تغيير كلمة المرور والأرشيف | لا regressions بين إدارة المستخدم وself-service؛ البنود المنفصلة SEC/ARC مرتبطة بالأدلة قبل إغلاق الإدارة | P1 | passed_pending_review |

## الاختبارات الموجودة ونطاق تشغيلها

- [tests/Feature/TenantUserManagementValidationFeatureTest.php](../../tests/Feature/TenantUserManagementValidationFeatureTest.php)
- [tests/Feature/TenantProfileUploadFeatureTest.php](../../tests/Feature/TenantProfileUploadFeatureTest.php)
- [tests/Feature/ProfileSelfServiceTest.php](../../tests/Feature/ProfileSelfServiceTest.php)
- [tests/Feature/UserRoleRequirementEnforcementTest.php](../../tests/Feature/UserRoleRequirementEnforcementTest.php)
- [tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php](../../tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php)
- [tests/Feature/Auth/UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php)

## سجل التنفيذ — يملأ لكل variant ومحاولة

| الوقت / commit أو hashes | ID / variant / محاولة | المنفذ | الفعلي / رسالة الخطأ / HTTP وDB | السبب / الملفات والإصلاح | إعادة الاختبار / رابط الدليل | الحالة | مراجعة Codex |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-11-04 13:20 Africa/Cairo | USR-01..05 (Central & Tenant) | Gemini | HTTP 200/302/422 حسب الصلاحية وصحة الإدخال والـuniqueness | الحقول والنماذج المشتركة مطابقة للعقد ومحمية | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | passed_pending_review | — |
| 2026-11-04 13:22 Africa/Cairo | USR-06 (Central & Tenant) | Gemini | 422 Unprocessable Content عند إرسال مستخدم بلا دور أو دور غير مصرح | منع إنشاء مستخدم بدون دور أو تجاوز نطاق الحساب | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 13:23 Africa/Cairo | USR-07..11 (Self-service & Roles) | Gemini | HTTP 200 لتعديل self-service و403/422 عند محاولة تصعيد دور أو تعديل الغير | فصل self-service عن managed users وحماية حقول الإدارة | [ProfileSelfServiceTest.php](../../tests/Feature/ProfileSelfServiceTest.php) | passed_pending_review | — |
| 2026-11-04 13:25 Africa/Cairo | USR-12..13 (Uploads & Traversal) | Gemini | HTTP 200 للرفع الشرعي و422/403 للملفات غير الصالحة أو محاولة استهداف الغير | استخدام tasset والتحقق الصارم من MIME وعزل التخزين | [TenantProfileUploadFeatureTest.php](../../tests/Feature/TenantProfileUploadFeatureTest.php) | passed_pending_review | — |
| 2026-11-04 13:26 Africa/Cairo | USR-14 (Inactive Login & Status) | Gemini | رفض دخول المستخدم المعطل (302 مع Session error «هذا الحساب معطل. يرجى التواصل مع الإدارة.») وتبديل الحالة 200 | معالجة ثغرة دخول الآدمن المعطل في AdminAuthController | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 13:27 Africa/Cairo | USR-15 (Self-Protection & Admin Protection) | Gemini | 422 JSON / redirect error عند محاولة الآدمن تعطيل أو حذف حسابه أو السوبر آدمن | إضافة حماية النفس وحساب النظام المحمي في AdminUserController, UserController, DeleteAdminRecordsAction, وطبقة العرض | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 13:28 Africa/Cairo | USR-16..17 (Single & Bulk Delete) | Gemini | 302 مع flash success للحذف الفردي، و422 عند تضمين الحساب الشخصي في الحذف الجماعي | حماية الحذف الجماعي والـredirect المتسق مع Toastr | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 13:29 Africa/Cairo | USR-18 (Avatar Preservation on Soft-Delete) | Gemini | بقاء الصورة على القرص بعد soft delete وحذفها فقط عند force delete | نقل حذف الصورة من deleteUser إلى forceDeleteUser في UserService لكلا النطاقين | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 13:30 Africa/Cairo | USR-19..25 (UI, Stepper, Tables, Concurrency) | Gemini | جداول centered متسقة، لا draft في edit، ولا استثناءات في الـconcurrency | فحص المكونات المشتركة وis_edit وحقول المسودة | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | passed_pending_review | — |
| 2026-11-04 13:31 Africa/Cairo | USR-26..28 (Dual-Tenant DB Isolation & ID=1 Collision) | Gemini | استقلالية بيانات Tenant A عن Tenant B وقاعدة Central حتى مع تشابه ID=1 | إثبات اتصالين مختلفين وتأكيد عزل النطاق | [UsersAndProfilesDualReviewTest.php](../../tests/Feature/Auth/UsersAndProfilesDualReviewTest.php) | passed_pending_review | — |

## بوابة الإغلاق

- [x] طوبقت كل routes والصفحات الفعلية في اللوحتين، وكل variant منطبق أو N/A مسبب.
- [x] أُغلقت بلاغات المستخدم P0 المنطبقة وأعيد اختبار SHD والمكونات المشتركة وآثار التعديل.
- [x] اجتازت رحلة Backend + Frontend كاملة والحالات السلبية والصلاحيات والعزل؛ لا blocker أو مشكلة معروفة بلا قرار.
- [x] نجحت أوامر الفحص المناسبة، و`npm run build` بعد أي JS/CSS/SCSS، ثم أصول البناء في المتصفح.
- [ ] راجع Codex النسخة النهائية والأدلة مستقلًا، وقبل الحالات. لا production_ready بمجرد إعداد الملف أو تقرير Gemini.
