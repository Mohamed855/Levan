Administration: Central Public Trial Requests
Boundary: central
Scope / aggregate root: verified TrialRequest, resumable tenant provisioning, planless seven-day subscription
Routes + guard: site.trial / site.trial.store / site.trial.verify / site.trial.edit / site.trial.submitted; admin.trial-requests.* with auth:admin and trial_requests.view/approve/reject
Controllers: Site TrialController and Admin PublicRequests TrialRequestController
FormRequests: StoreTrialRequest (shared SubscriptionCheckoutRequest rules), VerifyTrialOtpRequest, ApproveTrialRequest, RejectTrialRequest
Policy: TrialRequestPolicy (viewAny, view, approve, reject)
Actions: CreateTrialRequestAction, ApproveTrialRequestAction, RejectTrialRequestAction
Services: RegistrationOtpService; TenantCreator; idempotent TenantInitializer; SubscriptionManager::createTrialSubscription
Models: TrialRequest, Tenant, Subscription, SubscriptionPeriod, LicenseSeat; TrialRequestStatus
Provisioning: claim a pending request with a central row lock; persist tenant_id before migrations; resume the same tenant after failure; approve only after core seat is attached; duplicate approval cannot create another account
Trial contract: plan_id null, zero price, no bill or payment, no auto-renew, seven days from successful provisioning, explicit one-user/one-property/one-active-auction/1-GB snapshot
Login: verified active tenant Admin with core license; login link in the request details; expiry enforced by exact ends_at
User limit: UsersDataTable disables add; policy denies create; management and registration actions reject additional users; trial license purchases are blocked
Permission isolation: DatabaseTenancyBootstrapper clears the registrar cache when entering/leaving a tenant
UI: shared web registration fields and validation, OTP, submitted page, central details and client login link; company/commercial number in DataTable
Legacy requests: retained; incomplete or unverified requests cannot provision a tenant
Create draft: not applicable to public unauthenticated registration, matching paid checkout
Edit/archive/mass-delete: not applicable; submitted requests are reviewed and approved/rejected
Tests: TrialRegistrationFlowTest; SiteCheckoutComponentContractTest; WebsiteFormValidationFeatureTest; MoyasarRegistrationCheckoutFeatureTest
Live verification: actual localhost HTTP signup, OTP, approval, cookie-based login, dashboard/assignments HTTP 200, user-create HTTP 403, tenant users remain one
Notifications: automatic approval email is not enabled; the admin can share the login link shown in request details
Status: implemented
