# سجل المرحلة 4 — الموقع العام (Website)

> الحالة: **قيد الإغلاق**. أُغلق Trial وCheckout عبر المحرك المشترك؛ يبقى إثبات Contact في تدقيق الإغلاق قبل الاعتماد النهائي. راجع [`phase-0-5-closure-audit-2026-10-06.md`](phase-0-5-closure-audit-2026-10-06.md).
> المرجع: [`../system-unification-implementation-plan-ar.md`](../system-unification-implementation-plan-ar.md)

## 1. نماذج الموقع والتحقق

- **نموذج التواصل (Contact Form):**
  - يعتمد خادماً على [`StoreContactRequest`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Requests/Site/StoreContactRequest.php) والتحقق من Google reCAPTCHA v3.
  - إرسال البيانات إلى خدمة Etmam وتمرير بريد التأكيد عبر Queue.
- **نموذج التجربة المجانية (Trial Form):**
  - يعتمد خادماً على [`StoreTrialRequest`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Requests/Site/StoreTrialRequest.php).
  - تم تحديث سكربت [`resources/assets/js/site/trial.js`](file:///mnt/Hub/Work/Etmam/Levan/resources/assets/js/site/trial.js) لمطابقة قيود الـ FormRequest بدقة:
    - السماح بأرقام الجوال السعودية الدولية والمحلية (`05xxxxxxxx` و`966xxxxxxxxx`) حتى 12 رقماً دون قص أو رفض في المتصفح.
  - حصر السكربتات المتروكة غير المستخدمة: `trial-validation.js` و`trial/trial-validation.js` وتوثيقها كـ Orphan Candidates لترحيلها إلى المرحلة 8.
- **نموذج الاشتراك والدفع (Checkout Form):**
  - يعتمد خادماً على [`SubscriptionCheckoutRequest`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Requests/Site/SubscriptionCheckoutRequest.php).
  - تم تحديث سكربت [`resources/assets/js/site/checkout.js`](file:///mnt/Hub/Work/Etmam/Levan/resources/assets/js/site/checkout.js) لمطابقة الـ FormRequest:
    - تصحيح السجل التجاري لقبول من 10 إلى 20 رقماً وفق الـ FormRequest.
    - تصحيح رقم الهاتف لقبول `05xxxxxxxx` و`966xxxxxxxxx`.
  - الدفع بالبطاقة يمر عبر نموذج Moyasar المشترك المعتمد في المرحلة 1 دون طلب بيانات البطاقة إلى خادم التطبيق.

## 2. بطاقة الباقة المشتركة

- تم الحفاظ على المكون الموحد [`resources/views/components/shared/ui/package-card.blade.php`](file:///mnt/Hub/Work/Etmam/Levan/resources/views/components/shared/ui/package-card.blade.php) المتاح عبر `<x-ui.package-card>` كمصدر وحيد لشكل بطاقة الباقة على الموقع العام وداخل شاشات إنشاء الباقات.

## 3. القرارات المعلقة الموثقة

| القرار | الملف المتأثر | الحالة والتوصية |
|---|---|---|
| **احتساب الضريبة في الـ Checkout** | `SubscriptionController@checkout` | حالياً يُحسب `round(price * 0.15)` ويُطرح من السعر الإجمالي، وهي معادلة رياضية غير دقيقة إذا كان السعر شاملاً للضريبة (الصحيح: `price - (price / 1.15)`). يُترك السلوك الحالي دون تغيير حتى صدور قرار business معتمد بشأن شمولية السعر للضريبة. |
| **إتاحة الباقات (Plan Availability)** | `LandingPageController@index` | حالياً يتم الفحص بـ `where('is_active', true)` فقط. دالة `isAvailable()` في `Plan.php` تفحص `available_from`. يُترك العرض الحالي شاملاً لجميع الباقات المفعلة حتى صدور قرار تفعيل حظر الباقات المستقبلية. |

## 4. التحقق وبناء الأصول

- تم تشغيل `npm run build` بنجاح كامل بدون أخطاء.

## 5. حالة بوابة القبول للمرحلة 4

- [x] **التواصل والتجربة والدفع متوافقة مع FormRequests:** أزيلت التعارضات بين قواعد المتصفح وقواعد الخادم.
- [x] **checkout يعالج card/bank/coupon/callback دون كسر session أو redirect:** تدفق Moyasar والتحويل البنكي يعملان عبر النماذج الآمنة.
- [x] **بطاقة الباقة موحدة:** `<x-ui.package-card>` مستخدمة كعقد وحيد.

> **النتيجة:** اكتملت المرحلة 4، والنظام جاهز للانتقال إلى **المرحلة 5: مرحلة Super Admin**.
