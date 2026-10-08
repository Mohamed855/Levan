# Golden Pattern Manifest — المرحلة 10

- **تاريخ خط الأساس:** 8 أكتوبر 2026
- **النطاق:** كل route نشط في `Admin` و`Tenant` فقط. لا يشمل Website أو المسارات المسجلة صراحةً كـInactive في سجل المرحلة 8.
- **المالك التقني:** فريق Levan Backend.
- **مرجع العقد:** [golden-pattern.md](../architecture/golden-pattern.md).
- **مصدر الجرد:** `php artisan route:list --json`، ثم إزالة تكرارات نفس `Controller@method` الناتجة من central-domain aliases. هذا يمنع اعتبار المسار الواحد إدارةً مرتين.

## 1. قواعد قراءة السجل

كل صف يمثل عائلة وظيفية واحدة، لا نسخة الـdomain من المسارات. `Central` تعني قاعدة البيانات المركزية مع `auth:admin`، و`Tenant` تعني قاعدة المستأجر التي يهيئها middleware tenancy مع `auth:tenant`. لا يسمح الصف الذي يحمل **Gap** بإغلاق الإدارة: يجب أن يحدد التعديل اللاحق الـPolicy، الـFormRequest، والاختبار الدال قبل نقله إلى **Compliant**.

الاختصارات داخل الجداول:

| الحقل | المعنى |
|---|---|
| Routes | بادئة الاسم؛ methods المقابلة تقرأ من route list، وكل mutation يجب أن يكون POST/PUT/PATCH/DELETE. |
| Request/Policy | `موجود` يعني أن العقد مثبت في الكود أو الاختبار؛ `Gap` يعني أن مرحلة 10 مطالبة بإكماله، وليس إقراراً بصحته. |
| UI | `DT` = DataTable، `View` = Blade، `API` = JSON/endpoint، `Dashboard/Report` = عقد خدمة view-data/report. |
| Tests | أدنى الدليل المطلوب: `F` Feature، `U` Unit، `I` tenant isolation، `S` smoke. |

## 2. Central / Super Admin

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة الأولية |
|---|---|---|---|---|---|---|
| Dashboard | `admin.dashboard.*` · `DashboardController` | مؤشرات مركزية/cache عند الحاجة | Dashboard service: **Gap** | Dashboard | F,U,S | Gap |
| Authentication & 2FA | `admin.login`, `admin.logout`, `admin.two-factor.*` · `AdminAuthController`, `AdminTwoFactor*Controller` | Admin guard، sessions، 2FA recovery | Login/confirm موجود؛ mutation guards موحّدة: Gap | View | F,S | Gap |
| Admin users & roles | `admin.users.*`, `admin.roles.*` · `AdminUserController`, `AdminRoleController` | Admin users، Spatie roles/permissions | Admin users: registered record Policy; roles: guard-aware Spatie Role policy/lifecycle: Gap | DT + forms | F,U,S | Partial |
| Plans | `admin.plans.*` · `PlanController` | `Plan`، features، pricing/cycle/limits | `PlanRequest` وreorder + registered resource Policy؛ archive lifecycle Policy/requests: Gap | DT + wizard | F,U,S | Partial |
| Companies / tenants | `admin.companies.*` · `CompanyController` | Company، tenant creation، central tenancy boundary | store/update/uniqueness requests + registered resource Policy؛ archive lifecycle/Action: Gap | DT + forms | F,U,S | Partial |
| Subscriptions & approvals | `admin.subscriptions.*`, `admin.subscription-management.*` · `SubscriptionController`, `SubscriptionManagementController` | central subscriptions، approval workflow | creation/reject/bulk requests جزئية؛ accept/status lifecycle: Gap | DT + wizard | F,U,S | Gap |
| Billing | `admin.bills.*` · `AdminBillsController` | central bills، receipts/payment state | reject request موجود؛ verify/reactivate Policy/request/action: Gap | DT + View | F,U,S | Gap |
| Coupons | `admin.coupons.*` · `AdminCouponController` | coupons، plan applicability | `CouponRequest` + registered record Policy; archive lifecycle/request: Gap | DT + forms | F,U,S | Partial |
| Support | `admin.tickets.*` · `AdminTicketController` | tickets/replies/workflow | reply/status requests موجودة؛ record Policy/lifecycle: Gap | DT + View | F,U,S | Gap |
| Content clients | `admin.content_management.client.*` · `ContentManagement\\Client\\ClientController` | central marketing clients | store/update requests + registered Policy; file Action: Gap | DT + forms | F,U,S | Partial |
| Content portfolios | `admin.content_management.portfolio.*` · `ContentManagement\\Portfolio\\PortfolioController` | central marketing portfolio/files | registered Policy using the existing `clients.*` content-management grant; file lifecycle Action: Gap | DT + forms | F,U,S | Partial |
| Platform settings | `admin.settings.*` · `SettingsController` | platform settings/cache | main settings request موجود؛ service/cache contract: Gap | View | F,U,S | Gap |
| Bank settings | `admin.settings.banks.*` · `SettingsBankController` | bank accounts/status | store/update + authenticated mutation requests and registered Policy; file Action: Gap | DT + forms | F,U,S | Partial |
| Domain settings | `admin.settings.domains.*` · `SettingsDomainController` | control domains/status | store/update + authenticated mutation requests and registered Policy; lifecycle Action: Gap | DT + forms | F,U,S | Partial |
| Versions & testers | `admin.versions.*`, `admin.version-testers.*` · `VersionController`, `VersionTesterController` | releases/tester relations/status | store/update requests + registered record Policies; lifecycle Action: Gap | DT + forms | F,U,S | Partial |
| Activity log | `admin.activity-logs.*` · `AdminActivityLogController` | central audit log | read-only, Policy scope: Gap | DT + View | F,S | Gap |
| Central archive | `admin.archives.*` · `Archive\\{AdminUser,Plan,Coupon,Tenant,Bill}*Controller` | soft-deleted central records | restore/force-delete requests & record Policies: Gap | archive DT | F,U,S | Gap |
| Central mass delete | `admin.mass-delete` · `AdminMassDeleteController` | explicit `config/models.php` allow-list | request/permission allow-list موجود؛ per-record Policy: Gap | shared handler | F,U,S | Gap |
| API integrations | `admin.api-layer.*` · `APILayerController` | external provider calls/cache | request/auth/error contract: Gap | API | F,U,S | Gap |

## 3. Tenant — identity, configuration, subscriptions

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة الأولية |
|---|---|---|---|---|---|---|
| Authentication & 2FA | `login/password/verification/tenants.two-factor.*` · `AuthenticatedSessionController`, `RegisteredUserController`, `Password*`, `ConfirmablePasswordController`, `VerifyEmailController`, `Fortify\\TenantTwoFactor*Controller` | tenant user/session/2FA | primary requests موجودة؛ every mutation/response: Gap | Views | F,I,S | Gap |
| Tenant users, teams, roles | `users.*`, `teams.*`, `roles.*` · `UserController`, `TeamController`, `RoleController` | tenant users + Spatie relations | users/teams: registered record Policies; roles: requires a guard-aware Spatie Role policy and lifecycle coverage | DT + forms | F,U,I,S | Partial |
| Tenant dashboard & charts | `dashboard.*`, `charSetting.*` · `dashboard\\DashboardController`, `ChartSettingController` | tenant KPIs/preferences/cache | dashboard service/DTO and chart request: Gap | Dashboard | F,U,I,S | Gap |
| Settings/API tokens | `settings.*` · `SettingsController`, `ApiTokenSettingsController` | tenant settings/tokens/files/cache | settings requests جزئية؛ Policy/cache lifecycle: Gap | Views | F,U,I,S | Gap |
| General settings | `general-settings.*` · `Settings{Country,PaymentMethod,ContactMethod,DocumentRequestReason,CustomerStatus}Controller`, `MessageTemplateController` | lookup dictionaries/templates | shared store/update requests موجودة؛ destroy/edit/status Policies: Gap | DT + forms | F,U,I,S | Gap |
| Notifications & activity | `notifications.*`, `activity-logs.*` · `NotificationController`, `activity_log\\ActivityLogController` | authenticated user notifications/audit | notification requests موجودة؛ owner Policy/log scope: Gap | DT + View | F,I,S | Gap |
| Tenant archive | `archives.*` · `Archive\\{User,Company,Contract,Portfolio,Customer,Assignment,Property,Auction}*Controller` | tenant soft deletes/files/relations | restore/force-delete requests and isolation Policies: Gap | archive DT | F,U,I,S | Gap |
| Tenant mass delete | `mass-delete` · `MassDeleteController` | tenant model whitelist | allow-list/request exists; Policy per row: Gap | shared handler | F,U,I,S | Gap |
| Subscription / billing / license | `subscribe.*`, `subscriptions.*`, `bills.*`, `licenses.*`, `versions.*` · `NewSubscriptionController`, `TenantSubscriptionController`, `BillsController`, `LicenseController`, `VersionController` | central gateway/payment callbacks/tenant ownership | checkout requests موجودة جزئياً; callback/idempotency/gateway: Gap | Views + API | F,U,I,S | Gap |
| Tickets | `tickets.*` · `TicketController` | central ticket storage scoped by current tenant; replies/survey | typed ticket/reply requests + scoped local gateway; external-record Policy and feature isolation: Gap | DT + forms | F,U,I,S | Partial |

## 4. Tenant — business center and customer operations

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة الأولية |
|---|---|---|---|---|---|---|
| Companies | `companies.*` · `BusinessCenter\\Company\\CompanyController`, `Archive\\Company\\CompanyArchiveController` | tenant companies/files | CRUD requests + registered record Policy؛ archive/files Action: Gap | DT + forms/archive | F,U,I,S | Partial |
| Contracts | `contracts.*` · `BusinessCenter\\Contract\\ContractController`, archive controller | contracts/company relations/files | CRUD requests + registered record Policy؛ archive lifecycle: Gap | DT + forms/archive | F,U,I,S | Partial |
| Portfolios | `portfolios.*` · `BusinessCenter\\Portfolio\\PortfolioController`, archive controller | company portfolios/files | CRUD requests + registered record Policy؛ archive/files: Gap | DT + forms/archive | F,U,I,S | Partial |
| Tasks | `tasks.*`, `my-tasks.*` · `TaskController`, `MyTaskController` | assignee/task exports | query/report service & scope: Gap | DT + View | F,U,I,S | Gap |
| Customers core | `customers.*` · `BusinessCenter\\Customer\\CustomerController`, archive controller | customers/company/portfolio relations | CRUD requests + record Policy preserving manager scope؛ archive/Action: Gap | DT + forms/archive | F,U,I,S | Partial |
| Customer communications | `customers.{comments,notes,attachments,phones,letters,sms}.*` · `Customer{Comment,Note,Attachment,PhoneRequest,Sms}Controller`, `CompanyLetterController` | customer-owned subresources/files/messages | requests جزئية; parent ownership/file lifecycle: Gap | Views/API | F,U,I,S | Gap |
| Customer finance | `customers.{payments,payment-plans,settlements,payment-history}.*` · `Customer{Payment,PaymentPlan,Settlement,PaymentHistory}Controller` | balances/installments/settlements | request/transaction/parent Policy: Gap | DT + forms | F,U,I,S | Gap |
| Customer bulk ops | `customers.{import,export,bulk-update,distribution,update-requests,follow}.*` · `Customer{Import,Export,BulkUpdate,Distribution,UpdateRequest,Follow}Controller`, `GetCustomerImportController` | files/jobs/progress/cache/owner | requests جزئية; queue ownership/idempotency/missing handler audit: Gap | API + View | F,U,I,S | Gap |

## 5. Tenant — assignments, assets, collections, communication and domains

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة الأولية |
|---|---|---|---|---|---|---|
| Assignments | `assignments.*` + archive · `AssignmentsController`, archive controller | assignment → property → auction lifecycle | wizard requests + relation validation + registered resource Policy؛ archive lifecycle Policy/Action: Gap | DT + wizard/archive | F,U,I,S | Partial |
| Properties | `properties.*` + archive · `PropertiesController`, archive controller | property/assignment/files/status | wizard requests + isolation baseline + registered resource Policy؛ archive/files: Gap | DT + wizard/archive | F,U,I,S | Partial |
| Auctions | `auctions.*` + archive · `AuctionsController`, archive controller | auction/properties/attachments | wizard requests + ownership baseline + registered resource Policy؛ archive/files: Gap | DT + wizard/archive | F,U,I,S | Partial |
| Collections | `collections.*`, `collection-process.*`, `collection-targets.*`, `collection-import.*`, `payment-reconciliation.*` · five FinancialCollection controllers | collection workflow, targets, imports, reconciliation/cache | collections/targets Policies + import ownership + collection-process manager scope; Action and transaction coverage: Gap | DT + forms/API | F,U,I,S | Partial |
| Communication logs | `communication.*` · `MessageLogController`, `MessageIndividualLogController` | message log/recipients | report/owner scope: Gap | DT + View | F,U,I,S | Gap |
| Reports | `reports.{company,customer,customer-follow,executive,financial,performance}.*` · six report controllers | filters/export files/jobs/progress | filter request/authorization/export owner: Gap | Reports/API | F,U,I,S | Gap |
| Domains & cPanel | `domains.*` · `DomainController`, `CheckMultipleDomainController`, `GetDomainInfoController`, `DomainPaymentController`, `RenewalDomainController`, `CpanelController`, `__LinkDomain\\LinkDomainController` | central domain gateway, provider API/cache/payment | FormRequest/GET mutations/missing route handlers/cross-context: Gap | DT + forms/API | F,U,I,S | Gap |

## 6. Baseline evidence and ordered remediation streams

1. **Route integrity and mutation boundary:** route reflection on 8 أكتوبر found 54 mutation methods without a typed `FormRequest`, and 15 configured tenant route actions with no matching controller method. Both counts are now zero: the handlers were restored, every active mutation has a typed FormRequest, and all 69 active request classes expose `authorize`, `rules`, `messages`, and `attributes`. `ActiveRouteHandlerContractFeatureTest` protects these conditions; no route may survive as a dead handler or bypass the request boundary.
2. **Authorization:** establish policy inventory and record authorization for every model-bound mutation, retaining the existing English permission middleware.
3. **Mutation boundary:** replace inline validation and generic `Request` in each active mutation with a dedicated request; standardise normal redirect/AJAX JSON responses.
4. **Application layer:** extract transactions, file lifecycles, asynchronous work and central crossings to Actions/Services/Gateways; do not create generic repositories.
5. **Presentation:** close DataTable, archive, form-control, dashboard-service and report-filter gaps without changing route names or database keys.
6. **Verification:** add focused Feature/Unit/isolation tests as each family becomes compliant, then run the full stage-10 acceptance gate.

The initial status is intentionally **Gap**, not a failure declaration: previous stages prove shared infrastructure and selected modules, while Phase 10 proves the full per-administration contract and records the missing evidence rather than assuming it.
