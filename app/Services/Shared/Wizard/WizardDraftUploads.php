<?php

declare(strict_types=1);

namespace App\Services\Shared\Wizard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

final class WizardDraftUploads
{
    private const RESOURCE_ROUTES = [
        'admin.user-create' => 'users.store', 'admin.role-create' => 'roles.store',
        'admin.client-create' => 'clients.store', 'admin.plan-create' => 'plans.store',
        'admin.coupon-create' => 'coupons.store', 'admin.subscription-create' => 'subscriptions.store',
        'admin.bank-create' => 'settings.banks.store', 'admin.domain-create' => 'settings.domains.store',
        'admin.auction-templates.create' => 'auction-templates.store',
        'tenant.user-create' => 'users.store', 'tenant.role-create' => 'roles.store',
        'tenant.assignment-create' => 'assignments.store', 'tenant.property-create' => 'properties.store',
        'tenant.auction-create' => 'auctions.store', 'tenant.ticket-create' => 'tickets.store',
    ];

    public function __construct(private readonly WizardDraftContext $context) {}

    public function rules(Request $request): array
    {
        if ($request->allFiles() === []) {
            return [];
        }

        $route = $this->resourceRoute($request);
        $targetRequest = Request::create('/', 'POST');
        $targetRequest->setRouteResolver(fn () => $route);
        abort_unless($this->context->guard($targetRequest) === $this->context->guard($request), 403);
        $tenantParameter = $route->parameter('tenant');
        abort_if($tenantParameter !== null && (string) $tenantParameter !== (string) tenant()?->getKey(), 403);
        [$controller, $method] = explode('@', $route->getActionName());
        $resourceRequest = null;
        foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && is_a($class = $type->getName(), FormRequest::class, true)) {
                $resourceRequest = $class::createFrom($request);
                $resourceRequest->setContainer(app());
                $resourceRequest->setRouteResolver(fn () => $route);
                $resourceRequest->setMethod('POST');
                break;
            }
        }
        abort_unless($resourceRequest && $resourceRequest->authorize(), 403);
        $resourceRules = $resourceRequest->rules();
        $rules = [];
        foreach (array_keys(Arr::dot($request->allFiles())) as $field) {
            $matchedRules = null;
            foreach ($resourceRules as $pattern => $candidate) {
                if (Str::is($pattern, $field)) {
                    $matchedRules = is_string($candidate) ? explode('|', $candidate) : $candidate;
                    break;
                }
            }
            if ($matchedRules === null) {
                $rules[$field] = ['prohibited'];
                continue;
            }
            // Keep the actual file constraints, including custom closures, without requiring unrelated create fields.
            $matchedRules = array_values(array_filter($matchedRules, fn ($rule) => ! is_string($rule) || ! preg_match('/^(required|present|filled|sometimes|nullable|exclude|prohibited)(:|_|$)/', $rule)));
            $hasMax = count(array_filter($matchedRules, fn ($rule) => is_string($rule) && str_starts_with($rule, 'max:'))) > 0;
            $rules[$field] = array_merge(['file'], $matchedRules, $hasMax ? [] : ['max:10240']);
            // Resource list maxima take precedence; otherwise bound progressive uploads to 50 per field.
            $root = explode('.', $field)[0];
            if (is_array($request->file($root))) {
                $rootRules = $resourceRules[$root] ?? [];
                $rootRules = is_string($rootRules) ? explode('|', $rootRules) : $rootRules;
                $maximum = array_values(array_filter($rootRules, fn ($rule) => is_string($rule) && str_starts_with($rule, 'max:')));
                $rules[$root] = ['array', ...($maximum ?: ['max:50'])];
            }
        }
        return $rules;
    }

    private function resourceRoute(Request $request): \Illuminate\Routing\Route
    {
        $key = (string) $request->input('resource_key');
        if (isset(self::RESOURCE_ROUTES[$key])) {
            $prefix = Str::beforeLast((string) $request->route()?->getName(), 'wizard-drafts.');
            $route = app('router')->getRoutes()->getByName($prefix.self::RESOURCE_ROUTES[$key]);
            abort_unless($route, 422, 'The draft resource is unavailable.');
            $route = clone $route;
            $uri = '/'.ltrim(str_replace('{tenant}', rawurlencode((string) tenant()?->getKey()), $route->uri()), '/');
            $route->bind(Request::create($uri, 'POST', [], [], [], ['HTTP_HOST' => $request->getHost()]));
            return $route;
        }

        $target = $request->input('target_path') ?: (str_starts_with($key, '/') ? $key : null);
        abort_unless(is_string($target) && str_starts_with($target, '/') && ! str_starts_with($target, '//'), 422, 'A valid draft resource is required for uploads.');
        $targetRequest = Request::create($target, 'POST', [], [], [], ['HTTP_HOST' => $request->getHost(), 'HTTPS' => $request->isSecure() ? 'on' : 'off']);
        $route = app('router')->getRoutes()->match($targetRequest);
        $route->bind($targetRequest);
        abort_unless($route->getActionMethod() === 'store' && is_string($route->getActionName()) && str_contains($route->getActionName(), '@'), 422);
        return $route;
    }
}
