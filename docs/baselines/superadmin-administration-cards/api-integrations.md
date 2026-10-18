Administration: Central API Layer & External Integrations
Boundary: central
Scope / aggregate root: Central outbound integrations: Namecheap registrar API, WHM cPanel server automation, domain DNS resolution, API layer gateway
Routes + middleware + guard: Central external services gateway used by TenantDomain / CentralDomainGateway, guarded by can:domain.connect and system permissions
Controller: App\Http\Controllers\Admin\APILayer\APILayerController, App\Http\Controllers\Admin\NameCheap\NameCheapController
FormRequests: Not applicable (internal central gateway calls and service-level DTOs)
Policy registration + abilities: Not applicable (external provider infrastructure layer)
Actions / Services / Gateways: App\Services\Shared\APILayer\apiLayerService, App\Services\Shared\NameCheap\NameCheapService, App\Services\Tenants\Shared\CentralDomainGateway
Models / Enums / Events / Jobs: App\Models\Admin\ControlDomain\ControlDomain
DataTable / index / filters / buttons: Not applicable
Create UI + draft resource key + restore/purge evidence: Not applicable
Edit UI: Not applicable
Review step: Not applicable
Show UI + child tables + attachments: Not applicable
Archive controller / DataTable / views: Not applicable
Shared components and JavaScript contracts: HTTP client timeouts, sanitized logging
Files and storage lifecycle: Not applicable
Tests: Tests\Unit\SuperAdminContractTest, Tests\Unit\TenantSettingsArchitectureContractTest
Verification commands and passing evidence: php artisan test tests/Unit/SuperAdminContractTest.php tests/Unit/TenantSettingsArchitectureContractTest.php
Not applicable items: FormRequests, Policy, DataTable, create UI, edit UI, drafts, review step, show UI, archive, files
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
