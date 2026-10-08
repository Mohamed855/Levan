<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\FinancialCollection\CollectionProcess\CollectionProcessController;
use App\Services\Tenants\FinancialCollection\CollectionProcess\CollectionProcessService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CollectionProcessCustomerScopeContractTest extends TestCase
{
    public function test_collection_process_uses_the_assigned_customer_scope(): void
    {
        $controller = file_get_contents((new ReflectionClass(CollectionProcessController::class))->getFileName());
        $service = file_get_contents((new ReflectionClass(CollectionProcessService::class))->getFileName());

        $this->assertStringContainsString("can:collect,customer", $controller);
        $this->assertStringContainsString('findCustomerForCurrentUser', $controller);
        $this->assertStringContainsString('where(\'relationship_manager_id\', Auth::id())', $service);
        $this->assertStringNotContainsString('findOrFail($request->customer_id)', $controller);
    }
}
