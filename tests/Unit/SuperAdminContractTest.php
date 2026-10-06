<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
use App\Http\Requests\Admin\Bills\RejectBillRequest;
use App\Http\Requests\Admin\DeleteBulk\MassDeleteRequest;
use App\Http\Requests\Admin\Plans\PlanRequest;
use App\Http\Requests\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\Admin\Companies\UpdateCompanyRequest;
use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use App\Http\Requests\Admin\Coupons\CouponRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SuperAdminContractTest extends TestCase
{
    public function test_admin_mass_delete_whitelist_models_are_central_and_have_permissions(): void
    {
        $reflection = new ReflectionClass(AdminMassDeleteController::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('ALLOWED_MODELS', $constants);
        $allowedModels = $constants['ALLOWED_MODELS'];

        foreach ($allowedModels as $key => $config) {
            $this->assertArrayHasKey('class', $config, "Model config {$key} must have class");
            $this->assertArrayHasKey('permission', $config, "Model config {$key} must have permission");
            $this->assertTrue(class_exists($config['class']), "Class {$config['class']} must exist");
            $this->assertMatchesRegularExpression('/^[a-z_]+(\.[a-z_]+)*$/', (string) $config['permission'], "Permission for {$key} must use English dot notation");
        }
    }

    public function test_admin_mutations_use_dedicated_form_requests(): void
    {
        $massDelete = new ReflectionClass(AdminMassDeleteController::class);
        $this->assertSame(MassDeleteRequest::class, $massDelete->getMethod('massDelete')->getParameters()[0]->getType()?->getName());

        $bills = new ReflectionClass(AdminBillsController::class);
        $this->assertSame(RejectBillRequest::class, $bills->getMethod('reject')->getParameters()[0]->getType()?->getName());

        foreach ([MassDeleteRequest::class, RejectBillRequest::class] as $requestClass) {
            $request = new ReflectionClass($requestClass);
            $this->assertTrue($request->hasMethod('authorize'));
            $this->assertTrue($request->hasMethod('rules'));
        }
    }

    public function test_super_admin_form_requests_have_authorization_and_rules(): void
    {
        $requests = [
            PlanRequest::class,
            StoreCompanyRequest::class,
            UpdateCompanyRequest::class,
            StoreSubscriptionRequest::class,
            CouponRequest::class,
        ];

        foreach ($requests as $requestClass) {
            $this->assertTrue(class_exists($requestClass), "{$requestClass} must exist");
            $ref = new ReflectionClass($requestClass);
            $this->assertTrue($ref->hasMethod('authorize'), "{$requestClass} must have authorize method");
            $this->assertTrue($ref->hasMethod('rules'), "{$requestClass} must have rules method");
        }
    }

    public function test_admin_bills_controller_has_authorization_middleware(): void
    {
        $ref = new ReflectionClass(AdminBillsController::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor, 'AdminBillsController must have a constructor');

        $content = file_get_contents($ref->getFileName());
        $this->assertStringContainsString('financial_transactions.view', $content);
        $this->assertStringContainsString('subscriptions.pending.approve', $content);
        $this->assertStringContainsString('subscriptions.pending.reject', $content);
    }

    public function test_admin_controllers_do_not_keep_inline_request_validation_rules(): void
    {
        $controllers = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path('Http/Controllers/Admin')),
        );

        foreach ($controllers as $controller) {
            if (! $controller->isFile() || $controller->getExtension() !== 'php' || str_ends_with($controller->getFilename(), '.orig')) {
                continue;
            }

            $content = file_get_contents($controller->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/\\$request->validate\\s*\\(/',
                $content,
                sprintf('%s must use a dedicated FormRequest.', $controller->getPathname()),
            );
        }
    }
}
