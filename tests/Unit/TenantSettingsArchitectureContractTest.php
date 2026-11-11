<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Admin\Settings\UpdatePlatformSettingsAction;
use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Admin\Settings\SettingsController as AdminSettingsController;
use App\Http\Controllers\Tenants\License\LicenseController;
use App\Http\Controllers\Tenants\Settings\ApiTokenSettingsController;
use App\Http\Controllers\Tenants\Settings\SettingsController as TenantSettingsController;
use App\Http\Requests\Tenants\License\ManageLicenseRequest;
use App\Services\Tenants\Shared\CentralTenantApiTokenGateway;
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
}
