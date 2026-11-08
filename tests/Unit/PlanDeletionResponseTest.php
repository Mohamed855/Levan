<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\Admin\Plans\PlanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class PlanDeletionResponseTest extends TestCase
{
    public static function jsonHeaders(): array
    {
        return [
            'accept JSON' => [['HTTP_ACCEPT' => 'application/json']],
            'AJAX' => [['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']],
        ];
    }

    #[DataProvider('jsonHeaders')]
    public function test_deleting_a_plan_returns_json_instead_of_redirecting(array $headers): void
    {
        $plan = new Plan;
        $service = Mockery::mock(PlanService::class);
        $service->shouldReceive('delete')->once()->with($plan)->andReturn(true);
        $request = AdminAuthenticatedRequest::create('/admin/plans/1', 'DELETE', server: $headers);

        $response = (new PlanController($service))->destroy($request, $plan);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->getData(true)['success']);
        self::assertNotEmpty($response->getData(true)['message']);
    }

    public function test_a_plan_in_use_returns_a_json_domain_error(): void
    {
        $plan = new Plan;
        $service = Mockery::mock(PlanService::class);
        $service->shouldReceive('delete')->once()->with($plan)->andThrow(new DomainException('Plan is in use.'));
        $request = AdminAuthenticatedRequest::create('/admin/plans/1', 'DELETE', server: ['HTTP_ACCEPT' => 'application/json']);

        $response = (new PlanController($service))->destroy($request, $plan);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['success' => false, 'message' => 'Plan is in use.'], $response->getData(true));
    }

    public function test_an_unexpected_plan_deletion_failure_returns_a_safe_json_error(): void
    {
        $plan = new Plan;
        $service = Mockery::mock(PlanService::class);
        $service->shouldReceive('delete')->once()->with($plan)->andThrow(new RuntimeException('Private connection details'));
        $request = AdminAuthenticatedRequest::create('/admin/plans/1', 'DELETE', server: ['HTTP_ACCEPT' => 'application/json']);

        $response = (new PlanController($service))->destroy($request, $plan);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertFalse($response->getData(true)['success']);
        self::assertStringNotContainsString('Private connection details', $response->getContent());
    }

    public function test_a_regular_plan_deletion_still_redirects_with_a_flash_message(): void
    {
        $plan = new Plan;
        $service = Mockery::mock(PlanService::class);
        $service->shouldReceive('delete')->once()->with($plan)->andReturn(true);
        $request = AdminAuthenticatedRequest::create('/admin/plans/1', 'DELETE');

        $response = (new PlanController($service))->destroy($request, $plan);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(route('admin.plans.index'), $response->getTargetUrl());
        self::assertTrue(session()->has('success'));
    }
}
