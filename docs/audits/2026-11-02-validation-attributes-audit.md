# تدقيق أسماء حقول التحقق

هذا التقرير للقراءة فقط؛ لم تُعدّل أي `messages()` أو `attributes()` أثناء إعداده.

## قاعدة التطبيق المعتمدة

- يجب أن تحتوي كل قاعدة تحقق مرتبطة بحقل ظاهر للمستخدم على attribute يساوي نص الـlabel الظاهر حرفيًا، عربيًا كان أو إنجليزيًا.
- تبقى `messages()` المخصصة دون أي تغيير.
- لا تُسمى الحقول الداخلية، المخفية، أو غير المرتبطة بواجهة نشطة تلقائيًا. تُدرج أدناه لاتخاذ قرار تسمية منفصل.
- طلبات الوراثة تعتمد `attributes()` من الأب ولا تُحسب كأنها ناقصة.

## نتائج الحصر

- جرى تدقيق كل Requests الإدارة والمستأجر والموقع والمشترك التي تعلن قواعد تحقق، مع تتبع الوراثة والـcontrollers والـviews النشطة.
- الحصر الآلي الأولي وجد 134 Request بقواعد مباشرة؛ أضيفت أيضًا الطلبات ذات القواعد الموروثة عند الحاجة.
- توجد Labels في Blade Components وHTML عادي؛ لا تكفي مطابقة اسم الحقل العامة لأن الاسم نفسه قد يختلف label الخاص به من نموذج لآخر.

## Labels مؤكدة تحتاج إضافة أو تصحيح

### الإدارة

| Request / field | الـlabel المطابق |
|---|---|
| AdminUserStoreRequest `name` | الاسم الكامل |
| AdminUserUpdateRequest / UpdateProfileRequest `name` | الاسم الكامل |
| AdminUserUpdateRequest `password`, `password_confirmation` | كلمة المرور الجديدة، تأكيد كلمة المرور الجديدة |
| Admin role requests `permissions` | الصلاحيات المتاحة |
| FAQ `is_active` | الحالة |
| Auction templates `description`, `is_active` | وصف القالب، القالب نشط |
| Admin two-factor `code` | أدخل الرمز الأمني المكون من 6 أرقام |
| Client `name` | اسم المستخدم المسؤول |
| Contact reply `reply` | نص الرد |
| Coupon fields | اسم الكوبون، كود الكوبون، نوع الخصم، قيمة الخصم، الحد الأقصى للخصم، تاريخ الانتهاء، حد الاستخدام، حالة الكوبون، إسناد الكوبون |
| Plan fields | أسماء الـlabels الظاهرة في نموذج الباقة، بما فيها: اسم الباقة، كود الباقة (English Uppercase)، نوع الباقة، لون الباقة المميز، وصف الباقة، سعر الدورة الأساسية (ر.س)، نسبة الخصم (%)، ميزات الباقة وحدودها |
| Bank fields | اسم البنك، أيقونة البنك، اسم الحساب، رقم الآيبان (IBAN)، رقم الحساب |
| Control domain fields | الامتداد، السعر |
| Admin settings | الـlabels الظاهرة في إعدادات العامة/إتمام/Microsoft، ومنها `إسم الشركة`, `API Key`, `Client ID`, `Redirect URI` بحسب الحقل |
| Subscription form | الشركة المستفيدة، الباقة المطلوبة، مدة الاشتراك (بالشهور)، طريقة الدفع، حالة الدفع الآن، رقم العملية / الحوالة البنكية، إيصال الدفع / الحوالة (مستند) |
| Admin ticket reply/status | إضافة رد على التذكرة:، الحالة الحالية: |
| Admin activity dates | تاريخ الإنشاء من، تاريخ الإنشاء إلى |

### الموقع والمشترك

| Request / field | الـlabel المطابق |
|---|---|
| SubscriptionCheckoutRequest / StoreTrialRequest `billing_email` | البريد الإلكتروني للإدارة |
| SubscriptionCheckoutRequest `coupon_code` | كود الخصم |
| CompleteCheckoutPaymentRequest `bank_id` | اختر البنك للتحويل |
| StoreContactRequest | `customer_name`: الاسم، `customer_email`: البريد الإلكتروني، `message`: الرسالة أو الاستفسار |
| VerifyCheckoutOtpRequest / VerifyTrialOtpRequest `otp` | رمز التحقق |
| Draft resource uploads | أسماء الملفات الظاهرة في النماذج، مثل: الصورة الشخصية، أيقونة البنك، صورة الإسناد، خطاب الإسناد، مستندات عامة، مرفقات إضافية، صورة غلاف التقرير، صورة غلاف المزاد، المرفقات |

### المستأجر

| Request / field | الـlabel المطابق |
|---|---|
| StoreUserRequest / UpdateUserRequest / UpdateProfileRequest `name` | الاسم الكامل |
| Role `permissions` | الصلاحيات المتاحة |
| Login `email` | البريد الإلكتروني أو رقم الجوال |
| Password reset OTP `otp` | رمز التحقق (OTP) |
| Two-factor `code` | أدخل الرمز الأمني المكون من 6 أرقام |
| Change user password | كلمة المرور الحالية، كلمة المرور الجديدة، تأكيد كلمة المرور الجديدة |
| Profile image | اختر صورة شخصية جديدة |
| Ticket category / attachments | تصنيف التذكرة، المرفقات |
| Ticket reply / rating / survey | إضافة رد على التذكرة:، تقييم هذا الرد:، وأسئلة الاستبيان الظاهرة |
| Bank transfer `bank_id` | البنك المحول إليه |
| License checkout count | عدد المستخدمين الإضافيين المطلوبين |
| Assignment fields | رقم الإسناد، اسم الإسناد، الجهة المسندة، المدينة، النطاق/الحي، تصنيف الإسناد، تاريخ البداية والنهاية، وصف الإسناد، العقارات المرتبطة، صورة الإسناد، خطاب الإسناد، مستندات عامة، مرفقات إضافية |
| Property fields | المساحة (م²)، وصف العقار، الحي / النطاق، صورة العقار، حدود العقار وأطوالها، جهة الإصدار، بيانات الملاك، صور إضافية للمعرض، مستندات إضافية، ملاحظات إضافية، وحقول الحدود والوحدات المتكررة |
| Auction fields | تاريخ بداية المزاد، تاريخ نهاية المزاد، الإسناد المرتبط بالمزاد، الجهة المشرفة على المزاد، اسم مدير المشروع، العقارات المرتبطة بالإسناد المختار، صورة غلاف التقرير، صورة غلاف المزاد، ملفات إضافية |
| Domain transfer / renewal / DNS | كود EPP / Auth Code، اختر مدة التجديد، اسم الدومين، طريقة الربط (يتم تحديدها تلقائيًا) |
| Activity log | نوع النشاط، تاريخ الإنشاء من، تاريخ الإنشاء إلى |
| Resource status | الحالة |

## حقول بلا label نشط — تحتاج قرار تسمية

### نقل وحالة داخلية مشتركة

- Dashboard realtime: `channel_name`, `socket_id`.
- Wizard draft/validation: `resource_key`, `file_key`, `step`, `target_path`, `target_method`, `fields`, `fields.*`.
- Upload drafts غير المعروفة أو غير المعروضة: `payment_proof`, `file`, `page_backgrounds`, `page_backgrounds.*`.

### الإدارة

- Bulk/action payloads: `model`, `ids`, `ids.*`, `order`, `order.*.id`, `order.*.sort_order`.
- Uniqueness exclusion IDs: `user_id`, `company_id`.
- DataTables transports: `search`, `search.value`، وحقول activity القديمة `log_name`, `date_from`, `date_to` عندما لا تقابل filter نشطًا.
- Dashboard `range` (له aria-label فقط، لا label ظاهر).
- Auction template designer transport: `schema`, `file`, `page_backgrounds` root.
- Dynamic permissions items: `permissions.*` (كل checkbox له label مترجم مختلف، ولا يوجد label ثابت واحد).
- Password-reset transport: `tenant_id` و`email` المخفي.
- Subscription/SubscriptionManagement غير المرتبطة بواجهة نشطة: الحقول غير المعروضة في النماذج الحالية، ومنها `payment_type`, `payment_proof`, `has_discount`, `discount_value`, `trial_end_date`, `activate_immediately`، وكل حقول Requests SubscriptionManagement legacy غير المربوطة بمسار حالي.
- Ticket attachment picker: `attachments`, `attachments.*` (tooltip فقط: إرفاق ملفات).

### المستأجر

- واجهات أو routes غير نشطة: `background_image`، وحقول Change/Update Tenant Password غير المربوطة بنموذج نشط حاليًا.
- سياق reset المخفي: `token`, `email`.
- Ticket internal/proxy: `attachments`, `attachments.*`، و`comment` (placeholder فقط).
- Settings المخفية أو غير المعروضة: `active_tab`, `vertical_header_image`, `vertical_footer_image`, `sms_api_endpoint`, `sms_from_number`, `sms_callback_url`, `sms_rate_limit`, `support_emails`, `support_emails.*`.
- Coupon proxy/hidden values: `coupon_code` في الدفع والاشتراك.
- Generated property state: `property_number`, `latitude`, `longitude`, `raw_aerial_image`, `is_active`, `boundary_geojson` وكل مفاتيحه المتداخلة.
- Auction steps غير المفعلة: `marketing`, `property_results` وكل عناصر النتائج، `marketing.campaigns`, `marketing.links`, `marketing_evidence`, `board_images_before`, `board_images_after` وكل عناصرها المتداخلة.
- Selected-record/action transports: `ids`, `ids.*`, `id`, `file`, `key`, `value`, `user_id`, `model` في الأرشيف والحذف الجماعي والإشعارات وإعدادات الرسم والتراخيص.
- Domain hidden/API values: `domain_id`, `domains`, `domains.*`, `attempt`, callback `id`, `limit`.
- Domain inputs بلا label ثابت: `domain` في سياقات بحث/ربط/نقل متعددة، `domain_name`، و`extension` عندما لا يكون له caller نشط.
- Role endpoints غير المرتبطة بنموذج نشط: `permissions`, `permissions.*`, `denied_permissions`, `denied_permissions.*`, `role`, `status`.

## ملاحظات قبل التنفيذ

1. الـlabels أعلاه هي نصوص مؤكدة من الواجهات النشطة، وليست ترجمات مستنتجة من أسماء الحقول.
2. أسماء الحقول الداخلية يجب أن تُعتمد منك أولًا؛ لن تُضاف أسماء عربية أو إنجليزية بالافتراض.
3. بعض الحقول المتداخلة لها label مجموعة واحد فقط (`features.*`, `permissions.*`، والملفات الديناميكية). يجب اعتماد ما إذا كان label المجموعة هو المطلوب للعنصر المتكرر.
4. بعد اعتماد أسماء الحقول الداخلية، يُنفذ التعديل مع اختبار عقد يمنع نقص attributes أو مخالفة label في الواجهات النشطة.
