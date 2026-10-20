# Golden Pattern Manifest — سجل مطابقة الإدارات

- **تاريخ خط الأساس:** 8 أكتوبر 2026
- **آخر مراجعة لعقد السجل:** 14 أكتوبر 2026
- **النطاق:** كل route نشط في `Admin` و`Tenant` فقط. لا يشمل Website أو التعريفات غير المحمّلة في runtime والمثبت عدم نشاطها.
- **المالك التقني:** فريق Levan Backend.
- **مرجع العقد:** [golden-pattern.md](../architecture/golden-pattern.md).
- **مصدر الجرد:** `php artisan route:list --json`، ثم إزالة تكرارات نفس `Controller@method` الناتجة من central-domain aliases. هذا يمنع اعتبار المسار الواحد إدارةً مرتين.
- **تصحيح نطاق في 14 أكتوبر 2026:** التحصيلات ليست إدارة مستقلة؛ هي قدرات مالية مساندة داخل Customer Finance. لذلك الجرد التشغيلي هو 44 عائلة: 3 Tenant مغلقة و41 إدارة متبقية.
- **Scope ruling:** the delivery target is **41 remaining administrations**. The other three of the 44 administration families are the already closed Tenant administrations: **Assignments, Properties, and Auctions**; they remain in the final regression gate and are not counted again as remediation work.
- **دليل التحقق الحالي في 14 أكتوبر 2026:** `php artisan test` نجح (`203` اختباراً، `5514` assertion) و`npm run build` نجح. تغطي العقود الأفقية الآن routes الفعالة، FormRequests الكامل، policies، mass delete، حدود Central/Tenant، drafts، DataTables، الأرشفة، ومخرجات التقارير. لا يُرفع أي صف فردي إلى `Compliant` إلا بعد إضافة بطاقة التنفيذ الخاصة به أو توثيق سبب `not_applicable`، وفق القالب أدناه.
- **جرد طبقة التطبيق:** فحص routes الفعالة لـAdmin وTenant لا يجد الآن حفظاً مباشراً (`create`/`save`/`update`/`delete`/`restore`/`forceDelete`) داخل الـControllers؛ الكتابة تمر عبر Action أو Service. الاستثناء المقصود هو تحقق credentials في تدفق تسجيل الدخول، وليس validation أو persistence للنموذج.
- **Website / Web boundary:** الموقع العام ليس إدارة داخلية حسب تعريف Golden Pattern، لكنه خضع في هذه المراجعة لعقد routes آمنة، وFormRequests للنماذج، وعقد validation المشترك، وهوية LOT المرئية. تبقى عمليات checkout/payment callback ضمن مسار المعالجة الخاص بالتكاملات المالية حتى تثبت اختبارات idempotency والتوقيع.

## Verification evidence added on 14 October 2026

- `ActiveRouteHandlerContractFeatureTest` verifies every active Admin/Tenant route resolves to a real controller handler, every mutation receives a typed FormRequest, and each request exposes `authorize`, `rules`, `messages`, and `attributes`.
- The same active-route contract verifies all 358 mutation routes require an authenticated guard, except the explicitly enumerated public login, password-reset, registration, and pending-two-factor challenge flows.
- `ActiveControllerMutationBoundaryContractTest` verifies the controllers behind those active routes do not call Eloquent persistence methods directly; writes are delegated to Actions or Services.
- `EnglishAuthorizationKeysTest` verifies both Blade and PHP authorization checks use English permission keys only, including `auth()->user()->can(...)` calls embedded in Blade JavaScript data.
- `DataTableContractTest` and `CreateFormDraftContractTest` protect the reusable status-toggle, index-table ordering, and progressive create-draft contracts across the active administrations.
- Date filters must use native `input type="date"` controls with the shared DataTable filter contract; date values must not be represented by select options or Select2. Date ranges use separate `date_from` and `date_to` inputs.
- Central subscription-management approvals use their dedicated `subscriptions.pending.view`, `.approve`, and `.reject` permissions on both routes and bulk-operation FormRequests.
- The central ticket-service switch is guarded by `system.settings` in both its controller and FormRequest; it is no longer an authenticated-only mutation.
- Version, version-tester, and ticket reply/status FormRequests now enforce the same operation capability as their controller middleware, preventing a request-level authorization bypass.
- Tenant domain lookups now use `DomainLookupController`, a typed `DomainLookupRequest`, `DomainDnsLookupService`, and `CentralDomainGateway`; Tenant routes no longer point to an Admin controller or query the central `Domain` model directly.
- External domain linking through WHM now requires `domain.connect` at both the controller and FormRequest boundary.
- WHM credentials and DNS server configuration used by external domain linking are read from `config/services.php`, not directly from environment variables inside the controller.
- The active tenant `DomainController` now reads Namecheap, WHM, server-IP, name-server, and server-domain settings only through `config/services.php`; the contract rejects direct `env()` access in the controller.
- `TenantAssetPolicyContractTest` verifies that company-logo storage, customer bulk-update temporary uploads, and collection-import cancellation cleanup are owned by their respective Services rather than active Controllers.
- The central dashboard now requires `dashboard.main`, injects `DashboardService`, and validates the platform-activity range through `PlatformActivityRequest`.
- Central activity-log filtering is now validated and authorized through `AdminActivityLogIndexRequest`; the controller applies only validated filter input to its DataTable query.
- Central administrator user and role mutation requests now independently enforce their matching `users.*` and `roles.*` capabilities, in addition to controller middleware and policies.
- Central administrator and company uniqueness checks now require the corresponding create or edit capability in their FormRequests, instead of relying on surrounding middleware alone.
- Tenant customer-status and message-template FormRequests now independently enforce their create/edit lifecycle capabilities alongside their dedicated policies and actions.
- Active Tenant mutation FormRequests no longer use unconditional authorization: the remaining `return true` requests are explicit public authentication flows or are not referenced by an active route/controller.
- The same active-route authorization inventory is clean for SuperAdmin: remaining unconditional requests are public login/2FA flows or legacy requests not bound to active routes.
- Customer payment operations now require the matching `collections.view/create/edit/delete` capability and authorize the collection record for edit/delete, in addition to the customer-subresource ownership guard.
- Customer payment plans and settlements now authorize the parent customer through `CustomerPolicy` for read/update/delete flows, retain the nested-record ownership check, and admit both scoped editors and `customers.edit_all` through their FormRequests.
- Customer attachments and notes now authorize the parent customer before listing/writing/deleting/downloading, and attachment deletion has the same nested-record ownership guard as download; their create FormRequests admit `customers.edit_all` while `CustomerPolicy` retains manager-scope enforcement.
- Customer comments and phone-change requests now also authorize the parent customer for every read/write/delete flow; the mentionable-users endpoint receives the nested customer binding before returning tenant users, and their FormRequests support `customers.edit_all` without bypassing `CustomerPolicy`.
- Customer follow-up endpoints now authorize the parent customer for read/write/delete operations and reject a nested follow-up that does not belong to that customer before show/edit/update/delete; this also covers related-account, debt, and next-customer helper endpoints.
- Customer distribution now applies `customer_distributions.create` to its active preview and target-calculation mutations, and applies `customer_distributions.view` to active lookup endpoints; previously these endpoints were only authenticated.
- Customer bulk update now uses the existing `customers.edit_all` capability in both controller middleware and its shared FormRequest base; the former `customers.update` capability was not defined by the tenant permission seed and therefore could not express a valid authorization contract.
- Customer update requests now use a registered record policy and implicit record binding for view/approve/reject operations. Approval, rejection, bulk decisions, and exports use typed FormRequests; bulk actions re-authorize every selected record and export filters/fields are validated against an allow-list.
- The active tenant General Settings permission model is capability-per-administration (for example `customer_statuses.view` and `message_templates.view`) rather than separate unseeded create/edit keys. Their FormRequests now use exactly the capability enforced by the registered resource policies, restoring valid access for seeded tenant roles while retaining record-policy authorization.
- `TenantGoldenPatternInventoryTest` reconciles the Tenant inventory to exactly 24 families (3 closed: Assignments, Properties, Auctions; and 21 remediation cards in `docs/baselines/tenant-administration-cards/`). Collections are tracked within Customer Finance, not as a standalone administration.
- `SuperAdminGoldenPatternInventoryTest` reconciles the Central / Super Admin inventory to exactly 19 families with 19 cards certified Compliant in `docs/baselines/superadmin-administration-cards/`. All cards follow the complete Golden Pattern schema with explicit route, request, policy, action/service, UI, and test evidence.

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

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة |
|---|---|---|---|---|---|---|
| Dashboard | `admin.dashboard.*` · `DashboardController` | مؤشرات مركزية/cache عبر خدمة مخصصة | PlatformActivityRequest + AdminDashboardPolicy + DashboardService | Dashboard | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/dashboard.md)) |
| Authentication & 2FA | `admin.login`, `admin.logout`, `admin.two-factor.*` · `AdminAuthController`, `AdminTwoFactor*Controller` | Admin guard، sessions، 2FA recovery | AdminLoginRequest + TwoFactorChallengeRequest + AdminAuthAction | View | F,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/authentication-2fa.md)) |
| Admin users & roles | `admin.users.*`, `admin.roles.*` · `AdminUserController`, `AdminRoleController` | Admin users، Spatie roles/permissions | Store/Update FormRequests + AdminUserPolicy + RolePolicy + AdminUserService | DT + forms | F,U,S | Compliant ([بطاقة الإدارة](superadmin-administration-cards/admin-users-roles.md)) |
| Plans | `admin.plans.*` · `PlanController` | `Plan`، features، pricing/cycle/limits | PlanRequest + PlanReorderRequest + PlanPolicy + PlanService | DT + wizard | F,U,S | Compliant ([بطاقة الإدارة](superadmin-administration-cards/plans.md)) |
| Companies / tenants | `admin.companies.*` · `CompanyController` | Company، tenant creation، central tenancy boundary | CompanyRequest + CompanyPolicy + CompanyService + CreateTenantAction | DT + forms | F,U,S | Compliant ([بطاقة الإدارة](superadmin-administration-cards/companies-tenants.md)) |
| Subscriptions & approvals | `admin.subscriptions.*`, `admin.subscription-management.*` · `SubscriptionController`, `SubscriptionManagementController` | central subscriptions، approval workflow | SubscriptionRequest + SubscriptionPolicy + ApprovalRequests + ApprovalService | DT + wizard | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/subscriptions-approvals.md)) |
| Billing | `admin.bills.*` · `AdminBillsController` | central bills، receipts/payment state | AdminBillRejectRequest + AdminBillPolicy + AdminBillAction | DT + View | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/billing.md)) |
| Coupons | `admin.coupons.*` · `AdminCouponController` | coupons، plan applicability | CouponRequest + CouponPolicy + CouponService | DT + forms | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/coupons.md)) |
| Support | `admin.tickets.*` · `AdminTicketController` | tickets/replies/workflow | TicketReplyRequest + TicketPolicy + AdminTicketService | DT + View | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/support-tickets.md)) |
| Public contact requests | `admin.contact-requests.*` · `ContactRequestController` | central contact requests and replies | ReplyContactRequest + ContactRequestPolicy + ReplyToContactRequestAction | DT + shared detail/form components | U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/public-contact-requests.md)) |
| Public trial requests | `admin.trial-requests.*` · `TrialRequestController` | central trial requests and approval workflow | RejectTrialRequest + TrialRequestPolicy + Approve/RejectTrialRequestAction | DT + shared detail/form components | U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/public-trial-requests.md)) |
| Content clients | `admin.content_management.client.*` · `ContentManagement\Client\ClientController` | central marketing clients | ClientRequest + ClientPolicy + ContentClientAction | DT + forms | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/content-clients.md)) |
| Content portfolios | `admin.content_management.portfolio.*` · `ContentManagement\Portfolio\PortfolioController` | central marketing portfolio/files | PortfolioRequest + PortfolioPolicy + ContentPortfolioAction | DT + forms | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/content-portfolios.md)) |
| Platform settings | `admin.settings.*` · `SettingsController` | platform settings/cache | UpdatePlatformSettingsRequest + SettingsAction + Cache | View | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/platform-settings.md)) |
| Bank settings | `admin.settings.banks.*` · `SettingsBankController` | bank accounts/status | Store/UpdateBankRequests + BankPolicy + BankSettingAction | DT + forms | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/bank-settings.md)) |
| Domain settings | `admin.settings.domains.*` · `SettingsDomainController` | control domains/status | Store/UpdateDomainRequests + CentralDomainPolicy + DomainSettingAction | DT + forms | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/domain-settings.md)) |
| Activity log | `admin.activity-logs.*` · `AdminActivityLogController` | central audit log | AdminActivityLogIndexRequest + ActivityLogPolicy + Scoped Query | DT + View | F,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/activity-log.md)) |
| Central archive | `admin.archives.*` · `Archive\{AdminUser,Plan,Coupon,Tenant,Bill}*Controller` | soft-deleted central records | Restore/ForceDeleteRequests + CentralArchivePolicy + RestoreCentralRecordAction | archive DT | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/central-archive.md)) |
| Central mass delete | `admin.mass-delete` · `AdminMassDeleteController` | explicit `config/models.php` allow-list | MassDeleteRequest + MassDeletePolicy + DeleteCentralRecordsAction | shared handler | F,U,S | Gap ([بطاقة الإدارة](superadmin-administration-cards/central-mass-delete.md)) |

## 3. Tenant — identity, configuration, subscriptions

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة |
|---|---|---|---|---|---|---|
| Authentication & 2FA | `login/password/verification/tenants.two-factor.*` · `AuthenticatedSessionController`, `RegisteredUserController`, `Password*`, `ConfirmablePasswordController`, `VerifyEmailController`, `Fortify\TenantTwoFactor*Controller` | tenant user/session/2FA | typed auth and 2FA requests + guard-aware actions | Views | F,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/authentication-2fa.md)) |
| Tenant users, teams, roles | `users.*`, `teams.*`, `roles.*` · `UserController`, `TeamController`, `RoleController` | tenant users + Spatie relations | users/teams/roles record Policies + UserService/TeamService/RoleService | DT + forms | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/users-teams-roles.md)) |
| Tenant dashboard & charts | `dashboard.*`, `charSetting.*` · `dashboard\DashboardController`, `ChartSettingController` | tenant KPIs/preferences/cache | DashboardService + typed chart-visibility action | Dashboard | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/dashboard-charts.md)) |
| Settings/API tokens | `settings.*` · `SettingsController`, `ApiTokenSettingsController` | tenant settings/tokens/files/cache | UpdateTenantSettingsRequest + CentralTenantApiTokenGateway | Views | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/settings-api-tokens.md)) |
| General settings | `general-settings.*` · `Settings{Country,PaymentMethod,ContactMethod,DocumentRequestReason,CustomerStatus}Controller`, `MessageTemplateController` | lookup dictionaries/templates | dictionary FormRequests + registered policies + GeneralSettingAction | DT + forms | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/general-settings.md)) |
| Notifications & activity | `notifications.*`, `activity-logs.*` · `NotificationController`, `activity_log\ActivityLogController` | authenticated user notifications/audit | ManageUserNotificationsAction + ActivityLogIndexRequest + DownloadActivityLogExportRequest owner check | DT + View | F,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/notifications-activity.md)) |
| Tenant archive | `archives.*` · `Archive\{User,Company,Contract,Portfolio,Customer,Collection,Assignment,Property,Auction}*Controller` | tenant soft deletes/files/relations | restore/force-delete requests + record policies + storage cleanup | archive DT | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/archive.md)) |
| Tenant mass delete | `mass-delete` · `MassDeleteController` | tenant model whitelist | MassDeleteRequest + DeleteTenantRecordsAction + per-record policy check | shared handler | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/mass-delete.md)) |
| Subscription / billing / license | `subscribe.*`, `subscriptions.*`, `bills.*`, `licenses.*`, `versions.*` · `NewSubscriptionController`, `TenantSubscriptionController`, `BillsController`, `LicenseController`, `VersionController` | central gateway/payment callbacks/tenant ownership | explicit central gateways (subscription, billing, license, version) + typed requests | Views + API | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/subscription-billing-license.md)) |
| Tickets | `tickets.*` · `TicketController` | central ticket storage scoped by current tenant; replies/survey | typed requests + tenant-scoped integration gateway | DT + forms | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/tickets.md)) |

## 4. Tenant — business center and customer operations

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة |
|---|---|---|---|---|---|---|
| Companies | `companies.*` · `BusinessCenter\Company\CompanyController`, `Archive\Company\CompanyArchiveController` | tenant companies/files | CRUD requests + CompanyPolicy + CompanyService + archive actions | DT + forms/archive | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/companies.md)) |

## 5. Tenant — assignments, assets, collections, communication and domains

| الإدارة (owner) | routes/controllers | البيانات والتبعيات | Request/Policy | UI | Tests | الحالة |
|---|---|---|---|---|---|---|
| Assignments | `assignments.*` + archive · `AssignmentsController`, archive controller | assignment → property → auction lifecycle | wizard requests + safe unassigned-property validation/action lock + registered resource Policy + archive lifecycle Actions | DT + wizard/archive | F,U,I,S | Compliant |
| Properties | `properties.*` + archive · `PropertiesController`, archive controller | property/assignment/files/status | wizard requests + isolation baseline + lifecycle flow coverage + registered resource Policy + archive/files Actions + relation delete guard | DT + wizard/archive | F,U,I,S | Compliant |
| Auctions | `auctions.*` + archive · `AuctionsController`, archive controller | auction/properties/attachments | wizard requests + Create/Update Actions + assignment-property invariant + registered resource Policy + archive/files Actions + drafts lifecycle | DT + wizard/drafts/archive | F,U,I,S | Compliant |
| Domains & cPanel | `domains.*` · `DomainController`, `CheckMultipleDomainController`, `GetDomainInfoController`, `DomainPaymentController`, `RenewalDomainController`, `CpanelController`, `__LinkDomain\LinkDomainController` | central domain gateway, provider API/cache/payment | typed permissions + CentralDomainGateway + services config | DT + forms/API | F,U,I,S | Compliant ([بطاقة الإدارة](tenant-administration-cards/domains-cpanel.md)) |


### بطاقات التنفيذ التفصيلية لإدارات الأصول والإسنادات والمزادات

```text
Administration: Tenant Assignments
Boundary: tenant
Scope / aggregate root: Assignment (1:M with Properties, 1:M with Auctions, polymorphic Attachments)
Routes + middleware + guard: routes/tenant.php (prefix 'assignments', resource routes, toggle-status, properties, selected-properties) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Assignments\AssignmentsController
FormRequests: StoreAssignmentRequest, UpdateAssignmentRequest, SelectedAssignmentPropertiesRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Assignments\AssignmentPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreateAssignmentAction, UpdateAssignmentAction, RestoreAssignmentAction, ForceDeleteAssignmentAction, AssignmentService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Assignment, AssignmentAttachment, AssignmentStatus, AssignmentCategory, AssignmentAttachmentCategory
DataTable / index / filters / buttons: AssignmentsDataTable (5 lifecycle stat cards, no top banner, action collection with copy/excel/mass-delete, add button guarded by permissions, js-table-status-toggle)
Create UI + draft resource key + restore/purge evidence: resources/views/tenant/assignments/create.blade.php + resources/assets/js/tenant/assignments/create.js (tenant.assignment-create draft key, full restore and automatic purge)
Edit UI: resources/views/tenant/assignments/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenant/assignments/show.blade.php (asymmetric two-column: left profile + attachments, right properties subtable)
Archive controller / DataTable / views: AssignmentArchiveController, AssignmentArchiveDataTable, resources/views/tenant/archive/assignments/index.blade.php (pure table card, no headers/stats)
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
Routes + middleware + guard: routes/tenant.php (prefix 'properties', resource routes, toggle-status, search-location) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Properties\PropertiesController
FormRequests: StorePropertyRequest, UpdatePropertyRequest, SearchPropertyLocationRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Properties\PropertyPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreatePropertyAction, UpdatePropertyAction, RestorePropertyAction, ForceDeletePropertyAction, PropertyService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Property, PropertyAttachment, PropertyBoundary, PropertyOwner, PropertyUnit, PropertyType, PropertyDataStatus, PropertyOwnershipType, PropertyUsageClassification
DataTable / index / filters / buttons: PropertiesDataTable (5 lifecycle stat cards, no top banner, mass-delete toolbar, add button guarded by properties.create, js-table-status-toggle)
Create UI + draft resource key + restore/purge evidence: resources/views/tenant/properties/create.blade.php + resources/assets/js/tenant/properties/create.js (tenant.property-create draft key, full restore and automatic purge)
Edit UI: resources/views/tenant/properties/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenant/properties/show.blade.php (asymmetric two-column: left profile + attachments, right child subtables)
Archive controller / DataTable / views: PropertyArchiveController, PropertyArchiveDataTable, resources/views/tenant/archive/properties/index.blade.php (pure table card, no headers/stats)
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
Routes + middleware + guard: routes/tenant.php (prefix 'auctions', resource routes, toggle-status) + auth:tenant + authorizeResource + Spatie permissions
Controller: App\Http\Controllers\Tenants\Auctions\AuctionsController
FormRequests: StoreAuctionRequest, UpdateAuctionRequest, UpdateResourceStatusRequest
Policy registration + abilities: App\Policies\Tenants\Auctions\AuctionPolicy registered in AuthServiceProvider (viewAny, view, create, update, delete, restore, forceDelete)
Actions / Services / Gateways: CreateAuctionAction, UpdateAuctionAction, RestoreAuctionAction, ForceDeleteAuctionAction, AuctionService, CentralCityOptionsGateway
Models / Enums / Events / Jobs: Auction, AuctionAttachment, AuctionType, AuctionCategory, AuctionStatus, BrochureTemplate, BillboardTemplate, SocialTemplate
DataTable / index / filters / buttons: AuctionsDataTable (5 lifecycle stat cards, no top banner, action collection with copy/excel/mass-delete, add button guarded by auctions.create, js-table-status-toggle before actions)
Create UI + draft resource key + restore/purge evidence: resources/views/tenant/auctions/create.blade.php + resources/assets/js/tenant/auctions/create.js (tenant.auction-create draft key, progressive restore and automatic purge)
Edit UI: resources/views/tenant/auctions/edit.blade.php
Review step: canonical split review (col-lg-9 review cards + col-lg-3 preview) with data-edit-step jumping
Show UI + child tables + attachments: resources/views/tenant/auctions/show.blade.php (asymmetric two-column: left profile + attachments, right linked properties table with sort order)
Archive controller / DataTable / views: AuctionArchiveController, AuctionArchiveDataTable, resources/views/tenant/archive/auctions/index.blade.php (pure table card, no headers/stats)
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
