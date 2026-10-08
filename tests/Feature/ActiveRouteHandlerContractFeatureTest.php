<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class ActiveRouteHandlerContractFeatureTest extends TestCase
{
    public function test_every_active_admin_and_tenant_controller_route_has_a_real_handler(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route): bool {
                $action = $route->getActionName();

                return is_string($action)
                    && str_contains($action, '@')
                    && (str_starts_with($action, 'App\\Http\\Controllers\\Admin\\')
                        || str_starts_with($action, 'App\\Http\\Controllers\\Tenants\\'));
            });

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            [$controller, $method] = explode('@', $route->getActionName(), 2);

            $this->assertTrue(
                class_exists($controller) && method_exists($controller, $method),
                sprintf(
                    'Route [%s] targets unavailable action [%s].',
                    $route->getName() ?? $route->uri(),
                    $route->getActionName(),
                ),
            );
        }
    }

    public function test_every_active_mutation_uses_a_form_request(): void
    {
        $actions = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }

            $action = $route->getActionName();

            if (! is_string($action) || ! str_contains($action, '@') || (! str_starts_with($action, 'App\\Http\\Controllers\\Admin\\') && ! str_starts_with($action, 'App\\Http\\Controllers\\Tenants\\'))) {
                continue;
            }

            $actions[$action] = $route->getName() ?? $route->uri();
        }

        foreach ($actions as $action => $routeName) {
            [$controller, $method] = explode('@', $action, 2);
            $parameters = (new ReflectionMethod($controller, $method))->getParameters();
            $hasFormRequest = collect($parameters)->contains(function ($parameter): bool {
                $type = $parameter->getType();

                return $type instanceof \ReflectionNamedType
                    && ! $type->isBuiltin()
                    && is_a($type->getName(), FormRequest::class, true);
            });

            $this->assertTrue($hasFormRequest, "Mutation route [{$routeName}] must use a FormRequest.");
        }
    }

    public function test_every_active_mutation_form_request_exposes_the_full_validation_contract(): void
    {
        $requests = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }

            $action = $route->getActionName();

            if (! is_string($action) || ! str_contains($action, '@') || (! str_starts_with($action, 'App\\Http\\Controllers\\Admin\\') && ! str_starts_with($action, 'App\\Http\\Controllers\\Tenants\\'))) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin() && is_a($type->getName(), FormRequest::class, true)) {
                    $requests[$type->getName()] = true;
                }
            }
        }

        foreach (array_keys($requests) as $request) {
            foreach (['authorize', 'rules', 'messages', 'attributes'] as $method) {
                $this->assertTrue(method_exists($request, $method), "FormRequest [{$request}] must expose {$method}().");
            }
        }
    }
}
