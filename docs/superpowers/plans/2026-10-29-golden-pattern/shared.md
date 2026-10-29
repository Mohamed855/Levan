# خطة مطابقة العقود المشتركة بين Admin وTenant

> هذا الجزء مكمل للخطة الرئيسية بتاريخ 29 أكتوبر 2026. أستخدم المعرفات S01–S08 لربط أعمال الإدارات A وT بهذه المتطلبات، حتى أصلح المصدر المشترك مرة واحدة وأعيد اختبار الإدارات المستهلكة له. هذه خطة تنفيذ ومقارنة ساكنة، وليست شهادة نجاح وظيفي أو بصري.

## منهج المقارنة وقراراتي

أريد تطبيق تعليمات المشروع في `AGENTS.md` أولًا، ثم العقد المكتوب في `docs/architecture/golden-pattern.md`، ثم الأمثلة الحالية. عند تعارض مثال مع القاعدة سأصلح المثال وأوثق القرار، ولن أنسخ التعارض إلى إدارة جديدة. سأحافظ على أسماء routes ومفاتيح قاعدة البيانات والصلاحيات الإنجليزية والعقود الخارجية القائمة، وأضيف migration جديدة إذا احتجت تغيير schema منشورًا.

أعتبر خطة `docs/superpowers/plans/2026-10-25-standardize-edit-views.md` مرجعًا تاريخيًا متجاوزًا في الأجزاء المتعارضة: تخصيص نص زر الحفظ إلى «تحديث»، وتكرار فورم Create بدل partial واحد، وإعادة إدارات Tenant التي لم تعد reachable من routes الفعلية. لا أطلب تعديل الملف التاريخي أو إعادة تفعيل تلك الإدارات. سأطبق الجرد المرتبط بـ`route-inventory.json` والخطة الحالية.

أحسم التعارضات المكتوبة كالتالي:

1. جداول Show التابعة تظل `table table-hover` داخل `table-responsive` تنفيذًا لتعليمات AGENTS الصريحة الأعلى أولوية، وأسجلها استثناءً من منع `table-hover` في Golden §14. بقية الجداول العادية والفرعية تتبع `table table-striped table-bordered w-100`. توحيد Show لاحقًا على striped يحتاج قرار تغيير قاعدة مستقل، فلا أقدمه كمتطلب حالي. جداول مخرجات الطباعة لها نطاقها الوظيفي المستقل ولا تُغير عشوائيًا.
2. مراجعة الـwizard بعرض كامل، تبدأ بتنبيه، ثم كروت `col-lg-6` والعلاقات/المرفقات العريضة `col-12`. وصف السايدبار `col-lg-9 + col-lg-3` في البطاقات يُحدث.
3. نصوص أزرار wizard الافتراضية ثابتة: «حفظ كمسودة»، «التالي»، «السابق»، «المراجعة»، «حفظ». أوصاف «إنشاء الكيان/تحديث الكيان» لا تسمح بـ`text` مخصص أو slot يلتف على القاعدة.
4. الهوية المرئية هي Levan وفق AGENTS. أحتفظ بألوان النظام المعتمدة `#1D4F44` و`#F7F3EC` ما لم يصدر طلب تصميم مستقل؛ لا أغير معرفات قواعد البيانات أو قنوات الإشعارات بسبب الاسم المرئي.
5. عند استبدال ملف، أبقي القديم صالحًا أثناء transaction، وأثبت مسار الجديد في DB، ثم أحذف القديم بعد نجاح commit. عند فشل transaction أحذف الجديد فقط. فشل تنظيف القديم بعد commit يُسجل ويعاد بمحاولة آمنة؛ لا أقبل فقد ملف صالح لتجنب ملف يتيم. سأصحح الصياغة المتعارضة في §11 قبل تعميمها.

## ما ثبت بالفحص وما يحتاج تشغيلًا

| العقد | الموجود الذي سأحافظ عليه | الفجوة أو حد الدليل |
|---|---|---|
| Form controls | `components/shared/form/layout/field.blade.php` يخرج `data-form-field` و`data-field-name` وerror accessible؛ controls تخرج `data-form-control` | لا تكفي آلية JS التي تضيف attributes تلقائيًا لإثبات التزام كل صفحة بالمكونات؛ يلزم فحص HTML الناتج للصفحات النشطة |
| Stepper | `stepper.blade.php:8–13` يخفي header عندما لا تزيد الخطوات عن واحدة | نفس المكون يقبل ويعرض `subtitle` في `:21,28–30` رغم منع subtitles؛ أزيله من العقد والمستهلكين النشطين |
| Next validation | `form-validation.js:1600–1652` يمنع الطلب المتكرر داخليًا ويستخدم `aria-busy` دون تعطيل Next | توجد رسالة مجال محلية عن `boundary_geojson` في `:1628–1639`؛ مصدر الرسالة يجب أن يكون FormRequest. التعليقات `:1291–1292` قديمة وتناقض التنفيذ الحالي |
| Draft database | `WizardDraft::getConnectionName():17–23` يختار اتصال tenant عند تهيئة السياق؛ migration tenant موجودة | وجود migration لا يثبت تنفيذها في كل tenant؛ اختبار عزل حقيقي مطلوب. اتصال central ثابت `mysql` يحتاج تثبيت اتساقه مع إعدادات التطبيق |
| Draft storage | UUID ومسارات خاصة، والاستعلام مقيد guard/user/tenant/resource | `save():37–46` يخزن الملفات ويحدث DB دون transaction وتعويض عند الفشل؛ `mergeStoredFiles():253–254` يحذف القديم قبل نجاح save |
| Draft safety | استرجاع المسودة يطلب قرار المستخدم في `form-validation.js:803–824` | عند فشل discard ينفذ `finally` فك `inert` في `:873–879`؛ لا يبقى النموذج محجوبًا حتى نجاح بدء جديد كما يطلب العقد |
| Draft deletion | `WizardDraft.php:47–48` ينظف مجلد المسودة عند deleted | يتم حذف سجل DB قبل معرفة نجاح `deleteDirectory`، ولا تُفحص نتيجته. `removeFile():91–96` يتجاهل فشل حذف الملف ثم يمسح metadata |
| Upload validation | `StoreWizardDraftRequest:16–22` يتحقق من resource key والخطوة | لا توجد قواعد نوع/حجم/عدد للملفات في هذا Request؛ الخدمة تستخدم `allFiles()` مباشرة. Requests المسودات الأربعة تفتقد `messages()` و`attributes()` |
| File cards | `file-card.blade.php` والمقاسات `150px` في `_file-cards.scss:24–25` موجودان | `form/controls/file.blade.php:89–112` و`file-upload.js:114–165` يبنيان معاينات `.file-item` مستقلة بدل file-card؛ تتكرر المخالفة من المصدر المشترك |
| File URLs | `data-table/image.blade.php` يوفر صورة دائرية وfallback | `file-card.blade.php:22` يختار tasset بمجرد وجود الدالة، لا حسب boundary؛ `TenantHelper.php:22–29` يرجع إلى public disk عند غياب الملف في tenant disk. هذا خطر اختلاط مسارات يتطلب اختبارًا؛ لم أثبت تسربًا حيًا |
| Tables | `Abstract/AbstractDataTable.php:244` يوفر `renderTableImage`، وshared status handler محمل في layout | `data-table/page.blade.php:3–4` يعلن `tableClass/responsive` دون استعمالهما على جدول أو wrapper؛ المكون لا يفرض العقد بنفسه. اعتماد إصلاح responsive لاحقًا بالـJS لا يغني عن HTML أولي صحيح |
| Selection | `datatable-page-defaults.js:32–67` يحدد wrapper عند التحديد ويلغي select-all عند draw | delegation مربوط على document حاليًا؛ أوحّده بحيث تكون handlers معزولة لكل wrapper، دون ازدواج handlers مع الصفحات أو اختيار سجلات جدول آخر |
| Visual shell | `_menu.scss` يحتوي إخفاء scrollbar، ارتفاع 3rem، فراغ 0.35rem وRTL؛ `_variables.scss:68–79` يحتوي السلم المطلوب | العناوين في `admin/layouts/commonMaster.blade.php:32` و`tenant/layouts/commonMaster.blade.php:31` ما زالت LOT؛ توجد أحجام محلية خارج bands. لا توجد معاينة متصفح تثبت النتيجة النهائية |
| Shared inbox | القنوات تشمل scope/tenant/user، وقراءة السجلات مقيدة بعلاقة إشعارات المستخدم | `DashboardNotificationController:read/readAll/destroy` يكتب مباشرة، و`getAdminSidebarCounts` يحمل queries/cache داخل Controller؛ Requests لا تكمل messages/attributes، وfilter لا يمر عبر validated input |
| Boundary | Gateways موجودة في المشروع وعقد ساكن يحرس الاستيراد | `TenantTwoFactorSettingsController.php:9,74–76` يستورد Central Tenant ويقرأه داخل `Tenancy::central` مباشرة؛ الخدمة الصريحة مطلوبة حتى لو كان الاتصال نفسه صحيحًا |

أرقام الأسطر توثق لقطة القراءة الحالية وقد تتغير مع التنفيذ. لا أصنف اختلاف quotation أو نقل markup إلى partial باعتباره عطلًا وظيفيًا، ولا أعتبر نجاح اختبار نصي بديلًا عن اختبار المتصفح أو عزل قاعدة البيانات.

## S01 — أوحد مصدر القواعد وجرد المطابقة

**الأولوية:** P0 قبل اعتماد أي حالة Compliant. **التبعيات:** لا شيء؛ ينفذ قبل إغلاق أي إدارة.

**الملفات التي سأعدلها عند التنفيذ:**

- `docs/architecture/golden-pattern.md` لحسم التعارضات المذكورة أعلاه وتصحيح خريطة المسارات والـview boundary إلى المسارات الفعلية.
- `docs/baselines/golden-pattern-manifest.md` لإعادة الجرد من routes الحالية، وتحديث الأعداد والروابط والحالات ودليل كل إدارة.
- بطاقات `docs/baselines/superadmin-administration-cards/` و`tenant-administration-cards/` التي يغطيها جرد الخطة؛ بطاقة واحدة لكل عائلة قرار مستقلة، دون فرض عدد قديم.
- `tests/Unit/SuperAdminGoldenPatternInventoryTest.php` و`TenantGoldenPatternInventoryTest.php` و`GoldenPatternScopeContractTest.php`.

**المطلوب مني:**

- [ ] أوفق بين `route-inventory.json` والجرد؛ أميز route alias عن endpoint مختلف، وأدرج الإدارات الحديثة مثل Clients/AuctionTemplates وعمليات AuctionReport/AuctionOutput، وأستبعد ملفات legacy غير reachable دون حذفها ضمن هذا العمل.
- [ ] أحول حالة `implemented` إلى واحدة من `Gap/Partial/Compliant` وفق الأدلة الفعلية. لا أجبر طلبات التجربة على Gap للأبد لأن اختبارًا قديمًا كتب ذلك، ولا أرفعها بناء على الوصف وحده.
- [ ] أستخدم الحقول الكاملة للبطاقة، بما فيها N/A المسبب، وأسمّي الفجوة ومالكها واختبار إغلاقها؛ ألغي وصف «audit pending» كبديل دائم عن تحليل إدارة فعلية.
- [ ] أحدث اختبارات الجرد لتطابق الفهرس الفعلي وتكشف route/صفحة بلا بطاقة. أفصل التحقق من schema البطاقة عن اختبار سلوك الإدارة؛ وجود اسم اختبار داخل markdown ليس دليل نجاحه.
- [ ] أضيف اختبار قبول `test_every_active_dashboard_family_has_a_complete_manifest_card` واختبار `test_manifest_counts_match_normalized_active_route_families` بعد تطبيع aliases، وأثبت عدم احتساب الموقع العام عائلة dashboard إلا قدراته الإدارية الموجودة في Admin.

**القبول:** كل endpoint في الجرد له إدارة أو عقد مشترك محدد، ولا توجد أعداد متعارضة أو حالة خارج enum المتفق عليه؛ روابط الملفات صحيحة؛ لا توجد بطاقة تعلن نجاحًا لم يُشغل. أشغل اختبارات الجرد الساكنة المحددة فقط قبل توسيع بوابة التحقق.

## S02 — أضبط FormRequest والمكونات والـWizard

**الأولوية:** P1؛ يخدم جميع نماذج الإنشاء والتعديل. **التبعيات:** قرارات S01، وبيئة الاختبار S08 قبل Feature tests.

**الملفات الحالية:** `app/Http/Controllers/Shared/Wizard/WizardDraftController.php`، `app/Http/Requests/Shared/Wizard/ValidateWizardStepRequest.php`، `app/Http/Requests/Concerns/WizardFormRequest.php`، `resources/assets/js/shared/form-validation.js`، ومكونات `resources/views/components/shared/form/{controls,layout,wizard}/`، و`tests/Unit/SharedFormValidationContractTest.php`.

**الواجهة التي سأحافظ عليها:** `validateStep` يستقبل `target_path/target_method/fields`، ويختار FormRequest الحقيقي ويمرر files/session/user/route model binding، ويستعمل `rules/messages/attributes` ثم يرجع `success` أو 422 و`errors`. كل control يستخدم الثلاثية `data-form-field/data-field-name/data-form-control` ومخرج خطأ مرتبطًا بـaria.

- [ ] أختبر `test_step_validation_uses_only_current_step_fields_and_real_request_messages`: رفض حقل مطلوب في الخطوة الحالية، وعدم رفض حقل مطلوب في خطوة لاحقة، وتطبيق rules الخاصة بالمصفوفات دون توسيع التحقق لكل جذر بشكل يحقق حقول خطوة أخرى.
- [ ] أختبر `test_step_validation_rejects_another_boundary_or_unauthorized_target`: تعديل `target_path` إلى Admin من Tenant أو سجل مستخدم آخر لا يكشف رسائل uniqueness أو بياناته؛ allow-list صريحة لكل boundary، مع policy/ownership للـupdate target، ولا اعتماد على مجرد كون method اسمها store/update. عند فشل implicit binding لا أسكت وأتابع بسجل خام يسمح بتجاوز النطاق.
- [ ] أراجع ترتيب `prepareForValidation/authorize` والتطابق مع final FormRequest وأثبت تطابق نتيجة كل منهما مع الطلب النهائي للبيانات نفسها. shared endpoint ليس إعادة تنفيذ لقواعد business متفرقة.
- [ ] أنقل رسالة `boundary_geojson` إلى FormRequest؛ أحتفظ بالتحقق HTML5 المساعد فقط عندما لا يناقض قواعد الخادم، ولا أضيف business regex أو رسائل مجال جديدة داخل JS. أثبت أن تأكيد كلمة المرور يعيد التحقق من الحقل المرتبط عند التغيير.
- [ ] أمنع عرض subtitles وألغي تفعيل تخصيص نصوص الأزرار في الاستعمالات النشطة، بما فيها slots. أحتفظ بتمييز Next/Review كحالتين قياسيتين يختارهما المكون، وبالسهم الصحيح في RTL. لا أغير native type=submit للحفظ النهائي.
- [ ] أجعل نموذج Create/Edit يستخدم partial واحدًا ومتغير `$is_edit`، وأثبت أن old input يسبق قيمة السجل، وأن disabled fields المطلوبة لها hidden fallback صحيح دون ازدواج قيم مربك. لا أضيف wizard لنموذج أحادي الخطوة.
- [ ] أستبدل assertions النصية الحساسة للاقتباس والمسافات باختبار DOM/render أو اختبار سلوك. فحص create يجب أن يتتبع include إلى `_form` بدل المطالبة بوجود markup مكرر في create.

**القبول:** Next/Review متاحان بصريًا دائمًا، ضغطة واحدة أثناء انتظار السيرفر تعطي طلبًا واحدًا، والفشل يبقي الخطوة والأخطاء بجوار الحقول. لا `disabled` ولا `.disabled` على أزرار Next/Review أثناء التحقق؛ يمكن بقاء قفل الحفظ النهائي لمنع إنشاء مكرر. اختبار `test_single_step_renders_no_stepper_header` واختبار `test_create_and_edit_share_the_same_step_structure` واختبار `test_password_confirmation_revalidates_its_matching_password_field` تمر بنتيجة سلوكية مثبتة.

## S03 — أجعل المسودات والملفات قابلة للاسترجاع والتنظيف الآمن

**الأولوية:** P0 بسبب احتمال فقد ملف قديم أو بقاء ملفات غير متتبعة. **التبعيات:** S01/S02 وS08، ثم تطبيق دورة الإنشاء في كل إدارة.

**الملفات الحالية:** `WizardDraftService.php`، `WizardDraft.php`، `DiscardCompletedWizardDraft.php`، Requests المسودات الأربعة تحت `app/Http/Requests/Shared/Wizard/`، `WizardDraftController.php`، migrations المركزية وtenant الخاصة بـ`wizard_drafts`، `form-validation.js`، `tests/Feature/WizardDraftCleanupTest.php` و`tests/Unit/DiscardCompletedWizardDraftTest.php`.

**الواجهات:** أحافظ على `save(Request,string,int): WizardDraft`، `find(Request,string,?string): ?WizardDraft`، `discard(Request,string): bool`، `removeFile(Request,string,string): bool` و`mergeIntoRequest(Request,string,string): array` للمستهلكين الحاليين. أوضح في التنفيذ حالات no-op والمفقود والفشل، وأضيف نتيجة خدمة صريحة إذا احتجت الفصل بينها دون تغيير مبهم للاستجابة.

- [ ] أربط guard بسياق route الموثوق وتهيئة tenancy، لا بأولوية `auth('admin')->check()`؛ جلسة Admin وجلسة Tenant متزامنتان يجب ألا تنسب مسودة Tenant إلى Admin. المورد يُحل من registry/route server-side مع صلاحية إنشائه، وليس resource key حرًا يُستخدم كصلاحية.
- [ ] أكمل `authorize/rules/messages/attributes` لكل Request. حفظ مسودة ناقصة لا يفرض required fields النهائية، لكنه يفرض نفس قواعد أمان الملفات: mime/extension/size/count وحدود الموارد المستخرجة من Request المورد، ورفض uploads غير معلنة. لا أحفظ كلمات المرور أو tokens/API secrets أو OTP/recovery codes داخل draft_data.
- [ ] أثبت أن قاعدة Tenant تُستخدم فعليًا، وأن user/resource IDs المتطابقة في A/B لا تتداخل. أختبر أيضا المسار domain والمسار path، وCentral بدون tenancy مهيأة. لا أعالج غياب جدول Tenant بالرجوع إلى الجدول المركزي.
- [ ] أجعل تحديث metadata والملفات عملية متسقة: أحفظ ملفات جديدة مؤقتة، أُحدث السجل داخل transaction مع lock/uniqueness الصحيحة، أنظف الجديد عند rollback، وأحذف المستبدل بعد commit. أختبر ضغط زر الحفظ من تبويبين دون duplicate أو فقد آخر تعديل ناجح.
- [ ] أصلح معنى دمج المصفوفات: `array_replace_recursive` الحالي قد يبقي عناصر محذوفة عند تقصير list؛ أتعامل مع list مرسلة كصورة حديثة كاملة لذلك الحقل، وأميز omission عن مسح القيمة، وأثبت unchecked boolean والعلاقات المحذوفة. لا أعد حقولًا حذفها المستخدم بمجرد الاسترجاع.
- [ ] لا أعتبر ملفين متطابقين بناء على الاسم والحجم وMIME فقط (`fileSignature:262–264`)؛ أستخدم identity للملف المؤرشف أو hash محتواه عند الحاجة لمنع duplicate، مع الاحتفاظ بملفين مختلفين لهما الاسم والحجم نفسيهما.
- [ ] أجعل discard/removeFile يتحققان من نجاح التخزين. لا أمسح سجلًا أو metadata يؤكدان وجود ملف قبل إثبات حذف الملف أو تسجيل cleanup قابل للاستئناف. عند فشل «تجاهل والبدء من جديد» يبقى النموذج في حالة حجب واضحة مع إعادة المحاولة؛ لا يفك `finally` الحجب ويتيح بداية جديدة صامتة.
- [ ] أستبدل استنتاج نجاح الإنشاء من POST+redirect+session success وحده بإشارة صريحة من عملية store بعد commit، لأن flash قديمًا أو POST خاصًا بالتعديل ليس دليل إنشاء جديد. أضيف اختبار JSON create success أيضًا؛ الحذف التلقائي الحالي لا يغطيه.
- [ ] أنقل/أعتمد الملفات النهائية أولًا بعد نجاح transaction، ثم أنظف المسودة؛ الفشل يحتفظ بمسودة قابلة للاستعادة. endpoint تنزيل ملف المسودة يتحقق من owner والboundary ويقدم private/no-store وdisposition/type آمنين.
- [ ] أضيف `config/wizard.php` و`app/Console/Commands/CleanupExpiredWizardDrafts.php` عند عدم وجود بديل فعلي؛ أوثق `wizard.drafts.retention_days=30` كقرار مقترح لهذه الخطة، وأنفذ cleanup مجدولًا لكل قاعدة Tenant والمركزي باستعلامات chunks وtenant context واضح، مع retry وفشل قابل للمراقبة. لا أشغل cleanup على البيانات الحالية أثناء المراجعة.

**اختبارات القبول التي سأضيفها:** `test_discard_storage_failure_keeps_draft_and_blocks_new_start`، `test_draft_upload_rejects_disallowed_type_without_full_creation_requirements`، `test_failed_metadata_save_keeps_old_file_and_removes_new_file`، `test_removed_nested_rows_do_not_return_after_restore`، `test_same_filename_and_size_different_content_are_both_preserved`، `test_drafts_use_route_guard_when_both_guards_are_authenticated`، `test_json_store_discards_only_its_committed_draft`، `test_expiry_cleanup_runs_in_each_tenant_without_crossing_databases`.

**القبول:** الاسترجاع يعيد scalar/Select2/arrays/relations/files/step؛ الإلغاء والفشل والنجاح لا يفقدون ملفات صالحة؛ purge محدود بالمورد والمالك والسياق الصحيح، والتنظيف له دليل اختبار فعلي.

## S04 — أوحد بطاقات الملفات ومسارات العرض والتنزيل

**الأولوية:** P1، وترتفع اختبارات العزل إلى P0. **التبعيات:** S03 لتجربة ملفات draft، S08 قبل اختبارات التخزين.

**الملفات الحالية:** `resources/views/components/shared/ui/{file-card,attachment-card}.blade.php`، `form/controls/{file,image-upload}.blade.php`، `resources/assets/js/shared/file-upload.js`، `resources/assets/scss/shared/_file-cards.scss`، `app/Helpers/TenantHelper.php`، `data-table/image.blade.php`، وrenderers المحلية النشطة التي تحيل إليها خطط الإدارات.

- [ ] أجعل file-card المصدر الوحيد لـmarkup، وأعرض ملفات الرفع الجديدة من template ناتج من نفس المكون مع تعبئة آمنة للقيم في JS. لا أنشئ نسخة HTML أخرى تسمى `.file-item` في file-upload أو صفحة المراجعة.
- [ ] أبدل markup الملفات المحفوظة في file control، وأحافظ على existing-file remove flags وعقود FormRequests الخاصة بها، وعلى metadata والملفات المؤرشفة وإعادة ترتيبها.
- [ ] أحافظ على حجم 150×150 وأصلح grid minimum الحالي 140px حتى لا تتجاوز بطاقة 150px عمودها في المساحات الضيقة. أختبر keyboard/focus وhover action، لأن المعاينة لا يجوز أن تختفي عن مستخدم لوحة المفاتيح.
- [ ] أحدد boundary بناء على سياق موثوق؛ Central يستخدم `asset('storage/'.$path)` عند الملفات العامة المعتمدة، وTenant يستخدم `tasset($path)` لملفاته. لا أستخدم `function_exists('tasset')` لاختيار نطاق ملف مركزي، ولا أسمح fallback إلى public disk لملف Tenant خاص لمجرد أن الاسم موجود هناك؛ الملفات المشتركة العامة تمر allow-list صريحة.
- [ ] أستخدم download endpoint محميًا للملفات الخاصة بدل direct storage URL؛ fallback للشعار هو أصل عام معتمد فقط. URL كامل خارجي يُقبل من مصدر موثوق حسب عقد المورد؛ لا أقبل `javascript:` أو protocol غير مصرح به من بيانات السجل.
- [ ] أستبدل raw `$deleteAction` HTML بخصائص data آمنة وattributes بنيوية؛ الاسم الأصلي وmetadata وعنوان التنزيل تُهرب. أعرّف تنزيل `downloadUrl` فعليًا أو أزيل prop غير المستخدم ضمن تعديل العقد، ولا أوهم المستهلك بميزة غير معروضة.

**القبول:** `test_all_upload_states_render_the_shared_file_card` يغطي جديد/محفوظ/مسودة/Review/Show، و`test_tenant_missing_file_never_falls_back_to_central_private_file` يغطي نفس الاسم بمخزنين مختلفين، و`test_attachment_names_and_delete_metadata_are_escaped` يغطي اسمًا عربيًا ونص HTML واقتباسات. أختبر PDF وصورة وملف غير قابل للعرض، وملف مفقود، وأبعاد 150px على viewport 360/768/1440.

## S05 — أوحد الجداول والصور والحالة والتحديد الجماعي

**الأولوية:** P1؛ مراجعة صلاحية كل سجل P0. **التبعيات:** S01، وS04 لعرض الصور، وتفاصيل Policies الإدارات.

**الملفات الحالية:** `app/DataTables/Abstract/AbstractDataTable.php`، `resources/views/components/shared/data-table/{page,archive-page,image,filters}.blade.php`، `resources/assets/js/shared/{table-status-toggle,datatable-page-defaults,datatable-responsive,datatable-primary-actions,datatable-feedback}.js`، `_tables.scss`، `config/models.php`، `tests/Unit/DataTableContractTest.php` و`DataTableDetailsControlContractTest.php`.

- [ ] أجعل component الجدول يفرض wrapper والclasses وقت render؛ أو ألغي props غير المستعملة وأجعل table slot يستقبل إعدادًا موحدًا موثقًا. لا أحمل shared JS مسؤولية إصلاح بنية HTML خاطئة بعد ظهور الصفحة.
- [ ] أربط select-all وrow-checkbox على wrapper محدد بـnamespace واحد، وأزيل تكرار handlers المحلي عند migration كل إدارة. أقرر أن تحديد الصفحة/نتائج العرض لا يعني تحديد جميع سجلات السيرفر، وأعيد reset مع draw، وأختبر وجود جدولين في الصفحة نفسها.
- [ ] أثبت ترتيب control/hidden id/checkbox/business/status/actions. أستخدم image column مستقلًا و`renderTableImage`، مع Tenant logo ثم platform logo وescape/alt/lazy loading. أفصل toggle النشاط عن workflow badge.
- [ ] أبقي handler AJAX المشترك، وأوحد الخادم في `app/Services/Shared/ActivityStateService.php` بواجهة `set(Model $record, string $field, bool|string $value): bool|string`؛ field وقيمه تأتي من إعداد المورد الموثوق وRequest المتحقق، ولا يأتي Model class أو اسم العمود حرًا من المتصفح. Policy وصلاحية العملية تبقيان خاصتين بالمورد قبل الاستدعاء.
- [ ] أستبدل `new_status` التدريجي بعقد `success/message/status|is_active` مع توافق مؤقت موثق؛ errors ترد 403/422/500 برسائل آمنة، وتعود حالة السويتش للسيرفر بعد الفشل. أختبر double-click وresponse خارج الترتيب.
- [ ] أتأكد من أن السماح بالحذف الجماعي في config/models لا يلغي Policy لكل سجل، وأن mixed IDs من Tenant آخر أو IDs محذوفة تعطي عقد فشل محددًا؛ إما transaction كاملة أو نتائج جزئية موثقة. لا أغير سياسة المجال بلا اختبار لكل إدارة.
- [ ] أراجع `rawColumns()` وescape/search/filter allow-lists وeager loading، وأثبت أن filters تبقى query-side. أراجع fallback والـempty/loading/error وcopy-code من المصدر المشترك بدل CSS/JS محلي مكرر.

**القبول:** `test_two_tables_keep_selection_isolated_after_draw`، `test_activity_toggle_uses_record_policy_and_canonical_response`، `test_workflow_enum_is_never_mutated_by_activity_switch`، `test_mass_delete_authorizes_every_record_in_current_boundary`، `test_table_image_fallback_uses_current_boundary_logo`، مع smoke browser لزر الإضافة الحصري في toolbar وأزرار archive بلا Add. أحافظ على رسائل DomainException المسموح بها كرسائل مجال مترجمة، وأمنع تفاصيل Exception التقنية؛ regex عام على `getMessage` وحده لا يحسم ذلك.

## S06 — أوحد الغلاف البصري والهوية والخطوط وRTL

**الأولوية:** P2 بعد استقرار العقود الوظيفية؛ تطبق التغييرات من المصدر المشترك. **التبعيات:** S01/S04/S05، ثم visual regression لكل إدارة.

**الملفات الحالية:** `resources/views/admin/layouts/{commonMaster,layoutMaster,print}.blade.php`، `resources/views/tenant/layouts/{commonMaster,layoutMaster}.blade.php`، `resources/views/components/shared/layout/`، `resources/assets/scss/shared/{_variables,_menu,_forms,_tables,_cards,_file-cards}.scss`، و`config/app.php` إذا استلزم default الاسم المرئي.

- [ ] أستبدل LOT المرئي في page titles/alt والنصوص المشتركة بـLevan مع الحفاظ على أصول الشعار المعتمدة وعدم اختراع شعار جديد ضمن المطابقة.
- [ ] أوحد typography عبر tokens: 0.75rem للمساعد، 0.9375rem للنص/الحقول/الأزرار/الجداول، 1.125rem لعناوين الكروت، 1.5rem للعنوان/المودال؛ أترك h2=38px وh1=46px والأيقونات كما ينص العقد. لا أستبدل كل قيمة رقمية بالبحث الآلي لأن icon sizing والرسومات والطباعة لها معنى مختلف.
- [ ] أعالج overrides النهائية التي تغير body/table/helper bands في `_forms/_tables/_cards/_tickets` بعد التحقق من cascade. ألتزم بمصدر مشترك وأمتنع عن SCSS خاص بكل إدارة لإخفاء اختلاف محلي.
- [ ] أتحقق من إخفاء scrollbar sidebar مع wheel/keyboard navigation، وارتفاع روابط submenu=48px والفاصل 0.35rem والأقواس وعدم تصادم active/hover. أتأكد من أن root dir=rtl يضع scrollbar يسارًا دون إفساد العناصر LTR مثل الأكواد.
- [ ] أنفذ مصفوفة مراجعة بصرية على Admin وTenant: index/create/edit/review/show/archive، والحالات empty/error/long Arabic labels/no image/no attachment، وأحجام viewport 360/768/1440 وzoom 200%؛ أحتفظ بلقطات وأي فرق مسمى في بطاقة الإدارة.

**القبول:** `npm run build` ينجح بعد كل حزمة تغيير assets، ولا horizontal overflow عام أو scrollbars ظاهرة في القائمة أو نص مقصوص يمنع الإجراء. لا أغلق هذا البند باختبار وجود `direction:rtl` في ملف فقط؛ أحتاج computed styles ولقطات فعلية.

## S07 — أنقل منطق الإشعارات والمصادقة المشتركة إلى طبقاته

**الأولوية:** P0 لحدود guard/owner، وP1 لفصل المسؤوليات. **التبعيات:** S08، وربطها بإدارتي Auth/Notifications في Admin وTenant.

**الملفات الحالية:** `app/Http/Controllers/Shared/DashboardNotificationController.php`، `app/Http/Requests/Shared/{DashboardNotificationRequest,DashboardBroadcastAuthRequest}.php`، `app/Services/Shared/Notifications/DashboardNotificationService.php`، `app/Http/Controllers/Tenants/Auth/Fortify/TenantTwoFactorSettingsController.php`، `resources/views/shared/dashboard-notifications.blade.php`، `resources/assets/js/shared/dashboard-notifications.js`، `tests/Feature/DashboardNotificationsTest.php`، وعقد `TenantCentralBoundaryContractTest`.

- [ ] أضيف `ManageDashboardNotificationsAction::markRead(string $scope, ?string $tenantId, int $userId, string $notificationId): void` و`markAllRead(...): int` و`delete(..., string $notificationId): void`؛ أضع العمليات داخل علاقة مستخدم موثقة وscope صحيح مع التحقق من السجل، وأبقي Controller للتنسيق والاستجابة فقط. أحافظ على route UUID وقنوات البث الحالية.
- [ ] أنقل feed/index queries والإحصائيات المركزية إلى service/query مخصصة: `DashboardNotificationQuery::paginateFor(string $scope, ?string $tenantId, int $userId, string $filter, int $perPage=30)` و`AdminSidebarCountService::getCounts(): array`. أرجع DTO/arrays عوض تمرير Central Eloquent models إلى Tenant.
- [ ] أتحقق من filter عبر FormRequest بـ`all/read/unread` وأكمل messages/attributes، وأوحد الاستجابة `success/message` دون كسر المستهلك الحالي. لا أضيف permission للمستخدم لقراءة إشعاراته الشخصية إذا كانت قدرته قائمة أصلًا؛ ownership والمستخدم النشط أساس هذا المورد.
- [ ] أعزل `TenantTwoFactorSettingsController::isEnforced()` خلف `app/Services/Shared/Integrations/CentralTenantSecurityGateway.php` بواجهة `isTwoFactorRequired(string $tenantId): bool`؛ القراءة المركزية داخل Gateway والسياق صريح، مع بقاء فرض إعداد Tenant المحلي أيضًا. أحظر تعطيل 2FA عند أي فرض مركزي أو محلي.
- [ ] أختبر أن tenant A/user1 لا يقرأ أو يحذف UUID user2/B، وأن Admin session متزامنة لا تغير scope، وأن broadcast auth يقبل قناته فقط ويرفض tenantId/userId مزورين. أحافظ على no-store/private ومنع deep links غير المصرح بها.
- [ ] أختبر after-commit delivery وidempotency/retry وtenant context reset عند الفشل؛ لا أعيد إرسال notification بسبب تكرار حدث أو worker. أراجع بيانات sidebar للمستخدم صاحب الصلاحية دون كشف counts لإدارة لا يحق له رؤيتها.

**القبول:** لا write مباشر في Shared Controller ولا Central Model import في Tenant Controller؛ 403/404 عند محاولة عبور الملكية، وطلبات readAll لا تمس scope آخر، وnotification feed يعمل دون Reverb كقناة عرض fallback متفق عليها. tests المستقبلية `test_notification_mutations_authorize_scope_and_owner` و`test_dual_guard_sessions_do_not_cross_dashboard_scope` و`test_tenant_two_factor_enforcement_reads_central_policy_through_gateway` تثبت السلوك.

## S08 — أثبت المطابقة ببوابات تحقق آمنة وذات معنى

**الأولوية:** P0 قبل أي Feature/DB test أو اعتماد. **التبعيات:** يستهل التنفيذ ويعاد عند إغلاق كل إدارة.

`phpunit.xml` الحالي يحدد `DB_CONNECTION=mysql` و`DB_DATABASE=etmam_levan`. لذلك لن أعتبر `APP_ENV=testing` ضمان عزل. بعض Feature tests الموجودة تستخدم مستخدمًا فعليًا وتكتب سجلات ثم تحذفها في teardown، ومنها `WizardDraftCleanupTest`. لن أشغلها على قاعدة العمل لمجرد أن اسمها Test.

- [ ] أجهز central database خاصة بالاختبار، وTenant A/B مخصصين للاختبار، وStorage fake/مجلدًا منفصلًا، وqueue/mail/broadcast/caches معزولة. أضيف preflight يفشل إذا تطابق اسم/DSN قاعدة الاختبار مع قاعدة التشغيل؛ لا أطبع credentials في أدلة التحقق.
- [ ] أنقل Feature tests التي تعتمد على `superadmin@gmail.com` أو بيانات موجودة إلى factories/fixtures داخل القاعدة المعزولة، وأوثق lifecycle teardown دون حذف بيانات خارج fixture scope. أوامر migrate fresh لا تنفذ إلا داخل بيئة الاختبار المثبتة.
- [ ] أكتب test فاشل لكل فجوة قبل إصلاحها؛ أميز فشلًا يكشف عيبًا سلوكيًا عن فشل assertion هش. لا أعدل اختبارًا لمجرد جعله أخضر دون إثبات أن العقد ما زال محميًا باختبار أفضل.
- [ ] أشغل اختبارات S/E الخاصة بالإدارة أولًا، ثم العقود الأفقية المتأثرة، ثم browser checks، ثم full suite المعزول وbuild عند دمج الحزمة. أسجل الأمر والتاريخ وcommit والنتيجة وعدد الاختبارات والفشل في البطاقة.
- [ ] أحدث الحالة إلى Partial أثناء تعديل العقد ثم Compliant فقط بعد استكمال الدليل؛ الإدارة التي تحتاج تحققًا بصريًا أو عزلًا غير منفذ تظل Partial، حتى إذا نجحت static tests.

### الدليل الذي شغلته أثناء إعداد الخطة

نفذت اختبارات PHPUnit الساكنة التالية مباشرة، وهي ممتدة من `PHPUnit\Framework\TestCase` ولا تهيئ قاعدة التطبيق:

```powershell
php vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter 'SharedFormValidationContractTest|DataTableContractTest|CreateFormDraftContractTest|TenantCentralBoundaryContractTest|ResourcesBoundaryContractTest|TenantGoldenPatternInventoryTest|SuperAdminGoldenPatternInventoryTest' tests/Unit
```

النتيجة الفعلية في 29 أكتوبر 2026: **24 اختبارًا، 1450 assertions، 7 failures، exit code 1**. هذه النتيجة لا تمثل فشل سبع إدارات ولا تغطية وظيفية لكل الصفحات:

| الاختبار الفاشل | تصنيفي وخطوة الإغلاق |
|---|---|
| CreateFormDraft: زر العودة في أول خطوة | الاختبار يقرأ create مباشرة؛ الفورم انتقل إلى `_form`. أفحص DOM الناتج وعقد href بدل فرض تكرار markup |
| DataTable: safe messages | يرفض أي `getMessage` حتى `DomainException` برسالة مجال. أفصل رسالة domain المعتمدة عن exception تقنية؛ مراجعة handlers الأخرى مطلوبة أيضًا |
| Shared validation: next button | assertion يطلب single quotes/سطرًا بتنسيق معين؛ التنفيذ يستخدم double quotes وتنسيقًا متعدد الأسطر ويظهر Next متاحًا. أستبدل بالفحص السلوكي |
| Shared validation: password confirmation | assertion نصي بنفس مشكلة quotation؛ الدالة `passwordConfirmationDependencies` موجودة وتُستعمل. لا أدعي تعطل التأكيد دون اختبار سلوكي |
| SuperAdmin inventory: schema | بطاقة public trial لا تستخدم أسماء الحقول المطلوبة؛ أصحح schema مع الحفاظ على وصف flow الحقيقي |
| SuperAdmin inventory: status | البطاقة تستخدم implemented، والاختبار يفرض Gap لكل ما عدا ثلاث إدارات قديمة. أعيد بناء التوقع من الأدلة والحالات المعتمدة |
| Tenant/Central boundary | مخالفة حقيقية باستيراد Central Tenant داخل TenantTwoFactorSettingsController؛ أغلقها عبر Gateway كما في S07 |

لم أشغل Feature tests أو migrations أو full suite أو build أو معاينة متصفح أثناء إعداد هذا الجزء. لا يلزم build لملف خطة Markdown.

## مخاطر المراجعة الخمسة التي لن أتركها ضمنية

1. **جلسة guard مزدوجة أو tenant context متبقٍ:** أختبرها في S03/S07؛ الهوية مصدرها route موثوق والسياق الحالي، ولا تتغير لمجرد وجود جلسة أخرى.
2. **فشل التخزين بعد/قبل DB commit:** أختبره في S03/S04؛ الملف القديم يبقى قابلًا للاستخدام، والجديد أو cleanup المتعثر له تتبع وإعادة محاولة.
3. **مصفوفة أقصر أو checkbox غير محدد أو ملفان بالاسم والحجم نفسيهما:** أختبرها في S03؛ الاسترجاع يعكس اختيار المستخدم الحالي ولا يحذف محتوى مختلفًا أو يعيد صفًا محذوفًا.
4. **استجابة validation بطيئة/قديمة وضغط Next متكرر:** أختبرها في S02/S05؛ نتيجة قديمة لا تمسح خطأ أحدث أو تنقل أكثر من خطوة، والحالة المرئية للزر تلتزم بالعقد.
5. **اختبارات نصية خضراء مع واجهة أو عزل معطلين:** أفصل static/Feature/browser evidence في S08، وأطلب screenshots وcomputed styles وTenant A/B tests قبل Compliant.

## ترتيب التنفيذ الذي سأعتمده

1. S08 لعزل الاختبار، وS01 لتوحيد المرجع والجرد.
2. S03 وS07 لإغلاق مخاطر الملفات والحدود والصلاحيات، مع الحفاظ على flow الإدارات الحالية.
3. S02 وS04 لتوحيد النماذج والمعاينات، ثم تطبيقهما في كل إدارة مستهلكة.
4. S05 للجداول والحالة والتحديد، ثم S06 للمراجعة البصرية والهوية والسلم.
5. أراجع كل إدارة وفق الجزء الخاص بها A/T، وأربط نتائجها بهذه المعرفات. لا أعتبر إصلاح shared component دليلًا تلقائيًا على مطابقة كل مستهلك؛ أغلق بطاقة الإدارة بعد اختبار صفحاتها وأعمالها الفعلية.
