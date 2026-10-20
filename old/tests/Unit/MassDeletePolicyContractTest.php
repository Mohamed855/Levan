<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Admin\MassDelete\DeleteAdminRecordsAction;
use App\Actions\Tenants\MassDelete\DeleteTenantRecordsAction;
use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use Tests\TestCase;

final class MassDeletePolicyContractTest extends TestCase
{
    public function test_every_mass_delete_model_has_a_registered_record_policy(): void
    {
        $central = array_column(AdminMassDeleteController::ALLOWED_MODELS, 'class');
        $tenant = array_values(config('models'));

        foreach (array_unique([...$central, ...$tenant]) as $modelClass) {
            $this->assertNotNull(
                Gate::getPolicyFor($modelClass),
                "{$modelClass} must have a registered policy before it can be mass deleted."
            );
        }
    }

    public function test_mass_delete_controllers_authorize_each_loaded_record(): void
    {
        $flows = [
            AdminMassDeleteController::class => DeleteAdminRecordsAction::class,
            MassDeleteController::class => DeleteTenantRecordsAction::class,
        ];

        foreach ($flows as $controller => $action) {
            $source = file_get_contents((new ReflectionClass($controller))->getFileName());
            $actionSource = file_get_contents((new ReflectionClass($action))->getFileName());

            $this->assertStringContainsString('whereIn(\'id\', $ids)->get()', $source);
            $this->assertStringContainsString("authorize('delete', \$record)", $source);
            $this->assertStringContainsString($action, $source);
            $this->assertStringContainsString('transaction(', $actionSource);
            $this->assertStringNotContainsString('DB::transaction', $source);
        }
    }
}
