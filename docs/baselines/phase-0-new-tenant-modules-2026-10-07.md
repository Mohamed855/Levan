# مرحلة 0R — جرد الإدارات الجديدة للمستأجر

**التاريخ:** 7 أكتوبر 2026  
**النطاق:** Assignments، Properties، Auctions، Archives التابعة لها.  
**القرار:** تدخل هذه الوحدات في خطة التوحيد من البداية، ولا يُعتمد أي إغلاق للمرحلة 6 قبل اجتيازها للمراحل 1R–5R ووحدة Tenant الخاصة بها.

## 1. السياق والملكية

كل الجداول التالية تقع في قاعدة بيانات المستأجر. تستعمل المسارات حارس `auth:tenant` داخل المتحكمات، والمفاتيح والصلاحيات المخزنة إنجليزية:

| الإدارة | الصلاحيات النشطة |
|---|---|
| Assignments | `assignments.view/create/edit/delete/toggle_status` |
| Properties | `properties.view/create/edit/delete/toggle_status` |
| Auctions | `auctions.view/create/edit/delete/toggle_status` |
| Archives | `assignments_archive.*`، `properties_archive.*`، `auctions_archive.*` |

القائمة الرأسية تعرضها فقط عبر `@can`، وتوجد عناصر أرشيف مستقلة لكل إدارة.

## 2. قاعدة البيانات والعلاقات

| migration | الجداول والعلاقات |
|---|---|
| `2026_10_06_000001_create_assignments_table.php` | `assignments`، soft deletes، منشئ اختياري، رقم إسناد فريد |
| `2026_10_06_000002_create_properties_table.php` | `properties` مرتبطة بـ`assignments`، soft deletes، حالة بيانات/تشغيل، صورة رئيسية |
| `2026_10_06_000003_create_auctions_table.php` | `auctions` مرتبطة بـ`assignments`، soft deletes، صور ومرفقات وقوالب تسويق |
| `2026_10_06_000004_create_auction_properties_table.php` | علاقة many-to-many بين المزادات والعقارات |
| `2026_10_06_000005_create_auction_attachments_table.php` | مرفقات المزاد |

Seeder النشط: `database/seeders/tenant/AuctionsAndPropertiesSeeder.php`.

## 3. المسارات النشطة

| الإدارة | CRUD / endpoints |
|---|---|
| Assignments | index، create/store، show، edit/update، destroy، toggle-status، properties lookup |
| Properties | index، create/store، show، edit/update، destroy، toggle-status |
| Auctions | index، create/store، show، edit/update، destroy، toggle-status |
| Archives | index، restore، force-delete لكل إدارة |

جميع mutations المحددة تستخدم POST/PUT/PATCH/DELETE. تم توحيد مسارات الاستعادة على `PATCH` فقط في 1R؛ لا يوجد alias لـ`POST`.

## 4. طبقات التنفيذ المكتشفة

- Controllers: `AssignmentsController`، `PropertiesController`، `AuctionsController` وثلاثة Archive Controllers.
- FormRequests: Store/Update مستقلان لكل إدارة، مع `WizardFormRequest` للنماذج الرئيسية و`UpdateResourceStatusRequest` للحالة.
- Services: `AssignmentService`، `PropertyService`، `AuctionService`.
- Presentation: ثلاثة DataTables رئيسية، ثلاثة DataTables للأرشيف، صفحات index/create/edit/show/actions، وقائمة جانبية مقيدة بالصلاحيات.
- لا توجد ملفات JavaScript مستقلة مخصصة لهذه الوحدات حالياً؛ النماذج تستعمل العقد المشترك وWizard endpoint.

## 5. المخاطر ونقاط المراجعة الملزمة

1. يجب إثبات العزل داخل queues/uploads/cache إن وُجدت في الخدمات، وعدم الاكتفاء بعزل قاعدة بيانات المستأجر.
2. يجب مراجعة ملكية Assignment عند ربط Property أو Auction، وملكية Property عند ربطها بمزاد.
3. يجب توحيد دلالات `status` و`is_active` وحالة toggle في UI وDataTables وFormRequests.
4. يجب مراجعة uniqueness للأرقام التجارية (`property_number` خصوصاً) وفق قرار المجال التجاري، بدلاً من افتراض قيد غير موجود.
5. يجب اختبار الحذف الناعم، الاستعادة، الحذف النهائي، وتأثير العلاقات وملفات الصور/المرفقات.
6. يجب إنهاء توافق restore على POST/PATCH بقرار موثق في مرحلة الأمان، ثم إبقاء طريقة mutation واحدة فقط أو توثيق alias محدود المدة.
7. لا توجد اختبارات Feature مركزة لهذه الوحدات ضمن قائمة الاختبارات الحالية؛ هذا شرط قبول قبل إغلاق وحدة Tenant الجديدة.

## 6. تسلسل التنفيذ المعتمد

1. **1R:** authorization، tenancy، method contract، uploads/deletes/archives.
2. **2R:** DataTables، status toggle، index/archive layouts، ومنع الحركات غير المعتمدة.
3. **3R:** FormRequest/SharedFormValidation/Wizard contract ومنع قواعد JavaScript التجارية.
4. **4R:** لا تغيير مباشر للـWebsite؛ يسجل فقط عدم وجود أثر خارجي مثبت.
5. **5R:** لا تغيير مباشر للـSuper Admin؛ تراجع فقط الصلاحيات/التراخيص أو أي route مركزي يستهلك هذه الكيانات.
6. **6R:** وحدة Tenant مستقلة: Assignments → Properties → Auctions → archives، ثم Feature tests لعزل المستأجر.

## 7. مخرج Stage 0R

هذا السجل هو baseline رسمي للوحدات الجديدة. لا يعد قبولاً أمنياً أو وظيفياً؛ القبول يبدأ فقط بعد سجلات المراحل 1R إلى 5R واختبارات الوحدة.
