# قبول الإصدار — Levan

الحالة الحالية: **غير مقبول للنشر بعد**. هذا النموذج لا يتحول إلى قبول إلا بعد بوابة Task 12 على نسخة ثابتة.

## شروط القبول

- [ ] كل صف في `docs/baselines/golden-pattern-manifest.md` حالته `verified` أو استثناء أعمال معتمد ومثبت.
- [ ] لا توجد فجوة provider/قرار أعمال مفتوحة. تسجيل/نقل/تجديد domain المدفوع يحتاج قرار registrar وتسعير وهوية Whois قبل القبول.
- [ ] suite كاملة على قواعد اختبار معزولة: zero failures وzero critical skips.
- [ ] migrations المركزية وTenant A/B وseed المرجعي idempotent مثبتة على staging؛ لا قواعد المستخدمين مست.
- [ ] اختبار HTTP لكل DataTable عملي: draw/search/sort/filter/authorization وescaping.
- [ ] اختبار ownership وPolicies لكل mutation، بما في ذلك mass delete/restore/force delete والمسودات والملفات.
- [ ] فحص payment/receipt/callback/provisioning/idempotency في provider sandbox؛ لا live charge.
- [ ] `composer validate`, `composer audit`, `npm run build`, syntax, config/route/view cache ناجحة ومسجلة بنتائج فعلية.
- [ ] smoke browser desktop/mobile/RTL للـAdmin وTenant والموقع، مع صور/مرفقات/PDF outputs.
- [ ] rehearsal للاستعادة وrollback من backup staging موثق في runbook.

## معلومات الموافقة

| بند | القيمة |
|---|---|
| Git revision | غير مسجل بعد |
| بيئة rehearsal | غير منفذة بعد |
| تاريخ التنفيذ | غير مسجل بعد |
| مالك الموافقة | غير محدد |
| نتيجة البوابة | غير مقفلة |

لا يعتبر وجود الملفات أو نجاح syntax/cache وحده قبول إصدار.
