Administration: Tenant Domains & cPanel Integration
Boundary: tenant
Scope / aggregate root: Tenant custom domain management, DNS lookup, domain purchase/connect/renew, cPanel web hosting automation
Routes + middleware + guard: routes/tenant.php (domains.*) + auth:tenant + domains permission
Controller: App\Http\Controllers\Tenants\Domains\DomainController, DomainLookupController, CheckMultipleDomainController, GetDomainInfoController, DomainPaymentController, RenewalDomainController, CpanelController, __LinkDomain\LinkDomainController
FormRequests: DomainLookupRequest, ConnectDomainRequest, CheckTransferDomainRequest, SuggestDomainNamesRequest, SuggestDomainAlternativesRequest
Policy registration + abilities: domain.connect and payment route capabilities, with typed requests and explicit Central gateway boundaries.
Actions / Services / Gateways: DomainService, DomainDnsLookupService, CentralDomainGateway
Models / Enums / Events / Jobs: Domain (via CentralDomainGateway)
DataTable / index / filters / buttons: DomainsDataTable with DNS status indicator, SSL status, and renewal actions
Create UI + draft resource key + restore/purge evidence: Domain connection and lookup wizard with draft support
Edit UI: DNS records configuration modal
Review step: Domain purchase / connect verification step
Show UI + child tables + attachments: Domain health check overview and SSL status card
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: domains.js, form-validation.js
Files and storage lifecycle: Not applicable
Tests: Tests\Feature\TenantDashboardComprehensiveTest, Tests\Unit\TenantSettingsArchitectureContractTest, Tests\Unit\TenantCentralBoundaryContractTest
Verification commands and passing evidence: php artisan test tests/Unit/TenantCentralBoundaryContractTest.php --filter="test_tenant_domain_lookup_uses_the_tenant_controller_and_explicit_central_gateway" (1 passed, 5 assertions)
Not applicable items: Archive
Remaining gaps: None
Status: Compliant
