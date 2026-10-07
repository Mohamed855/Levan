# المراجعة الإجمالية للمراحل 0–9

**تاريخ المراجعة:** 7 أكتوبر 2026 — **تحديث التحقق:** 8 أكتوبر 2026
**غرضها:** تثبيت الفرق بين الإغلاق التقني المثبت وبين قبول الإصدار الذي يحتاج متصفحاً وخدمات خارجية. لا تُعد هذه الوثيقة بديلاً عن سجل أي مرحلة.

## مصفوفة الحالة النهائية

| المرحلة | الحالة | دليل الإغلاق | ما لا يدخل في الإغلاق التقني |
|---|---|---|---|
| 0 — Baseline والجرد | مغلقة كسجل تاريخي ومتحقق تشغيلياً لاحقاً | [phase-0-baseline-2026-10-05.md](phase-0-baseline-2026-10-05.md)، [مواءمة 1–6](phase-1-6-closure-reconciliation-2026-10-07.md) | لا يمثل baseline قبول إصدار production. جرد Assignments/Properties/Auctions في [0R](phase-0-new-tenant-modules-2026-10-07.md). |
| 1 — الأمن والعزل | مغلقة تقنياً | [phase-1-security-register.md](phase-1-security-register.md)، واختبارات HMAC/auth/isolation | تفعيل live payment أو انتهاء نافذة `X-Secret-Key` قرار نشر مؤرخ، لا تغيير تلقائي داخل هذه المراجعة. |
| 2 — UI contracts | مغلقة | [phase-2-ui-contracts-register.md](phase-2-ui-contracts-register.md) | المراجعة البصرية الحية في متصفح تأتي ضمن smoke الإصدار. |
| 3 — Forms والـWizard | مغلقة كأساس مشترك | [phase-3-forms-validation-register.md](phase-3-forms-validation-register.md) | تعميم Golden Pattern على كل إدارة هو المرحلة 10 وليس نقصاً خفياً في المحرك. |
| 4 — Website | مغلقة تقنياً | [phase-4-website-register.md](phase-4-website-register.md) | قرارا الضريبة وإتاحة الباقات business decisions موثقان؛ callback الحي ضمن smoke. |
| 5 — Super Admin | مغلقة تقنياً | [phase-5-admin-register.md](phase-5-admin-register.md) | قبول واجهات الحسابات الحية والصلاحيات في المتصفح ضمن smoke. |
| 6 — Tenant | مغلقة تقنياً | [phase-6-tenant-closure-2026-10-07.md](phase-6-tenant-closure-2026-10-07.md) | اختبار المسارات مع مستخدمين حقيقيين وعزل tenancy في المتصفح ضمن smoke. Branches مستبعدة نهائياً. |
| 7 — DataTables والاستجابات | مغلقة تقنياً | [phase-7-datatables-responses-register-2026-10-07.md](phase-7-datatables-responses-register-2026-10-07.md) | loading/empty/error المرئي لكل جدول يثبت في smoke، والعقد البرمجي مغطى بالاختبارات. |
| 8 — routes والـlegacy | مغلقة | [phase-8-legacy-routes-register-2026-10-07.md](phase-8-legacy-routes-register-2026-10-07.md)، `LegacyRouteContractTest` | حُذفت مرشحات الملفات المحددة في 8 أكتوبر بعد موافقة صريحة؛ الإدارات غير المفعّلة تبقى مسجلة بمالكيها إلى قرار product مستقل. |
| 9 — regression وقبول التشغيل | مغلقة محلياً | [phase-9-regression-register-2026-10-07.md](phase-9-regression-register-2026-10-07.md)، [سجل smoke المحلي](phase-9-local-smoke-register-2026-10-08.md)، suite الإغلاق وbuild/cache/routes | لا يمثل القبول المحلي قبول production أو transaction حي؛ ذلك قرار release مستقل. |

## نتيجة فحص الإغلاق الحالي

1. لا توجد routes معلقة في ملفات `routes`؛ اختبار العقد يمنع إعادة ذلك.
2. أزيلت ملفات legacy المحددة بعد تحقق مراجعها وموافقة صريحة؛ يحتفظ Git بسجل الاسترجاع. لا يشمل ذلك الإدارات غير المفعّلة المسجلة كـInactive لأنها تتطلب قرار product مستقل.
3. أغلقت suite الإغلاق بـ**121 اختباراً و2,767 assertion**؛ وبعد حذف legacy نجحت إعادة فحص 32 Unit (1,544 assertion) و23 Feature متأثراً (122 assertion).
4. `npm run build` و`route:cache` و`config:cache` و`view:cache` نجحت، ثم أزيلت caches المؤقتة.
5. نُفذ smoke فعلي محلي عبر Chromium وsessions للحسابات المزروعة، وشمل Website وSuper Admin وpath/domain tenancy وDataTables و2FA والـvalidation. تفاصيله في سجل smoke المحلي.

## بوابة المرحلة التالية

اكتمل شرط البدء المحلي للمرحلة 10. عند تجهيز production، تعاد قائمة [manual-smoke-checklist.md](manual-smoke-checklist.md) ضمن إجراءات الإصدار ولا تعيد فتح إغلاق التطوير المحلي.
