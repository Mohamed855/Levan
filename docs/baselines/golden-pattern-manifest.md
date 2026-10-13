# Golden Pattern Manifest — سجل مطابقة الإدارات

- **تاريخ خط الأساس:** 8 أكتوبر 2026
- **آخر مراجعة لعقد السجل:** 13 أكتوبر 2026
- **النطاق:** كل route نشط في `Admin` و`Tenant` فقط. لا يشمل Website أو التعريفات غير المحمّلة في runtime والمثبت عدم نشاطها.
- **المالك التقني:** فريق Levan Backend.
- **مرجع العقد:** [golden-pattern.md](../architecture/golden-pattern.md).
- **مصدر الجرد:** `php artisan route:list --json`، ثم إزالة تكرارات نفس `Controller@method` الناتجة من central-domain aliases. هذا يمنع اعتبار المسار الواحد إدارةً مرتين.

## 1. قواعد قراءة السجل

كل صف يمثل عائلة وظيفية واحدة، لا نسخة الـdomain من المسارات. `Central` تعني قاعدة البيانات المركزية مع `auth:admin`، و`Tenant` تعني قاعدة المستأجر التي يهيئها middleware tenancy مع `auth:tenant`. لا يسمح الصف الذي يحمل **Gap** بإغلاق الإدارة: يجب أن يحدد التعديل اللاحق الـPolicy، الـFormRequest، والاختبار الدال قبل نقله إلى **Compliant**.

الاختصارات داخل الجداول:

| الحقل | المعنى |
|---|---|
| Routes | بادئة الاسم؛ methods المقابلة تقرأ من route list، وكل mutation يجب أن يكون POST/PUT/PATCH/DELETE. |
| Request/Policy | `موجود` يعني أن العقد مثبت في الكود أو الاختبار؛ `Gap` يعني أن الإدارة ما زالت مطالبة بإكماله، وليس إقراراً بصحته. |
| UI | `DT` = DataTable، `View` = Blade، `API` = JSON/endpoint، `Dashboard/Report` = عقد خدمة view-data/report. |
| Tests | أدنى الدليل المطلوب: `F` Feature، `U` Unit، `I` tenant isolation، `S` smoke. |

### بطاقة التنفيذ التفصيلية لكل إدارة

الجدول العام أدناه هو فهرس حالة، وليس بديلًا عن تفاصيل الملفات. قبل بدء إدارة جديدة أو تعديل إدارة قائمة، يضاف تحت صفها أو في PR/issue المرتبط بها manifest تنفيذي بهذه الحقول، ولا يجوز نقل الحالة إلى **Compliant** قبل اكتمالها:

```text
Administration:
Boundary: central | tenant
Scope / aggregate root:
Routes + middleware + guard:
Controller:
FormRequests (store/update/specialized):
Policy registration + abilities:
Actions / Services / Gateways:
Models / Enums / Events / Jobs:
DataTable / index / filters / buttons:
Create UI + draft resource key + restore/purge evidence:
Edit UI:
Review step (when multi-step):
Show UI + child tables + attachments:
Archive controller / DataTable / views:
Shared components and JavaScript contracts:
Files and storage lifecycle:
Tests (Feature / Unit / Isolation / Contract):
Verification commands and passing evidence:
Not applicable items + reason:
Remaining gaps + owner:
Status: Gap | Partial | Compliant
```

قواعد الانتقال:

- **Gap:** طبقة إلزامية مفقودة أو لا يوجد دليل اختبار عليها.
- **Partial:** التدفق الأساسي يعمل لكن بطاقة التنفيذ تحتوي فجوة مسماة ومالكًا واضحًا.
- **Compliant:** كل بند إما مثبت بمسار ملف واختبار ناجح، أو `not_applicable` بسبب وظيفي محدد؛ وجود الملفات وحده لا يكفي.
- عند تعديل إدارة Compliant، يعاد فتح البنود المتأثرة في البطاقة وتشغيل اختبارات الإدارة والعقود الأفقية المتأثرة قبل إبقاء الحالة.

## 2. Central / Super Admin

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة الأولية |
|---|---|---|---|---|---|---|
| Dashboard | `admin.dashboard.*` · `DashboardController` | مؤشرات مركزية/cache عند الحاجة | Dashboard service: **Gap** | Dashboard | F,U,S | Gap |
| Authentication & 2FA | `admin.login`, `admin.logout`, `admin.two-factor.*` · `AdminAuthController`, `AdminTwoFactor*Controller` | Admin guard، sessions، 2FA recovery | Login/confirm موجود؛ mutation guards موحّدة: Gap | View | F,S | Gap |
| Admin users & roles | `admin.users.*`, `admin.roles.*` · `AdminUserController`, `AdminRoleController` | Admin users، Spatie roles/permissions | Admin users: registered record Policy; roles: guard-aware Spatie Role policy/lifecycle: Gap | DT + forms | F,U,S | Partial |
| Plans | `admin.plans.*` · `PlanController` | `Plan`، features، pricing/cycle/limits | `PlanRequest` وreorder + registered resource Policy؛ archive lifecycle Policy/requests: Gap | DT + wizard | F,U,S | Partial |
| Companies / tenants | `admin.companies.*` · `CompanyController` | Company، tenant creation، central tenancy boundary | store/update/uniqueness requests + registered resource Policy؛ archive lifecycle/Action: Gap | DT + forms | F,U,S | Partial |
| Subscriptions & approvals | `admin.subscriptions.*`, `admin.subscription-management.*` · `SubscriptionController`, `SubscriptionManagementController` | central subscriptions، approval workflow | SubscriptionController record Policy; management approval lifecycle: Gap | DT + wizard | F,U,S | Partial |
| Billing | `admin.bills.*` · `AdminBillsController` | central bills، receipts/payment state | reject request + registered record Policy for view/verify/reject/reactivate; Action workflow: Gap | DT + View | F,U,S | Partial |
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
| Notifications & activity | `notifications.*`, `activity-logs.*` · `NotificationController`, `activity_log\\ActivityLogController` | authenticated user notifications/audit | notification ownership + activity-export owner contract; record Policy/log scope: Gap | DT + View | F,I,S | Partial |
| Tenant archive | `archives.*` · `Archive\\{User,Company,Contract,Portfolio,Customer,Assignment,Property,Auction}*Controller` | tenant soft deletes/files/relations | restore/force-delete requests and isolation Policies: Gap | archive DT | F,U,I,S | Gap |
| Tenant mass delete | `mass-delete` · `MassDeleteController` | tenant model whitelist | allow-list/request exists; Policy per row: Gap | shared handler | F,U,I,S | Gap |
| Subscription / billing / license | `subscribe.*`, `subscriptions.*`, `bills.*`, `licenses.*`, `versions.*` · `NewSubscriptionController`, `TenantSubscriptionController`, `BillsController`, `LicenseController`, `VersionController` | central gateway/payment callbacks/tenant ownership | checkout requests موجودة جزئياً; callback/idempotency/gateway: Gap | Views + API | F,U,I,S | Gap |
| Tickets | `tickets.*` · `TicketController` | central ticket storage scoped by current tenant; replies/survey | typed requests + explicit guard/permissions + scoped local gateway; external-record Policy and feature isolation: Gap | DT + forms | F,U,I,S | Partial |

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

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة |
|---|---|---|---|---|---|---|
| Assignments | `assignments.*` + archive · `AssignmentsController`, archive controller | assignment → property → auction lifecycle | wizard requests + safe unassigned-property validation/action lock + registered resource Policy + archive lifecycle Actions | DT + wizard/archive | F,U,I,S | Compliant |
| Properties | `properties.*` + archive · `PropertiesController`, archive controller | property/assignment/files/status | wizard requests + isolation baseline + lifecycle flow coverage + registered resource Policy + archive/files Actions + relation delete guard | DT + wizard/archive | F,U,I,S | Compliant |
| Auctions | `auctions.*` + archive · `AuctionsController`, archive controller | auction/properties/attachments | wizard requests + Create/Update Actions + assignment-property invariant + registered resource Policy + archive/files Actions + drafts lifecycle | DT + wizard/drafts/archive | F,U,I,S | Compliant |
| Collections | `collections.*`, `collection-process.*`, `collection-targets.*`, `collection-import.*`, `payment-reconciliation.*` · five FinancialCollection controllers | collection workflow, targets, imports, reconciliation/cache | collections/targets Policies + import ownership + collection-process manager scope; Action and transaction coverage: Gap | DT + forms/API | F,U,I,S | Partial |
| Communication logs | `communication.*` · `MessageLogController`, `MessageIndividualLogController` | message log/recipients | registered record Policies and permissioned export; exported-file ownership: Gap | DT + View | F,U,I,S | Partial |
| Reports | `reports.{company,customer,customer-follow,executive,financial,performance}.*` · six report controllers | filters/export files/jobs/progress | filter request/authorization/export owner: Gap | Reports/API | F,U,I,S | Gap |
| Domains & cPanel | `domains.*` · `DomainController`, `CheckMultipleDomainController`, `GetDomainInfoController`, `DomainPaymentController`, `RenewalDomainController`, `CpanelController`, `__LinkDomain\LinkDomainController` | central domain gateway, provider API/cache/payment | FormRequest/GET mutations/missing route handlers/cross-context: Gap | DT + forms/API | F,U,I,S | Gap |

### بطاقات التنفيذ التفصيلية لإدارات الأصول والإسنادات والمزادات

```text
Administration: Tenant Assignments
Boundary: tenant
Scope / aggregate root: Assignment (1:M with Properties, 1:M with Auctions, polymorphic Attachments)
Routes + middleware + guard: routes/tenant/tenant.php (prefix 'assignments', resource routes, toggle-status, properties, selected-properties) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Assignments\AssignmentsController
FormRequests: StoreAssignmentRequest, UpdateAssignmentRequest, SelectedAssignmentPropertiesRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Assignments\AssignmentPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreateAssignmentAction, UpdateAssignmentAction, RestoreAssignmentAction, ForceDeleteAssignmentAction, AssignmentService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Assignment, AssignmentAttachment, AssignmentStatus, AssignmentCategory, AssignmentAttachmentCategory
DataTable / index / filters / buttons: AssignmentsDataTable (5 lifecycle stat cards, no top banner, action collection with copy/excel/mass-delete, add button guarded by permissions, js-table-status-toggle)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/assignments/create.blade.php + resources/assets/js/tenant/assignments/create.js (tenant.assignment-create draft key, full restore and automatic purge)
Edit UI: resources/views/tenants/assignments/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenants/assignments/show.blade.php (asymmetric two-column: left profile + attachments, right properties subtable)
Archive controller / DataTable / views: AssignmentArchiveController, AssignmentArchiveDataTable, resources/views/tenants/archive/assignments/index.blade.php (pure table card, no headers/stats)
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper
Files and storage lifecycle: tenant_public disk, atomic storage with rollback cleanup on exception, cascade deletion on forceDelete
Tests: Tests\Feature\TenantAssetModulesFeatureTest
Verification commands and passing evidence: php artisan test --filter="Auction|Assignment|Property" (22 passed, 114 assertions)
Not applicable items: None
Remaining gaps: None
Status: Compliant
```

```text
Administration: Tenant Properties
Boundary: tenant
Scope / aggregate root: Property (M:1 Assignment, M:M Auctions, 1:M Boundaries/Owners/Units, polymorphic Attachments)
Routes + middleware + guard: routes/tenant/tenant.php (prefix 'properties', resource routes, toggle-status, search-location) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Properties\PropertiesController
FormRequests: StorePropertyRequest, UpdatePropertyRequest, SearchPropertyLocationRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Properties\PropertyPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreatePropertyAction, UpdatePropertyAction, RestorePropertyAction, ForceDeletePropertyAction, PropertyService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Property, PropertyAttachment, PropertyBoundary, PropertyOwner, PropertyUnit, PropertyType, PropertyDataStatus, PropertyOwnershipType, PropertyUsageClassification
DataTable / index / filters / buttons: PropertiesDataTable (5 lifecycle stat cards, no top banner, mass-delete toolbar, add button guarded by properties.create, js-table-status-toggle)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/properties/create.blade.php + resources/assets/js/tenant/properties/create.js (tenant.property-create draft key, full restore and automatic purge)
Edit UI: resources/views/tenants/properties/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenants/properties/show.blade.php (asymmetric two-column: left profile + attachments, right child subtables)
Archive controller / DataTable / views: PropertyArchiveController, PropertyArchiveDataTable, resources/views/tenants/archive/properties/index.blade.php (pure table card, no headers/stats)
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, copyable-code-wrapper, relation delete prevention guard
Files and storage lifecycle: tenant_public disk, rollback cleanup on action exception, complete file cleanup on forceDelete
Tests: Tests\Feature\PropertyManagementFeatureTest, Tests\Feature\TenantAssetModulesFeatureTest
Verification commands and passing evidence: php artisan test --filter="Auction|Assignment|Property" (22 passed, 114 assertions)
Not applicable items: None
Remaining gaps: None
Status: Compliant
```

```text
Administration: Tenant Auctions
Boundary: tenant
Scope / aggregate root: Auction (M:1 Assignment, M:M Properties via auction_properties pivot with sort_order, 1:M AuctionAttachments)
Routes + middleware + guard: routes/tenant/tenant.php (prefix 'auctions', resource routes, toggle-status) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Auctions\AuctionsController
FormRequests: StoreAuctionRequest, UpdateAuctionRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Auctions\AuctionPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreateAuctionAction, UpdateAuctionAction, RestoreAuctionAction, ForceDeleteAuctionAction, AuctionService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Auction, AuctionAttachment, AuctionType, AuctionCategory, AuctionStatus, BrochureTemplate, BillboardTemplate, SocialTemplate
DataTable / index / filters / buttons: AuctionsDataTable (5 lifecycle stat cards, no top banner, action collection with copy/excel/mass-delete, add button guarded by auctions.create, js-table-status-toggle before actions)
Create UI + draft resource key + restore/purge evidence: resources/views/tenants/auctions/create.blade.php + resources/assets/js/tenant/auctions/create.js (tenant.auction-create draft key, progressive restore and automatic purge)
Edit UI: resources/views/tenants/auctions/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenants/auctions/show.blade.php (asymmetric two-column: left profile + attachments, right linked properties table with sort order)
Archive controller / DataTable / views: AuctionArchiveController, AuctionArchiveDataTable, resources/views/tenants/archive/auctions/index.blade.php (pure table card, no headers/stats)
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js, dynamic assignment property loading endpoint
Files and storage lifecycle: tenant_public disk, atomic cover & attachments storage, rollback cleanup on exception, complete file cleanup on forceDelete
Tests: Tests\Feature\TenantAssetModulesFeatureTest
Verification commands and passing evidence: php artisan test --filter="Auction|Assignment|Property" (22 passed, 114 assertions)
Not applicable items: None
Remaining gaps: None
Status: Compliant
```

## 6. Baseline evidence and ordered remediation streams

1. **Route integrity and mutation boundary:** route reflection on 8 أكتوبر found 54 mutation methods without a typed `FormRequest`, and 15 configured tenant route actions with no matching controller method. Both counts are now zero: the handlers were restored, every active mutation has a typed FormRequest, and all 69 active request classes expose `authorize`, `rules`, `messages`, and `attributes`. `ActiveRouteHandlerContractFeatureTest` protects these conditions; no route may survive as a dead handler or bypass the request boundary.
2. **Authorization:** establish policy inventory and record authorization for every model-bound mutation, retaining the existing English permission middleware.
3. **Mutation boundary:** replace inline validation and generic `Request` in each active mutation with a dedicated request; standardise normal redirect/AJAX JSON responses.
4. **Application layer:** extract transactions, file lifecycles, asynchronous work and central crossings to Actions/Services/Gateways; do not create generic repositories.
5. **Presentation:** close DataTable, archive, form-control, dashboard-service and report-filter gaps without changing route names or database keys.
6. **Verification:** add focused Feature/Unit/isolation tests as each family becomes compliant, then run the full stage-10 acceptance gate.

The initial status is intentionally **Gap**, not a failure declaration. This manifest proves the full per-administration contract and records missing evidence rather than assuming it.
