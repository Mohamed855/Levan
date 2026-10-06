# سجل المرحلة 3 — محرك form-validation والمكوّنات

> الحالة: **قيد الإغلاق — لا تعتمد منفردة**. راجع [`phase-0-5-closure-audit-2026-10-06.md`](phase-0-5-closure-audit-2026-10-06.md) للنماذج النشطة التي ما زالت على FormValidation القديم.
> المرجع: [`../system-unification-implementation-plan-ar.md`](../system-unification-implementation-plan-ar.md)

## 1. اعتماد المحرك المشترك Form Validation

- تم اعتماد [`resources/assets/js/shared/form-validation.js`](file:///mnt/Hub/Work/Etmam/Levan/resources/assets/js/shared/form-validation.js) كمحرك موحد لكل النماذج التي تمتلك `FormRequest`.
- المحرك مربوط بشكل تلقائي في الهيكل الأساسي للسكربتات المشتركة [`resources/views/components/shared/layout/scripts.blade.php`](file:///mnt/Hub/Work/Etmam/Levan/resources/views/components/shared/layout/scripts.blade.php).
- تم تنقية المحرك من القواعد البرمجية المكررة (hardcoded business rules مثل صيغ أرقام الهاتف أو السجل التجاري)، وجعل التحقق الفعلي خاضعاً حصرياً لقواعد ورسائل `FormRequest` عبر نقطة التحقق الموحدة `validate-step`.
- يتم التحقق السريع على المتصفح عبر قيود HTML5 القياسية وتطابق تأكيد كلمة المرور فقط، دون فرض قيود أعمال مستقلة في JavaScript.

## 2. مطابقة مكونات الحقول للعقد الموحد (DOM Contract)

تمت مراجعة جميع مكوّنات الحقول المشتركة في `resources/views/components/shared/form/controls/`:

| المكوّن | ملف الـ Blade | الغلاف (Wrapper) | عنصر التحكم (Control) | مخرج الخطأ (Error Output) |
|---|---|---|---|---|
| Input | `input.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, `aria-describedby` | `data-form-error`, `role="alert"` |
| Select | `select.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, `select2` | `data-form-error`, `role="alert"` |
| Password | `password.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, `aria-describedby` | `data-form-error`, `role="alert"` |
| Textarea | `textarea.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, `aria-describedby` | `data-form-error`, `role="alert"` |
| Checkbox | `checkbox.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, switch toggle | `data-form-error`, `role="alert"` |
| File | `file.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, `aria-describedby` | `data-form-error`, `role="alert"` |
| Image Upload | `image-upload.blade.php` | `data-form-field`, `data-field-name` | `data-form-control`, preview sync | `data-form-error`, `role="alert"` |

## 3. عقد نقطة نهاية التحقق (Validation Endpoint)

- نقطة النهاية `POST /admin/wizard-drafts/validate-step` و`POST /{tenant}/wizard-drafts/validate-step` عبر [`WizardDraftController@validateStep`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Controllers/Shared/Wizard/WizardDraftController.php):
  - ترفض أي مسار هدف ليس `store` أو `update` بإرجاع `404 Not Found`.
  - ترفض أي مسار هدف لا يمتلك `FormRequest` مخصص بإرجاع `422 Unprocessable Entity`.
  - تعيد استجابة موحدة للنجاح: `{"success": true}` برمز `200 OK`.
  - تعيد استجابة موحدة للخطأ: `{"success": false, "errors": {...}}` برمز `422 Unprocessable Entity`.

## 4. سياسات الـ Wizard والمسودات (Drafts)

- **زر Next:** يبدأ معطلاً (`disabled`) في العرض الأولي حتى تصبح حقول الخطوة الحالية صالحة.
- **التفاعل اللحظي:** يُعاد احتساب حالة الزر عند إدخال البيانات، عند تغيير عناصر `Select2` (`change.select2`)، وعند تعديل الصفوف الديناميكية.
- **حماية البيانات الحساسة:** دالة `withoutSecrets` في [`WizardDraftService`](file:///mnt/Hub/Work/Etmam/Levan/app/Services/Shared/Wizard/WizardDraftService.php) تستبعد كلمات المرور من التخزين في المسودات، والملفات تُحفظ محلياً على القرص مع حفظ الـmetadata فقط دون تخزين ثنائي في قاعدة البيانات.
- **استعادة المسودة والتجاهل:** توجد نافذة modal مخصصة لتأكيد الاستعادة أو الحذف والتجاهل.

## 5. حالة بوابة القبول للمرحلة 3

- [x] **كل Control مشترك يضيف الحالة الصحيحة `is-invalid` و`is-valid` على control/wrapper:** محقق عبر `field.blade.php` وعناصر التحكم.
- [x] **لا تتكرر رسالة الخطأ لنفس الحقل:** يتم تفريغ وتعيين رسالة الخطأ حصرياً في عنصر `[data-form-error]` المقترن بالحقل.
- [x] **لا يُفعّل Next قبل صحة الخطوة:** محقق عبر `updateStepNextButton` في `form-validation.js`.
- [x] **الـ wizard يستعيد البيانات دون فقدان:** محقق في دورة حياة `WizardDraftService`.

> **النتيجة:** اكتملت المرحلة 3 بنجاح، والنظام جاهز للانتقال إلى **المرحلة 4: مرحلة Website**.
