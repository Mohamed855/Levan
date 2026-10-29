# سجل تنفيذ خطة Golden Pattern الشاملة

الخطة: [الخطة الرئيسية](../superpowers/plans/2026-10-29-golden-pattern-remediation.md) وملحقات Admin/Tenant/Shared. تاريخ بدء هذه الدفعة: 29 أكتوبر 2026. الفرع: `codex/golden-pattern-complete-20261029`.

## تأكيد النطاق

قارنت handlers الحالية من `php artisan route:list --json` بالجرد المرتبط بالخطة قبل البدء: لم تظهر handlers للوحتين خارج الخطة. النطاق يشمل SuperAdmin وTenant وكل الصفحات النشطة والعمليات التابعة، ولا يقتصر على الإسنادات والعقارات والمزادات. هذه مطابقة لنطاق التنفيذ؛ لا تعني نجاح فحص بصري لكل صفحة.

## الدفعة الأولى: إصلاحات الأمن وسلامة البيانات والأعطال المباشرة

| المرجع | ما نُفذ | الدليل | حدود الإغلاق |
|---|---|---|---|
| A02 / R01 | فصل reset الإداري إلى Request/Action مركزيين، وتفويض تعديل المستأجر قبل تغيير كلمة المرور؛ الحفاظ على تدفق OTP | ScopedPasswordResetAndFaqAuthorizationTest وTenantAuthenticationContractTest | لا تعني اكتمال جميع صفحات المصادقة و2FA |
| A21 / R13 | فصل صلاحيات Store/Update للـFAQ، وإكمال رسائل/attributes والتحقق | ScopedPasswordResetAndFaqAuthorizationTest | تحويل Index إلى DataTable وتوحيد الفورم والمسودات ما زال مطلوبًا |
| T05 / R03–R04 | تشغيل سكربت Create/Edit، واستعادة الاختيارات والمراجعة، وقفل العقارات وإعادة فحص الإتاحة ومنع كسر ارتباطات المزاد | AssetMutationSafetyTest وAssignmentFrontendSafetyTest | المعاينة البصرية لكل خطوة وقبول الإدارة الكامل لم ينفذا |
| T06 / R02 | تأجيل حذف الملفات القديمة بعد commit، ومنع تنظيف الملف الجديد عند فشل تنظيف القديم، والحفاظ على الملفات عند فشل الاستبدال | AssetMutationSafetyTest مع ملفات فعلية معزولة | تنظيف فشل التخزين الدائم يحتاج متابعة تشغيلية؛ لا أعتمد كل دورة الملفات مكتملة |
| T15 / R05 | allow-list مستقلة لـTenant، ورفض Central models قبل الاستعلام، وفحص الصلاحيات وكل سجل، ومنع حذف subsets أو IDs مكررة، وتطبيق domain guards على الأصول والمستخدمين والأدوار | AssetMutationSafetyTest: حالات HTTP وtransactions وrollback | قبول إدارة الأرشيف والتزامن في MySQL ما زال منفصلًا |
| S03 / R06–R09 | تحديد guard من route وDB connection الصحيح؛ file rollback وafterCommit، واستبدال القوائم بدل إحياء المحذوف، وhash للملفات، وحدود uploads من FormRequest المورد، ومنع البدء الجديد عند فشل discard | WizardDraftSafetyTest وWizardDraftFrontendSafetyTest | ما زال مطلوبًا job/retention وتنظيف دائم للفشل وضمان discard الكامل وإشارة نجاح Create/JSON purge؛ S03 جزئي |
| T02 | إصلاح view التحقق من البريد إلى view Tenant الموجودة | NavigationBoundaryTest | Central gateway الخاص بإعدادات2FA ما زال مطلوبًا |
| T13 | إصلاح صفحة قائمة الإشعارات وFormRequest الخاص بتحديد IDs لسجل النشاط | NavigationBoundaryTest | استخراج بقية orchestration والتصدير والقبول الكامل ما زال مطلوبًا |
| T10 / R11 | توجيه مسار renewal القديم إلى تدفق الدفع المصرح القائم، بدل handler primitive المعطوب والتجديد المباشر | NavigationBoundaryTest | لا اتصالات أو تجديد حقيقي بمزود خارجي؛ بقية صفحات الدومينات لم تغلق |
| T08 / R12 | رسالة عامة لأخطاء التقارير في API/Show/DataTable، تشمل السجلات القديمة؛ دعم غياب أول generation | ReportErrorPrivacyTest | بقية async/idempotency/queue acceptance لم تغلق |
| توافق روابط Admin / R10 | وجهة absolute و307 يحافظ على method/body للروابط القديمة | NavigationBoundaryTest | أسماء routes بقيت كما هي |

الاختبارات الجديدة كُتبت وشُغلت قبل الإصلاح، ثم شُغلت بعده. راجع وكيل مستقل الدفعة وكشف حالة Show عندما لا يوجد generation؛ أُصلحت وأضيف لها اختبار. لا يوجد commit أو نشر لهذه الدفعة؛ احتفظت بالتغييرات المحلية السابقة.

## التحقق المنفذ

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Tenants/AssetMutationSafetyTest.php tests/Unit/WizardDraftSafetyTest.php tests/Feature/Admin/ScopedPasswordResetAndFaqAuthorizationTest.php tests/Unit/TenantAuthenticationContractTest.php tests/Feature/GoldenPattern/NavigationBoundaryTest.php tests/Feature/GoldenPattern/ReportErrorPrivacyTest.php
```

النتيجة: **58 اختبارًا، 217 assertions، دون فشل**. استخدمت حزم سلامة البيانات SQLite داخل الذاكرة وملفات اختبار منفصلة، ولم تستخدم قاعدة المشروع. تشغيل PHPUnit احتاج الخروج من sandbox بسبب `is_readable` لملف bootstrap؛ التشغيل خارجه نجح، فالسبب السابق كان قيود القراءة وليس غياب autoload.

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/SharedFormValidationContractTest.php tests/Unit/CreateFormDraftContractTest.php tests/Unit/DiscardCompletedWizardDraftTest.php tests/Unit/MassDeletePolicyContractTest.php tests/Unit/TenantArchivePolicyContractTest.php tests/Unit/TenantAuthenticationContractTest.php
node --test tests/Unit/WizardDraftFrontendSafetyTest.cjs tests/Unit/AssignmentFrontendSafetyTest.cjs
npm run build
```

العقود: **23 اختبارًا، 503 assertions، دون فشل**. يوجد تداخل ملفات مع الأمر الأول؛ لا تُجمع الأعداد كاختبارات فريدة. اختبارات Node: **4 ناجحة**. بناء الواجهة نجح؛ ليس دليلًا على المطابقة البصرية.

لم أشغل suite كاملة تستعمل قاعدة `etmam_levan`، ولم أشغل migrations أو حذف بيانات فعلية. تهيئة بوابة DB اختبار منفصلة لبقية integration tests ما زالت بند G0/S08 مفتوحًا.

## ما زال مفتوحًا من الخطة الشاملة

- S01: تحديث manifest والبطاقات والجرد والتعارضات بحالات تستند إلى أدلة حديثة؛ لا أرفع الحالات إلى Compliant بسبب نجاح هذه الدفعة فقط.
- S02/S04/S05/S06/S07: تعميم النماذج وfile cards والجداول وstatus toggle/layout وCentral gateways والإشعارات على جميع الإدارات؛ الدفعة الحالية لا تغلق هذه العقود.
- S03: استكمال التنظيف الدوري والدائم، وdiscard الذري، ونجاح الإنشاء وJSON purge واختبارات lifecycle الأفقية لكل Create.
- Admin A01–A21: إكمال كل بطاقة تفصيلية؛ الإصلاحان A02/A21 أعلاه جزئيان، وبقية الإدارات لا تزال تحتاج تنفيذًا وتحقيقًا.
- Tenant T01–T15: إكمال كل بطاقة؛ الإصلاحات المسجلة أعلاه لا تعني إغلاق بقية صفحات الإدارة أو الأرشيف أو الملفات أو التكاملات.
- G0/G1/G2/G3: اختبارات التكامل المعزولة، المعاينة البصرية لكل صفحة عند1440/1024/390، الاختبارات الأفقية النهائية والـmanifest، ومراجعة التزامن على قاعدة تدعم row locks.

**حالة الخطة: قيد التنفيذ، غير مكتملة.** هذا سجل بداية التنفيذ والدليل الحالي، وليس إعلان مطابقة كاملة لجميع الصفحات.
