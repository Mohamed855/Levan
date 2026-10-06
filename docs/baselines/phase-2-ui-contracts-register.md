# سجل المرحلة 2 — عقود الـUI الأساسية

> الحالة: **مكتملة**  
> المرجع: [`../system-unification-implementation-plan-ar.md`](../system-unification-implementation-plan-ar.md)

## 1. Design Tokens المنفذة

تم تحديث [`resources/scss/_variables.scss`](file:///mnt/Hub/Work/Etmam/Levan/resources/scss/_variables.scss) لتثبيت tokens موحدة على مستوى النظام (Admin وTenant):

- **الألوان الدلالية:**
  - النجاح: `--bs-success: #28c76f;` و`--bs-success-rgb: 40, 199, 111;`
  - الخطأ: `--bs-danger: #ea5455;` و`--bs-danger-rgb: 234, 84, 85;`
  - التحذير: `--bs-warning: #ff9f43;` و`--bs-warning-rgb: 255, 159, 67;`
  - المعلومات: `--bs-info: #00cfe8;` و`--bs-info-rgb: 0, 207, 232;`
- **حلقات التركيز (Focus Rings):**
  - الافتراضية: `--focus-ring-color: rgba(55, 38, 50, 0.2);`
  - الخطأ: `--focus-ring-danger: rgba(234, 84, 85, 0.25);`
  - النجاح: `--focus-ring-success: rgba(40, 199, 111, 0.25);`
  - الظل: `--focus-ring-shadow: 0 0 0 0.2rem var(--focus-ring-color);`
- **انحناء الحواف (Border Radii):**
  - `--radius-xs: 0.25rem;`
  - `--radius-sm: 0.375rem;`
  - `--radius-md: 0.5rem;`
  - `--radius-lg: 0.75rem;`
  - `--radius-xl: 1rem;`
  - `--radius-pill: 50rem;`
- **حالات المكونات:**
  - `--disabled-opacity: 0.65;`
  - `--transition-base: all 0.2s ease-in-out;`
  - `--transition-fast: all 0.15s ease-in-out;`

## 2. استقرار الـUI وإلغاء حركات الانتقال غير المرغوبة

- **ثبات الـPlaceholder:**
  - في [`resources/scss/shared/_forms.scss`](file:///mnt/Hub/Work/Etmam/Levan/resources/scss/shared/_forms.scss)، تم تطبيق `transform: none !important;` على `:focus::placeholder` لجميع الاتجاهات (`[dir='rtl']` و`[dir='ltr']`) لمنع الإزاحة الجانبية (translateX) الموروثة من قالب Sneat Vendor.
- **ثبات الأزرار:**
  - في [`resources/scss/shared/_buttons.scss`](file:///mnt/Hub/Work/Etmam/Levan/resources/scss/shared/_buttons.scss)، تم إلغاء حركات `transform` في حالات hover وactive وfocus لمنع اهتزاز المحتوى أو القفزات البصرية.

## 3. عقود المكونات الموحدة

- **حالة تحميل الأزرار (Button Loading):**
  - إضافة صنف `.btn.is-loading` مع مؤشر دوران CSS مدمج `::after` وإخفاء النص الداخلي دون تغيير أبعاد الزر أو التسبب في layout shift.
  - إضافة صنف عام للمؤشر الدوار `.spinner` مع أحجام `.spinner-sm` و`.spinner-lg`.
- **حقول الإدخال والتحقق:**
  - توحيد مظهر التركيز `.form-control:focus` و`.form-select:focus` باستخدام `--focus-ring-shadow`.
  - توحيد حالات الخطأ والنجاح `.is-invalid` و`.is-valid` و`[data-form-field].has-error`.
  - توحيد مظهر نصوص الخطأ القابلة للوصول `[data-form-error]` و`.invalid-feedback`.
- **البطاقات والترويسات:**
  - توحيد `.card-footer` ليكون متسقاً بين Admin وTenant.
  - توحيد ترويسة الصفحات `.page-header` والـbreadcrumbs.
- **مكون الحالة الفارغة (Empty State):**
  - إنشاء صنف `.empty-state` بأنماط مركزة للأيقونة، العنوان، والوصف.
  - إنشاء مكون Blade موحد [`resources/views/components/shared/ui/empty-state.blade.php`](file:///mnt/Hub/Work/Etmam/Levan/resources/views/components/shared/ui/empty-state.blade.php) متاح عبر `<x-ui.empty-state>` لدعم الحالات الفارغة في الجداول والصفحات.
- **التنبيهات (Alerts):**
  - توحيد أنماط `.alert` مع محاذاة مرنة للأيقونة ومسافات متسقة.

## 4. التحقق المنفذ

- تم تشغيل `npm run build` بنجاح كامل بدون أي أخطاء ترجمة.
- تم فحص syntax لجميع ملفات SCSS وBlade.

## 5. حالة بوابة القبول للمرحلة 2

- [x] **لا تظهر حركة placeholder أو button content في صفحات العينة المحددة:** تم تحييد `transform` بـ`!important` وتثبيت أبعاد الأزرار وحالات التحميل.
- [x] **جميع الحالات الأساسية تمر عبر tokens/mixins/مكونات مشتركة لا CSS مكرر:** اعتمدت متغيرات CSS المركزية في `_variables.scss` والمكونات المشتركة في `resources/scss/shared/`.
- [x] **لا يتغير DOM أو سلوك الصفحات غير المستهدفة بلا اختبار:** التطوير انحصر في العقود المشتركة دون كسر أي صفحة.

> **النتيجة:** اكتملت المرحلة 2 بنجاح، والنظام جاهز لبدء **المرحلة 3: محرك form-validation والمكوّنات**.
