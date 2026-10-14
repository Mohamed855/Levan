<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Admin\Settings\UpdatePlatformSettingsAction;
use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Admin\Settings\SettingsController as AdminSettingsController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Distribution\CustomerDistributionController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Import\GetCustomerImportController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Letter\CompanyLetterController;
use App\Http\Controllers\Tenants\Domains\CheckMultipleDomainController;
use App\Http\Controllers\Tenants\FinancialCollection\Admin\AdminCollectionsController;
use App\Http\Controllers\Tenants\FinancialCollection\CollectionProcess\CollectionProcessController;
use App\Http\Controllers\Tenants\License\LicenseController;
use App\Http\Controllers\Tenants\Settings\ApiTokenSettingsController;
use App\Http\Controllers\Tenants\Settings\SettingsController as TenantSettingsController;
use App\Http\Controllers\Tenants\Versions\VersionController;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerPortfolioFieldsRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution\DistributionPortfolioLookupRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Distribution\DistributionTargetsRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Import\CustomerImportCompanyRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Letter\PreviewCompanyLetterRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Letter\SendCompanyLetterRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\PreviewCustomerWhatsAppTemplateRequest;
use App\Http\Requests\Tenants\Domains\SuggestDomainAlternativesRequest;
use App\Http\Requests\Tenants\Domains\SuggestDomainNamesRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\ApproveSelectedAdminCollectionsRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\DeleteSelectedAdminCollectionsRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\RejectCollectionRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\RejectSelectedAdminCollectionsRequest;
use App\Http\Requests\Tenants\FinancialCollection\CollectionProcess\AddCollectionCustomerPhoneRequest;
use App\Http\Requests\Tenants\FinancialCollection\CollectionProcess\PreviewCollectionWhatsAppTemplateRequest;
use App\Http\Requests\Tenants\FinancialCollection\CollectionProcess\SetCollectionCustomerPrimaryPhoneRequest;
use App\Http\Requests\Tenants\License\ManageLicenseRequest;
use App\Jobs\Tenants\Versions\UpgradeVersionJob;
use App\Services\Tenants\Shared\CentralTenantApiTokenGateway;
use App\Services\Tenants\Shared\CentralVersionGateway;
use App\Services\Tenants\Versions\VersionService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TenantSettingsArchitectureContractTest extends TestCase
{
    public function test_settings_controllers_delegate_mutations_to_actions(): void
    {
        $admin = file_get_contents((new ReflectionClass(AdminSettingsController::class))->getFileName());
        $tenant = file_get_contents((new ReflectionClass(TenantSettingsController::class))->getFileName());

        $this->assertStringContainsString((new ReflectionClass(UpdatePlatformSettingsAction::class))->getShortName(), $admin);
        $this->assertStringContainsString('$this->updateSettings->execute(', $admin);
        $this->assertStringContainsString((new ReflectionClass(UpdateTenantSettingsAction::class))->getShortName(), $tenant);
        $this->assertStringContainsString('$this->updateSettings->execute(', $tenant);
        $this->assertStringNotContainsString('->save()', $admin);
        $this->assertStringNotContainsString('->save()', $tenant);
    }

    public function test_tenant_api_token_crossing_uses_an_explicit_central_gateway(): void
    {
        $controller = file_get_contents((new ReflectionClass(ApiTokenSettingsController::class))->getFileName());
        $gateway = file_get_contents((new ReflectionClass(CentralTenantApiTokenGateway::class))->getFileName());

        $this->assertStringContainsString((new ReflectionClass(CentralTenantApiTokenGateway::class))->getShortName(), $controller);
        $this->assertStringNotContainsString('tenant()->regenerateApiToken()', $controller);
        $this->assertStringContainsString('Tenancy::central(', $gateway);
    }

    public function test_settings_actions_own_transactions_cache_and_file_lifecycle(): void
    {
        foreach ([UpdatePlatformSettingsAction::class, UpdateTenantSettingsAction::class] as $action) {
            $source = file_get_contents((new ReflectionClass($action))->getFileName());

            $this->assertStringContainsString('DB::transaction(', $source);
            $this->assertStringContainsString("Cache::forget('app_settings')", $source);
            $this->assertStringContainsString('Storage::disk(', $source);
        }
    }

    public function test_license_mutations_use_a_dedicated_form_request(): void
    {
        $controller = new ReflectionClass(LicenseController::class);

        foreach (['assign', 'release'] as $method) {
            $parameter = $controller->getMethod($method)->getParameters()[0];

            $this->assertSame(
                ManageLicenseRequest::class,
                $parameter->getType()?->getName(),
            );
        }
    }

    public function test_admin_collection_mutations_use_dedicated_form_requests(): void
    {
        $controller = new ReflectionClass(AdminCollectionsController::class);
        $requests = [
            'reject' => RejectCollectionRequest::class,
            'approveSelected' => ApproveSelectedAdminCollectionsRequest::class,
            'rejectSelected' => RejectSelectedAdminCollectionsRequest::class,
            'deleteSelected' => DeleteSelectedAdminCollectionsRequest::class,
        ];

        foreach ($requests as $method => $request) {
            $this->assertSame($request, $controller->getMethod($method)->getParameters()[0]->getType()?->getName());
        }

        $source = file_get_contents($controller->getFileName());
        $this->assertDoesNotMatchRegularExpression('/\$request->validate\s*\(/', $source);
    }

    public function test_domain_suggestions_use_dedicated_form_requests(): void
    {
        $controller = new ReflectionClass(CheckMultipleDomainController::class);

        $this->assertSame(
            SuggestDomainAlternativesRequest::class,
            $controller->getMethod('suggestAlternatives')->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            SuggestDomainNamesRequest::class,
            $controller->getMethod('suggestNames')->getParameters()[0]->getType()?->getName(),
        );

        $source = file_get_contents($controller->getFileName());
        $this->assertStringNotContainsString('Validator::make(', $source);
    }

    public function test_customer_import_company_lookup_uses_a_dedicated_form_request(): void
    {
        $controller = new ReflectionClass(GetCustomerImportController::class);

        $this->assertSame(
            CustomerImportCompanyRequest::class,
            $controller->getMethod('getPortfoliosByCompany')->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_company_letter_mutations_use_dedicated_form_requests(): void
    {
        $controller = new ReflectionClass(CompanyLetterController::class);

        $this->assertSame(
            PreviewCompanyLetterRequest::class,
            $controller->getMethod('previewTemplate')->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            SendCompanyLetterRequest::class,
            $controller->getMethod('send')->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_distribution_lookup_and_target_preview_use_form_requests_and_service_calculation(): void
    {
        $controller = new ReflectionClass(CustomerDistributionController::class);

        $this->assertSame(
            DistributionPortfolioLookupRequest::class,
            $controller->getMethod('getPortfoliosWithUnassignedCustomers')->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            DistributionTargetsRequest::class,
            $controller->getMethod('calculateTargets')->getParameters()[0]->getType()?->getName(),
        );

        $source = file_get_contents($controller->getFileName());
        $this->assertStringContainsString('$this->distributionService->calculateMonthlyTargets(', $source);
        $this->assertStringNotContainsString('public function calculateMonthlyTargets(', $source);
    }

    public function test_customer_auxiliary_lookups_use_dedicated_form_requests(): void
    {
        $controller = new ReflectionClass(CustomerController::class);

        $this->assertSame(
            CustomerPortfolioFieldsRequest::class,
            $controller->getMethod('getPortfolioFields')->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            PreviewCustomerWhatsAppTemplateRequest::class,
            $controller->getMethod('previewWhatsAppTemplate')->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_collection_process_whatsapp_preview_uses_a_dedicated_form_request(): void
    {
        $controller = new ReflectionClass(CollectionProcessController::class);

        $this->assertSame(
            PreviewCollectionWhatsAppTemplateRequest::class,
            $controller->getMethod('previewWhatsAppTemplate')->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_collection_process_phone_mutations_use_dedicated_form_requests(): void
    {
        $controller = new ReflectionClass(CollectionProcessController::class);

        $this->assertSame(
            AddCollectionCustomerPhoneRequest::class,
            $controller->getMethod('addCustomerPhone')->getParameters()[0]->getType()?->getName(),
        );
        $this->assertSame(
            SetCollectionCustomerPrimaryPhoneRequest::class,
            $controller->getMethod('setPrimaryPhone')->getParameters()[0]->getType()?->getName(),
        );

        $source = file_get_contents($controller->getFileName());
        $this->assertStringContainsString('ManageCollectionCustomerPhoneAction', $source);
        $this->assertStringNotContainsString('CustomerPhone::create(', $source);
    }

    public function test_tenant_version_flow_crosses_central_storage_only_through_gateway(): void
    {
        foreach ([VersionController::class, VersionService::class, UpgradeVersionJob::class] as $class) {
            $source = file_get_contents((new ReflectionClass($class))->getFileName());

            $this->assertStringNotContainsString('App\\Models\\Admin', $source);
            $this->assertStringNotContainsString("DB::connection('mysql')", $source);
        }

        $gateway = file_get_contents((new ReflectionClass(CentralVersionGateway::class))->getFileName());
        $this->assertStringContainsString('Tenancy::central(', $gateway);
    }

    public function test_external_domain_linking_requires_domain_connect_permission(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Tenants/__LinkDomain/LinkDomainController.php');
        $request = file_get_contents($projectRoot.'/app/Http/Requests/Tenants/Domains/ProcessExternalDomainLinkRequest.php');

        $this->assertStringContainsString("middleware('can:domain.connect')", $controller);
        $this->assertStringContainsString("can('domain.connect')", $request);
        $this->assertStringNotContainsString('env(', $controller);
    }

    public function test_domain_controller_reads_provider_configuration_through_services_config(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Tenants/Domains/DomainController.php');

        $this->assertStringNotContainsString('env(', $controller);
        $this->assertStringContainsString("config('services.namecheap.user')", $controller);
        $this->assertStringContainsString("config('services.domains.server_ip')", $controller);
    }

    public function test_domain_payment_operations_require_their_matching_permissions(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Tenants/Domains/DomainPaymentController.php');
        $registration = file_get_contents($projectRoot.'/app/Http/Requests/Tenants/Domains/StartDomainRegistrationPaymentRequest.php');
        $renewal = file_get_contents($projectRoot.'/app/Http/Requests/Tenants/Domains/StartDomainRenewalPaymentRequest.php');
        $transfer = file_get_contents($projectRoot.'/app/Http/Requests/Tenants/Domains/StartDomainTransferPaymentRequest.php');

        $this->assertStringContainsString("middleware('can:domain.reserve')", $controller);
        $this->assertStringContainsString("middleware('can:domain.transfer')", $controller);
        $this->assertStringContainsString("can('domain.reserve')", $registration);
        $this->assertStringContainsString("can('domain.reserve')", $renewal);
        $this->assertStringContainsString("can('domain.transfer')", $transfer);
    }
}
