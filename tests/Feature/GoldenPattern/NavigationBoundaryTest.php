<?php

declare(strict_types=1);

namespace Tests\Feature\GoldenPattern;

use App\Http\Controllers\Tenants\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Tenants\Domains\DomainPaymentController;
use App\Http\Controllers\Tenants\Notification\NotificationController;
use App\Actions\Tenants\Notification\ManageUserNotificationsAction;
use App\DataTables\Tenants\Notifications\NotificationsDataTable;
use App\Http\Requests\Tenants\ActivityLog\ActivityLogIndexRequest;
use App\Http\Controllers\Tenants\activity_log\ActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

final class NavigationBoundaryTest extends TestCase
{
    public function test_shared_admin_prefix_does_not_resolve_tenant_management_routes(): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $request = Request::create('http://localhost/admin/users/12/edit', $method);
            try {
                Route::getRoutes()->match($request);
                $this->fail('Tenant management routes must include the tenant identifier.');
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_unverified_tenant_is_given_an_existing_tenant_view(): void
    {
        $request = Request::create('/verify-email');
        $request->setUserResolver(fn () => new class {
            public function hasVerifiedEmail(): bool { return false; }
        });
        $response = (new EmailVerificationPromptController())($request);
        $this->assertSame('tenant.auth.verify-email', $response->getName());
        $this->assertTrue(View::exists($response->getName()));
    }

    public function test_notification_index_uses_the_existing_notification_page(): void
    {
        $table = Mockery::mock(NotificationsDataTable::class);
        $table->shouldReceive('render')->once()->with('tenant.notifications.all')->andReturn('rendered');
        $controller = new NotificationController(new ManageUserNotificationsAction());
        $this->assertSame('rendered', $controller->index($table));
        $this->assertTrue(View::exists('tenant.notifications.all'));
    }

    public function test_legacy_renewal_uses_the_authorized_payment_flow(): void
    {
        $route = Route::getRoutes()->getByName('domain.domains.renewal');
        $this->assertNotNull($route);
        $this->assertSame(DomainPaymentController::class.'@startRenewal', ltrim($route->getActionName(), '\\'));
    }

    public function test_activity_id_selection_uses_its_validated_filter_request(): void
    {
        $parameter = (new ReflectionMethod(ActivityLogController::class, 'getAllIds'))->getParameters()[0];
        $this->assertSame(ActivityLogIndexRequest::class, $parameter->getType()->getName());
    }
}
