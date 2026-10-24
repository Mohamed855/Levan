# Golden Pattern — سجل مطابقة الإقفال

مرجع التنفيذ: [خطة الإقفال](../superpowers/plans/2026-10-21-production-closure.md). مرجع الأعمال: [الجرد](../dashboard-legacy-inventory.md). يبدأ كل صف unverified؛ وجود الملفات لا يساوي اجتياز الاختبار. آخر حصر: 2026-10-23. لا يوجد صف verified في هذه الدفعة.

| ID | الإدارة / المسار | موضع التحقق الحالي | عقد الأعمال المطلوب | الحالة |
|---|---|---|---|---|
| A01 | Admin Dashboard | app/Http/Controllers/Admin/Dashboard/DashboardController.php | stats/navigation | unverified |
| A02 | Tenants/Provisioning | app/Actions/Admin/Tenants/ProvisionTenantAction.php | create/edit/owner/settings/failure compensation | implemented_unverified |
| A03 | Plans | app/DataTables/Admin/Plans/PlanDataTable.php | limits/prices/availability/reorder/archive/drafts | implemented_unverified |
| A04 | Subscriptions | app/Actions/Admin/Subscriptions/UpdateSubscriptionStatusAction.php | create/read/transitions/period snapshots | implemented_unverified |
| A05 | Subscription follow-up | routes/admin.php | pending/expiring/expired + individual/bulk accept/reject | implemented_unverified |
| A06 | Coupons | app/Actions/Admin/Coupons/SaveCouponAction.php | application/expiry/limits/usage/archive | implemented_unverified |
| A07 | Admin Users | app/Http/Controllers/Admin/Users/AdminUserController.php | profile/avatar/roles/unique fields/archive | unverified |
| A08 | Admin Roles | app/Http/Controllers/Admin/Roles/AdminRoleController.php | permission matrix/last administrator protection | implemented_unverified |
| A09 | Template Designer | app/Actions/Admin/AuctionTemplates/PublishAuctionTemplateVersionAction.php | design/publish/version/preview/validation | implemented_unverified |
| A10 | Bills | app/Http/Requests/Admin/Bills/UpdateBillStatusRequest.php | print/verify/reject/reactivate/settlement | implemented_unverified |
| A11 | Payment transactions | app/Http/Controllers/Admin/Bills/BillController.php | legacy bill-based transaction list plus payment-attempt reconciliation | implemented_unverified |
| A12 | Tickets | app/Http/Controllers/Admin/Tickets/TicketController.php | replies/attachments/states/service switch | unverified |
| A13 | Contact Requests | app/Http/Controllers/Admin/PublicRequests/PublicRequestController.php | show/reply/delivery/audit | implemented_unverified |
| A14 | Trial Requests | app/Http/Controllers/Admin/PublicRequests/PublicRequestController.php | show/prefill/approve after provisioning/reject | implemented_unverified |
| A15 | Banks | app/Actions/Admin/Banks/SaveBankAction.php | reference fields/toggle/permissions | implemented_unverified |
| A16 | Control Domains | app/Http/Controllers/Admin/ControlDomains/ControlDomainController.php | reference fields/toggle/permissions | implemented_unverified |
| A17 | FAQ | app/Http/Controllers/Admin/Faqs/FaqController.php | CRUD/activation/public content | implemented_unverified |
| A18 | Admin Settings | app/Http/Controllers/Admin/Settings/SettingController.php | general/etmam/microsoft/security/integrations | implemented_unverified |
| A19 | Admin Activity | app/Models/Admin/ActivityLog.php | event capture/actor/before-after/redaction/read | gap: no central schema or writer |
| A20 | Admin Archives | app/Http/Controllers/Admin/Archive | users/plans/coupons/tenants/bills lifecycle | gap: admin-users archive lacks soft-delete schema |
| T01 | Tenant Dashboard | app/Http/Controllers/Tenant/Dashboard | metrics/current subscription/scoping | unverified |
| T02 | Tenant Users | app/Http/Controllers/Tenant/Users/TenantUserController.php | employee/license/avatar/roles/reset/archive | unverified |
| T03 | Tenant Roles/Permissions | app/Http/Controllers/Tenant/Roles/TenantRoleController.php | CRUD/matrix/guard separation/permission admin if applicable | unverified |
| T04 | Assignments | app/Http/Controllers/Tenant/Assignments/AssignmentController.php | forms/property selection/relations/files/drafts/archive | implemented_unverified |
| T05 | Properties | app/Http/Controllers/Tenant/Properties/PropertyController.php | full fields/owners/units/boundaries/uploads/drafts/archive | implemented_unverified |
| T06 | Auctions | app/Http/Controllers/Tenant/Auctions/AuctionController.php | forms/assignment/properties/dates/attachments | implemented_unverified |
| T07 | Auction Outputs | app/Services/Auctions/AuctionSnapshotPdfExporter.php | snapshot/compatibility/preview/export | implemented_unverified |
| T08 | Companies | app/Http/Controllers/Tenant/Companies/CompanyController.php | logo/authorized_people/default/target/drafts | unverified |
| T09 | Tenant Bills | app/Services/Tenants/Shared/CentralBillingGateway.php | read/payment/receipt/tenant ownership/central stats | implemented_unverified |
| T10 | Tenant Subscriptions | routes/tenant.php | purchase/renew/upgrade/pending/price calculation | implemented_unverified |
| T11 | Tenant Tickets | app/Services/Tenants/Shared/CentralTicketGateway.php | central context/actor/private attachments/replies | implemented_unverified |
| T12 | Notifications | app/Http/Controllers/Tenant/Notifications/TenantNotificationController.php | per-user reads/count/delete/bulk | implemented_unverified |
| T13 | Tenant Domains | routes/tenant.php | search/link/subdomain/transfer/renew/pay/DNS/cache | unverified |
| T14 | Licenses | routes/tenant.php | user entitlements/limits/payment/subscription linking | implemented_unverified |
| T15 | Tenant Settings | app/Http/Controllers/Tenant/Settings/TenantSettingController.php | general/print/SMS/email/2FA | gap: SMS and maintenance lack runtime adapters |
| T16 | Tenant API Token | routes/tenant.php | issue/rotate/revoke/ownership | implemented_unverified |
| T17 | Tenant Activity | app/Http/Controllers/Tenant/ActivityLog/TenantActivityLogController.php | capture/read/filter/archive histories | unverified |
| T18 | Tenant Archives | app/Http/Controllers/Tenant/Archive | users/companies/properties/auctions/assignments | unverified |
| W01 | Public Website | app/Http/Controllers/Web/LandingPageController.php | plans/content/privacy/contact/trial DB binding | implemented_unverified |
| W02 | Checkout | app/Http/Controllers/Web/CheckoutController.php | verify/quote/invoice/pay/provision/retry/callback | gap |
| W03 | Coupon/Tax Quote | app/Services/Web/CouponQuoteService.php | server-side quote/rounding/limits/no client trust | implemented_unverified |
| X01 | Authorization | app/Providers/AppServiceProvider.php | Policies/Gate/Requests/actions/record authorization | gap |
| X02 | DataTables | app/DataTables | all concrete HTTP endpoints/filters/search/export/scopes | unverified |
| X03 | Files/Drafts | app/Services/Shared/Wizard/WizardDraftService.php | ownership/private reads/lifecycle/cleanup | implemented_unverified |
| X04 | Queue/Scheduler/Integrations | app/Providers/TenancyServiceProvider.php | tenant jobs/mail/SMS/provider/recovery/idempotency | unverified |
| X05 | Safe Test Harness | tests/TestCase.php | DB/prefix/URL/cache refusal before test traits | implemented_unverified |
| X06 | Production Seed | database/seeders/DatabaseSeeder.php | no default passwords or demo/reference resets through the default production entry point | implemented_unverified |
| X07 | Release/Rehearsal | docs/closure | clean deployment/backups/rollback/runtime evidence | unverified |

## بنية دليل الإغلاق لكل صف

تضاف روابط ملفات Route/guard/Controller/Request/Policy/Action/Model/DataTable/View/JS/test/fixture ومرجع legacy file:line في business-parity.md. تضاف النتائج والأوامر الفعلية في implementation-ledger.md؛ لا يستخدم not_applicable لإخفاء عملية موجودة في الجرد. كل عملية مالية أو تغيير صلاحية أو حذف/restore لها دليل مستقل عن عرض القائمة.

